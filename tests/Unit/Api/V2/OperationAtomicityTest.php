<?php

namespace Wncms\Tests\Unit\Api\V2;

use Carbon\CarbonImmutable;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as IlluminateCacheRepository;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Lock as CacheLock;
use Illuminate\Support\Str;
use Mockery;
use Ramsey\Uuid\Uuid;
use Wncms\Api\V2\Contracts\AtomicOperationRepository;
use Wncms\Api\V2\Data\AsyncOperation;
use Wncms\Api\V2\Enums\AsyncOperationStatus;
use Wncms\Api\V2\Exceptions\ApiConflictException;
use Wncms\Api\V2\OperationService;
use Wncms\Api\V2\Repositories\CacheOperationRepository;
use Wncms\Tests\TestCase;

class OperationAtomicityTest extends TestCase
{
    protected const OPERATION_ID = '123e4567-e89b-42d3-a456-426614174080';

    /**
     * Freeze operation identifiers and timestamps for deterministic concurrency tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-08-12 08:00:00 UTC');
        Str::createUuidsUsingSequence([Uuid::fromString(self::OPERATION_ID)]);
    }

    /**
     * Restore global UUID and time factories after each concurrency test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Str::createUuidsNormally();
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    /**
     * Verify a cancellation that wins a race cannot be overwritten by a stale worker completion.
     *
     * @return void
     */
    public function test_concurrent_cancellation_wins_without_reviving_a_terminal_operation(): void
    {
        $repository = new InterleavingOperationRepository();
        $worker = new OperationService($repository, 86400);
        $canceller = new OperationService($repository, 86400);
        $operation = $worker->queue('plugins.upgrade', 42, [], true);
        $worker->start($operation->id);
        $repository->beforeNextCompareAndSwap = static function () use ($canceller, $operation): void {
            $canceller->cancel($operation->id);
        };

        try {
            $worker->succeed($operation->id, ['version' => '7.0.0']);
            $this->fail('A stale worker completion must not overwrite cancellation.');
        } catch (ApiConflictException) {
            $current = $repository->find($operation->id);

            $this->assertSame(AsyncOperationStatus::Cancelled, $current?->status);
            $this->assertNull($current?->result);
        }
    }

    /**
     * Verify one transient compare-and-swap miss is retried against fresh state.
     *
     * @return void
     */
    public function test_transition_retries_a_transient_compare_and_swap_miss(): void
    {
        $repository = new InterleavingOperationRepository();
        $repository->remainingForcedMisses = 1;
        $service = new OperationService($repository, 86400);
        $operation = $service->queue('themes.install', 42);

        $running = $service->start($operation->id);

        $this->assertSame(AsyncOperationStatus::Running, $running->status);
        $this->assertSame(2, $repository->compareAndSwapCount);
    }

    /**
     * Verify persistent contention ends in a clear conflict after bounded retries.
     *
     * @return void
     */
    public function test_transition_stops_after_bounded_compare_and_swap_conflicts(): void
    {
        $repository = new InterleavingOperationRepository();
        $repository->alwaysMiss = true;
        $service = new OperationService($repository, 86400);
        $operation = $service->queue('themes.install', 42);

        try {
            $service->start($operation->id);
            $this->fail('Persistent transition contention must become an API conflict.');
        } catch (ApiConflictException $exception) {
            $this->assertSame('Operation was modified concurrently', $exception->getMessage());
            $this->assertSame(3, $repository->compareAndSwapCount);
            $this->assertSame(AsyncOperationStatus::Queued, $repository->find($operation->id)?->status);
        }
    }

    /**
     * Verify persisted opaque revisions reject ABA even when every public DTO field matches again.
     *
     * @return void
     */
    public function test_cache_compare_and_swap_rejects_an_aba_record_with_identical_dto_fields(): void
    {
        $repository = new CacheOperationRepository(app(CacheFactory::class), 'array');
        $queued = $this->operation(AsyncOperationStatus::Queued);
        $repository->save($queued, 86400);
        $stale = $repository->find(self::OPERATION_ID);
        $repository->save($this->operation(AsyncOperationStatus::Running), 86400);
        $repository->save($this->operation(AsyncOperationStatus::Queued), 86400);

        $swapped = $repository->compareAndSwap(
            $stale,
            $this->operation(AsyncOperationStatus::Running),
            86400
        );

        $this->assertFalse($swapped);
        $this->assertSame(AsyncOperationStatus::Queued, $repository->find(self::OPERATION_ID)?->status);
    }

    /**
     * Verify backend lock contention makes compare-and-swap fail without writing.
     *
     * @return void
     */
    public function test_cache_compare_and_swap_does_not_write_when_the_backend_lock_is_contended(): void
    {
        $store = new ContendedOperationArrayStore();
        $cacheRepository = new IlluminateCacheRepository($store);
        $cache = Mockery::mock(CacheFactory::class);
        $cache->shouldReceive('store')->once()->with('contended')->andReturn($cacheRepository);
        $repository = new CacheOperationRepository($cache, 'contended');
        $queued = $this->operation(AsyncOperationStatus::Queued);
        $repository->save($queued, 86400);
        $expected = $repository->find(self::OPERATION_ID);
        $store->contendLocks = true;

        $swapped = $repository->compareAndSwap(
            $expected,
            $this->operation(AsyncOperationStatus::Running),
            86400
        );

        $this->assertFalse($swapped);
        $store->contendLocks = false;
        $this->assertSame(AsyncOperationStatus::Queued, $repository->find(self::OPERATION_ID)?->status);
    }

    /**
     * Build a valid operation at the requested state without changing second-precision timestamps.
     *
     * @param  \Wncms\Api\V2\Enums\AsyncOperationStatus  $status
     *
     * @return \Wncms\Api\V2\Data\AsyncOperation
     */
    private function operation(AsyncOperationStatus $status): AsyncOperation
    {
        return new AsyncOperation(
            id: self::OPERATION_ID,
            type: 'plugins.upgrade',
            status: $status,
            progress: $status === AsyncOperationStatus::Running ? 10 : 0,
            cancellable: true,
            actorId: 42,
            websiteIds: [1, '2'],
            result: null,
            error: null,
            createdAt: '2026-08-12T08:00:00Z',
            updatedAt: '2026-08-12T08:00:00Z',
            expiresAt: '2026-08-13T08:00:00Z',
        );
    }
}

final class InterleavingOperationRepository implements AtomicOperationRepository
{
    /**
     * @var array<string, \Wncms\Api\V2\Data\AsyncOperation>
     */
    private array $operations = [];

    /**
     * @var array<string, int>
     */
    private array $revisions = [];

    /**
     * @var \WeakMap<\Wncms\Api\V2\Data\AsyncOperation, int>
     */
    private \WeakMap $observedRevisions;

    public ?\Closure $beforeNextCompareAndSwap = null;

    public int $remainingForcedMisses = 0;

    public bool $alwaysMiss = false;

    public int $compareAndSwapCount = 0;

    /**
     * Create the interleaving repository test double.
     */
    public function __construct()
    {
        $this->observedRevisions = new \WeakMap();
    }

    /**
     * Save an operation and issue a distinct internal revision.
     *
     * @param  \Wncms\Api\V2\Data\AsyncOperation  $operation
     * @param  int  $ttlSeconds
     *
     * @return void
     */
    public function save(AsyncOperation $operation, int $ttlSeconds): void
    {
        $revision = ($this->revisions[$operation->id] ?? 0) + 1;
        $this->operations[$operation->id] = $operation;
        $this->revisions[$operation->id] = $revision;
        $this->observedRevisions[$operation] = $revision;
    }

    /**
     * Find an operation and retain the revision observed with that immutable instance.
     *
     * @param  string  $id
     *
     * @return \Wncms\Api\V2\Data\AsyncOperation|null
     */
    public function find(string $id): ?AsyncOperation
    {
        $operation = $this->operations[$id] ?? null;
        if ($operation instanceof AsyncOperation) {
            $this->observedRevisions[$operation] = $this->revisions[$id];
        }

        return $operation;
    }

    /**
     * Forget an operation and its internal revision.
     *
     * @param  string  $id
     *
     * @return void
     */
    public function forget(string $id): void
    {
        unset($this->operations[$id], $this->revisions[$id]);
    }

    /**
     * Replace an operation only when the expected immutable instance still owns the current revision.
     *
     * @param  \Wncms\Api\V2\Data\AsyncOperation  $expected
     * @param  \Wncms\Api\V2\Data\AsyncOperation  $replacement
     * @param  int  $ttlSeconds
     *
     * @return bool
     */
    public function compareAndSwap(
        AsyncOperation $expected,
        AsyncOperation $replacement,
        int $ttlSeconds
    ): bool {
        $this->compareAndSwapCount++;

        $interleave = $this->beforeNextCompareAndSwap;
        $this->beforeNextCompareAndSwap = null;
        if ($interleave instanceof \Closure) {
            $interleave();
        }

        if ($this->alwaysMiss || $this->remainingForcedMisses-- > 0) {
            return false;
        }

        $observed = $this->observedRevisions[$expected] ?? null;
        if ($observed === null || $observed !== ($this->revisions[$expected->id] ?? null)) {
            return false;
        }

        $this->save($replacement, $ttlSeconds);

        return true;
    }
}

final class ContendedOperationArrayStore extends ArrayStore
{
    public bool $contendLocks = false;

    /**
     * Return a deliberately contended lock when requested by the test.
     *
     * @param  string  $name
     * @param  int  $seconds
     * @param  string|null  $owner
     *
     * @return \Illuminate\Contracts\Cache\Lock
     */
    public function lock($name, $seconds = 0, $owner = null)
    {
        if ($this->contendLocks) {
            return new ContendedOperationLock();
        }

        return parent::lock($name, $seconds, $owner);
    }
}

final class ContendedOperationLock implements CacheLock
{
    /**
     * Refuse lock acquisition.
     *
     * @param  callable|null  $callback
     *
     * @return false
     */
    public function get($callback = null)
    {
        return false;
    }

    /**
     * Refuse blocking lock acquisition.
     *
     * @param  int  $seconds
     * @param  callable|null  $callback
     *
     * @return false
     */
    public function block($seconds, $callback = null)
    {
        return false;
    }

    /**
     * Report that no lock was released.
     *
     * @return false
     */
    public function release()
    {
        return false;
    }

    /**
     * Return the fixed owner used by this contended lock.
     *
     * @return string
     */
    public function owner()
    {
        return 'contended';
    }

    /**
     * Ignore forced release for this non-acquired lock.
     *
     * @return void
     */
    public function forceRelease()
    {
    }
}
