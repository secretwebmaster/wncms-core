<?php

namespace Wncms\Tests\Unit\Api\V2;

use Carbon\CarbonImmutable;
use Illuminate\Cache\ArrayStore;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Mockery;
use Ramsey\Uuid\Uuid;
use Wncms\Api\V2\Contracts\AtomicOperationRepository;
use Wncms\Api\V2\Contracts\OperationRepository;
use Wncms\Api\V2\Data\AsyncOperation;
use Wncms\Api\V2\Enums\AsyncOperationStatus;
use Wncms\Api\V2\Exceptions\ApiConflictException;
use Wncms\Api\V2\OperationService;
use Wncms\Api\V2\Repositories\CacheOperationRepository;
use Wncms\Tests\TestCase;

class OperationServiceTest extends TestCase
{
    protected const OPERATION_ID = '123e4567-e89b-42d3-a456-426614174080';

    /**
     * Freeze operation identifiers and timestamps for deterministic state tests.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        CarbonImmutable::setTestNow('2026-08-12 08:00:00 UTC');
        Str::createUuidsUsingSequence([Uuid::fromString(self::OPERATION_ID)]);
    }

    /**
     * Restore global UUID and time factories after each state test.
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
     * Verify queue creates an immutable, deterministic operation and persists its configured lifetime.
     *
     * Changing the initial status, timestamp timezone, or TTL would break the asynchronous contract.
     *
     * @return void
     */
    public function test_queue_creates_an_immutable_operation_with_stable_uuid_and_utc_timestamps(): void
    {
        $repository = new InMemoryOperationRepository();
        $service = new OperationService($repository, 86400);

        $operation = $service->queue('plugins.upgrade', 42, [9, 3], true);

        $this->assertSame(self::OPERATION_ID, $operation->id);
        $this->assertSame('plugins.upgrade', $operation->type);
        $this->assertSame(AsyncOperationStatus::Queued, $operation->status);
        $this->assertSame(0, $operation->progress);
        $this->assertTrue($operation->cancellable);
        $this->assertSame(42, $operation->actorId);
        $this->assertSame([9, 3], $operation->websiteIds);
        $this->assertNull($operation->result);
        $this->assertNull($operation->error);
        $this->assertSame('2026-08-12T08:00:00Z', $operation->createdAt);
        $this->assertSame('2026-08-12T08:00:00Z', $operation->updatedAt);
        $this->assertSame('2026-08-13T08:00:00Z', $operation->expiresAt);
        $this->assertSame(86400, $repository->lastTtlSeconds);

        $this->expectException(\Error::class);
        $operation->progress = 1;
    }

    /**
     * Verify a running operation can report progress and complete successfully with its result.
     *
     * @return void
     */
    public function test_queued_operation_can_run_progress_and_succeed(): void
    {
        $repository = new InMemoryOperationRepository();
        $service = new OperationService($repository, 86400);
        $queued = $service->queue('themes.install', 7, [12], true);

        CarbonImmutable::setTestNow('2026-08-12 08:00:10 UTC');
        $running = $service->start($queued->id);
        CarbonImmutable::setTestNow('2026-08-12 08:00:20 UTC');
        $progressed = $service->progress($queued->id, 37);
        CarbonImmutable::setTestNow('2026-08-12 08:00:30 UTC');
        $result = ['installed' => true, 'version' => '7.0.0'];
        $succeeded = $service->succeed($queued->id, $result);

        $this->assertNotSame($queued, $running);
        $this->assertSame(AsyncOperationStatus::Running, $running->status);
        $this->assertSame(0, $running->progress);
        $this->assertSame('2026-08-12T08:00:10Z', $running->updatedAt);
        $this->assertSame(AsyncOperationStatus::Running, $progressed->status);
        $this->assertSame(37, $progressed->progress);
        $this->assertSame(AsyncOperationStatus::Succeeded, $succeeded->status);
        $this->assertSame(100, $succeeded->progress);
        $this->assertFalse($succeeded->cancellable);
        $this->assertSame($result, $succeeded->result);
        $this->assertNull($succeeded->error);
        $this->assertSame('2026-08-12T08:00:30Z', $succeeded->updatedAt);
        $this->assertSame('2026-08-13T08:00:00Z', $succeeded->expiresAt);
        $this->assertSame(86370, $repository->lastTtlSeconds);
    }

    /**
     * Verify a running operation can terminate with structured error details.
     *
     * @return void
     */
    public function test_running_operation_can_fail_with_structured_error_details(): void
    {
        $repository = new InMemoryOperationRepository();
        $service = new OperationService($repository, 86400);
        $queued = $service->queue('updates.core', 11, [], true);
        $service->start($queued->id);
        $service->progress($queued->id, 63);

        $error = ['code' => 'download_failed', 'details' => ['status' => 503]];
        $failed = $service->fail($queued->id, $error);

        $this->assertSame(AsyncOperationStatus::Failed, $failed->status);
        $this->assertSame(63, $failed->progress);
        $this->assertFalse($failed->cancellable);
        $this->assertNull($failed->result);
        $this->assertSame($error, $failed->error);
    }

    /**
     * Verify progress accepts both inclusive boundaries while an operation is running.
     *
     * @return void
     */
    public function test_progress_accepts_zero_and_one_hundred_while_running(): void
    {
        $repository = new InMemoryOperationRepository();
        $service = new OperationService($repository, 86400);
        $queued = $service->queue('exports.posts', 5);
        $service->start($queued->id);

        $this->assertSame(0, $service->progress($queued->id, 0)->progress);
        $this->assertSame(100, $service->progress($queued->id, 100)->progress);
    }

    /**
     * Verify progress outside the inclusive contract range is rejected as a conflict.
     *
     * @return void
     */
    public function test_progress_rejects_values_outside_zero_to_one_hundred(): void
    {
        $repository = new InMemoryOperationRepository();
        $service = new OperationService($repository, 86400);
        $first = $service->queue('exports.posts', 5);
        $service->start($first->id);

        try {
            $service->progress($first->id, -1);
            $this->fail('Negative progress should be rejected.');
        } catch (ApiConflictException) {
            $this->assertSame(0, $repository->find($first->id)?->progress);
        }

        $second = $service->queue('exports.pages', 5);
        $service->start($second->id);

        $this->expectException(ApiConflictException::class);
        $service->progress($second->id, 101);
    }

    /**
     * Verify transition methods reject states outside the declared state graph.
     *
     * @return void
     */
    public function test_invalid_state_transitions_throw_api_conflicts(): void
    {
        $repository = new InMemoryOperationRepository();
        $service = new OperationService($repository, 86400);
        $queued = $service->queue('plugins.delete', 4);

        try {
            $service->progress($queued->id, 10);
            $this->fail('Queued progress should be rejected.');
        } catch (ApiConflictException) {
            $this->assertSame(AsyncOperationStatus::Queued, $repository->find($queued->id)?->status);
        }

        try {
            $service->succeed($queued->id);
            $this->fail('Queued success should be rejected.');
        } catch (ApiConflictException) {
            $this->assertSame(AsyncOperationStatus::Queued, $repository->find($queued->id)?->status);
        }

        try {
            $service->fail($queued->id, ['code' => 'failed']);
            $this->fail('Queued failure should be rejected.');
        } catch (ApiConflictException) {
            $this->assertSame(AsyncOperationStatus::Queued, $repository->find($queued->id)?->status);
        }

        $service->start($queued->id);

        $this->expectException(ApiConflictException::class);
        $service->start($queued->id);
    }

    /**
     * Verify queued and running cancellable operations can be cancelled exactly once.
     *
     * Repeating cancellation must return the existing terminal value without another persistence write.
     *
     * @return void
     */
    public function test_cancellation_is_legal_from_queued_or_running_and_idempotent_afterwards(): void
    {
        $repository = new InMemoryOperationRepository();
        $service = new OperationService($repository, 86400);
        $queued = $service->queue('plugins.activate', 4, [], true);
        $cancelledFromQueue = $service->cancel($queued->id);
        $saveCount = $repository->saveCount;
        $cancelledAgain = $service->cancel($queued->id);

        $this->assertSame(AsyncOperationStatus::Cancelled, $cancelledFromQueue->status);
        $this->assertFalse($cancelledFromQueue->cancellable);
        $this->assertEquals($cancelledFromQueue, $cancelledAgain);
        $this->assertSame($saveCount, $repository->saveCount);

        $runningCandidate = $service->queue('themes.activate', 4, [], true);
        $service->start($runningCandidate->id);
        $cancelledFromRunning = $service->cancel($runningCandidate->id);

        $this->assertSame(AsyncOperationStatus::Cancelled, $cancelledFromRunning->status);
    }

    /**
     * Verify non-cancellable and completed operations cannot be cancelled.
     *
     * @return void
     */
    public function test_cancellation_rejects_non_cancellable_and_completed_operations(): void
    {
        $repository = new InMemoryOperationRepository();
        $service = new OperationService($repository, 86400);
        $nonCancellable = $service->queue('exports.posts', 4, [], false);

        try {
            $service->cancel($nonCancellable->id);
            $this->fail('A non-cancellable operation should be rejected.');
        } catch (ApiConflictException) {
            $this->assertSame(AsyncOperationStatus::Queued, $repository->find($nonCancellable->id)?->status);
        }

        $completed = $service->queue('themes.install', 4, [], true);
        $service->start($completed->id);
        $service->succeed($completed->id);

        $this->expectException(ApiConflictException::class);
        $service->cancel($completed->id);
    }

    /**
     * Verify actor ownership and DTO expiration are both enforced without disclosing records.
     *
     * @return void
     */
    public function test_find_for_actor_hides_other_actors_and_forgets_expired_operations(): void
    {
        $repository = new InMemoryOperationRepository();
        $service = new OperationService($repository, 86400);
        $operation = $service->queue('packages.check', 99);

        $this->assertEquals($operation, $service->findForActor($operation->id, 99));
        $this->assertNull($service->findForActor($operation->id, 100));

        CarbonImmutable::setTestNow('2026-08-13 08:00:01 UTC');

        $this->assertNull($service->findForActor($operation->id, 99));
        $this->assertNull($repository->find($operation->id));
    }

    /**
     * Verify cache serialization restores enum and mixed payload types exactly.
     *
     * @return void
     */
    public function test_cache_repository_round_trips_the_complete_typed_operation_record(): void
    {
        $repository = new CacheOperationRepository(app(CacheFactory::class), 'array');
        $result = [
            'integer' => 8,
            'float' => 2.5,
            'boolean' => true,
            'null' => null,
            'string' => 'done',
            'status' => 'running',
            'nested' => ['ids' => [3, '4']],
        ];
        $operation = new AsyncOperation(
            id: self::OPERATION_ID,
            type: 'imports.posts',
            status: AsyncOperationStatus::Failed,
            progress: 72,
            cancellable: false,
            actorId: 31,
            websiteIds: [2, '7'],
            result: $result,
            error: ['code' => 'row_invalid', 'row' => 9],
            createdAt: '2026-08-12T07:00:00Z',
            updatedAt: '2026-08-12T07:30:00Z',
            expiresAt: '2026-08-13T07:00:00Z',
        );

        $repository->save($operation, 3600);
        $restored = $repository->find(self::OPERATION_ID);

        $this->assertEquals($operation, $restored);
        $this->assertInstanceOf(AsyncOperationStatus::class, $restored?->status);
        $this->assertSame($result, $restored?->result);
        $this->assertSame([2, '7'], $restored?->websiteIds);
    }

    /**
     * Verify logical operation expiration is enforced even while a cache record still exists.
     *
     * @return void
     */
    public function test_cache_repository_hides_and_removes_logically_expired_records(): void
    {
        $repository = new CacheOperationRepository(app(CacheFactory::class), 'array');
        $operation = new AsyncOperation(
            id: self::OPERATION_ID,
            type: 'imports.posts',
            status: AsyncOperationStatus::Queued,
            progress: 0,
            cancellable: true,
            actorId: 31,
            websiteIds: [],
            result: null,
            error: null,
            createdAt: '2026-08-11T07:00:00Z',
            updatedAt: '2026-08-11T07:00:00Z',
            expiresAt: '2026-08-12T07:59:59Z',
        );

        $repository->save($operation, 60);

        $this->assertNull($repository->find(self::OPERATION_ID));
    }

    /**
     * Verify production rejects local-only cache stores that lose cross-process operation state.
     *
     * @return void
     */
    public function test_cache_repository_rejects_non_shared_array_store_in_production(): void
    {
        $repository = new CacheOperationRepository(app(CacheFactory::class), 'array', true);

        $this->expectException(\InvalidArgumentException::class);
        $repository->find(self::OPERATION_ID);
    }

    /**
     * Verify a failed cache write is surfaced instead of silently losing operation state.
     *
     * @return void
     */
    public function test_cache_repository_throws_when_the_cache_write_fails(): void
    {
        $cache = Mockery::mock(CacheFactory::class);
        $cacheRepository = Mockery::mock(CacheRepository::class);
        $cache->shouldReceive('store')->once()->with('failing')->andReturn($cacheRepository);
        $cacheRepository->shouldReceive('getStore')->once()->andReturn(new ArrayStore());
        $cacheRepository->shouldReceive('put')->once()->andReturn(false);
        $repository = new CacheOperationRepository($cache, 'failing');
        $operation = new AsyncOperation(
            id: self::OPERATION_ID,
            type: 'imports.posts',
            status: AsyncOperationStatus::Queued,
            progress: 0,
            cancellable: true,
            actorId: 31,
            websiteIds: [],
            result: null,
            error: null,
            createdAt: '2026-08-12T08:00:00Z',
            updatedAt: '2026-08-12T08:00:00Z',
            expiresAt: '2026-08-13T08:00:00Z',
        );

        $this->expectException(\RuntimeException::class);
        $repository->save($operation, 86400);
    }

    /**
     * Verify the container binds the cache repository and the configured one-day service lifetime.
     *
     * @return void
     */
    public function test_service_provider_binds_operation_contracts_with_the_configured_ttl(): void
    {
        config([
            'wncms-api-v2.operations.store' => 'array',
            'wncms-api-v2.operations.ttl_seconds' => 86400,
        ]);
        app()->forgetInstance(AtomicOperationRepository::class);
        app()->forgetInstance(OperationRepository::class);
        app()->forgetInstance(OperationService::class);

        $repository = app(OperationRepository::class);
        $operation = app(OperationService::class)->queue('plugins.upgrade', 42);

        $this->assertInstanceOf(CacheOperationRepository::class, $repository);
        $this->assertSame('2026-08-13T08:00:00Z', $operation->expiresAt);
        $this->assertEquals($operation, $repository->find($operation->id));
    }
}

final class InMemoryOperationRepository implements AtomicOperationRepository
{
    /**
     * @var array<string, \Wncms\Api\V2\Data\AsyncOperation>
     */
    public array $operations = [];

    public int $saveCount = 0;

    public ?int $lastTtlSeconds = null;

    /**
     * Save an operation for state-machine tests.
     *
     * @param  \Wncms\Api\V2\Data\AsyncOperation  $operation
     * @param  int  $ttlSeconds
     *
     * @return void
     */
    public function save(AsyncOperation $operation, int $ttlSeconds): void
    {
        $this->operations[$operation->id] = $operation;
        $this->lastTtlSeconds = $ttlSeconds;
        $this->saveCount++;
    }

    /**
     * Find an operation for state-machine tests.
     *
     * @param  string  $id
     *
     * @return \Wncms\Api\V2\Data\AsyncOperation|null
     */
    public function find(string $id): ?AsyncOperation
    {
        return $this->operations[$id] ?? null;
    }

    /**
     * Forget an operation for state-machine tests.
     *
     * @param  string  $id
     *
     * @return void
     */
    public function forget(string $id): void
    {
        unset($this->operations[$id]);
    }

    /**
     * Replace the expected in-memory operation when it remains current.
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
        if (($this->operations[$expected->id] ?? null) !== $expected) {
            return false;
        }

        $this->save($replacement, $ttlSeconds);

        return true;
    }
}
