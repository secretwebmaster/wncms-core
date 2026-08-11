<?php

namespace Wncms\Tests\Unit\Api\V2;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use JsonSerializable;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;
use Wncms\Api\V2\Contracts\AtomicOperationRepository;
use Wncms\Api\V2\Data\AsyncOperation;
use Wncms\Api\V2\Enums\AsyncOperationStatus;
use Wncms\Api\V2\OperationService;
use Wncms\Api\V2\Repositories\CacheOperationRepository;
use Wncms\Tests\TestCase;

class OperationValidationTest extends TestCase
{
    protected const OPERATION_ID = '123e4567-e89b-42d3-a456-426614174080';

    /**
     * Freeze time for deterministic operation validation.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-08-12 08:00:00 UTC');
    }

    /**
     * Restore global time after each validation test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    /**
     * Verify queue rejects invalid domain inputs before the repository receives a write.
     *
     * @return void
     */
    public function test_queue_rejects_invalid_domain_inputs_before_first_write(): void
    {
        $repository = new ValidationProbeOperationRepository();
        $service = new OperationService($repository, 86400);
        $invalidCalls = [
            static fn (): AsyncOperation => $service->queue('', 42),
            static fn (): AsyncOperation => $service->queue('plugins.upgrade', 0),
            static fn (): AsyncOperation => $service->queue('plugins.upgrade', 42, ['']),
        ];

        foreach ($invalidCalls as $call) {
            try {
                $call();
                $this->fail('Invalid queue input must be rejected.');
            } catch (\InvalidArgumentException) {
                $this->assertSame(0, $repository->writeCount);
            }
        }
    }

    /**
     * Verify transitions reject every unsafe JSON value without overwriting running state.
     *
     * @return void
     */
    public function test_succeed_rejects_non_json_safe_results_before_compare_and_swap(): void
    {
        $recursive = [];
        $recursive['self'] = &$recursive;
        $resource = fopen('php://memory', 'r');
        $invalidValues = [
            'invalid_utf8' => "\xB1\x31",
            'nan' => NAN,
            'positive_infinity' => INF,
            'negative_infinity' => -INF,
            'resource' => $resource,
            'recursive_array' => $recursive,
            'unsafe_object' => new UnsafeOperationPayload(),
            'throwing_json_serializable' => new ThrowingOperationPayload(),
        ];

        try {
            foreach ($invalidValues as $name => $value) {
                $repository = new ValidationProbeOperationRepository();
                $service = new OperationService($repository, 86400);
                $queued = $service->queue('plugins.upgrade', 42);
                $running = $service->start($queued->id);
                $writesBefore = $repository->writeCount;

                try {
                    $service->succeed($queued->id, ['case' => $name, 'value' => $value]);
                    $this->fail("Unsafe result case {$name} must be rejected.");
                } catch (\InvalidArgumentException) {
                    $this->assertEquals($running, $repository->find($queued->id), $name);
                    $this->assertSame($writesBefore, $repository->writeCount, $name);
                }
            }
        } finally {
            if (is_resource($resource)) {
                fclose($resource);
            }
        }
    }

    /**
     * Verify failure details are subject to the same JSON-safety boundary as success results.
     *
     * @return void
     */
    public function test_fail_rejects_non_json_safe_error_before_compare_and_swap(): void
    {
        $repository = new ValidationProbeOperationRepository();
        $service = new OperationService($repository, 86400);
        $queued = $service->queue('plugins.upgrade', 42);
        $running = $service->start($queued->id);
        $writesBefore = $repository->writeCount;

        try {
            $service->fail($queued->id, ['code' => 'failed', 'context' => NAN]);
            $this->fail('A non-JSON-safe operation error must be rejected.');
        } catch (\InvalidArgumentException) {
            $this->assertEquals($running, $repository->find($queued->id));
            $this->assertSame($writesBefore, $repository->writeCount);
        }
    }

    /**
     * Verify every transition validates the current operation before creating replacement state.
     *
     * @return void
     */
    public function test_transition_rejects_an_invalid_current_operation_before_writing(): void
    {
        $repository = new ValidationProbeOperationRepository();
        $service = new OperationService($repository, 86400);
        $queued = $service->queue('plugins.upgrade', 42);
        $repository->replaceWithoutValidation($this->operation([
            'id' => $queued->id,
            'actorId' => 0,
        ]));
        $writesBefore = $repository->writeCount;

        try {
            $service->start($queued->id);
            $this->fail('A transition must reject invalid persisted operation state.');
        } catch (\InvalidArgumentException) {
            $this->assertSame($writesBefore, $repository->writeCount);
            $this->assertSame(0, $repository->find($queued->id)?->actorId);
        }
    }

    /**
     * Verify repository writes enforce all operation-domain invariants before touching cache.
     *
     * @param  array<string, mixed>  $overrides
     *
     * @return void
     */
    #[DataProvider('invalidOperationOverrides')]
    public function test_repository_rejects_invalid_operation_domain_fields_before_write(array $overrides): void
    {
        $repository = new CacheOperationRepository(app(CacheFactory::class), 'array');
        $key = $this->recordKey(self::OPERATION_ID);
        cache()->store('array')->forget($key);

        try {
            $repository->save($this->operation($overrides), 86400);
            $this->fail('An invalid operation must not be persisted.');
        } catch (\InvalidArgumentException) {
            $this->assertNull(cache()->store('array')->get($key));
        }
    }

    /**
     * Provide invalid operation-domain fields for the shared validator boundary.
     *
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function invalidOperationOverrides(): array
    {
        return [
            'uuid' => [['id' => 'not-a-uuid']],
            'empty type' => [['type' => '   ']],
            'invalid UTF-8 type' => [['type' => "plugins.\xB1"]],
            'negative progress' => [['progress' => -1]],
            'progress over one hundred' => [['progress' => 101]],
            'non-positive actor' => [['actorId' => 0]],
            'website IDs not a list' => [['websiteIds' => ['primary' => 1]]],
            'invalid integer website ID' => [['websiteIds' => [0]]],
            'invalid string website ID' => [['websiteIds' => ['']]],
            'non-UTC timestamp' => [['updatedAt' => '2026-08-12T16:00:00+08:00']],
            'updated before creation' => [['updatedAt' => '2026-08-11T08:00:00Z']],
            'expiry not after update' => [['expiresAt' => '2026-08-12T08:00:00Z']],
        ];
    }

    /**
     * Verify corrupt cached records are evicted and treated as missing instead of causing repeated errors.
     *
     * @return void
     */
    public function test_repository_forgets_a_corrupt_hydrated_record_and_returns_null(): void
    {
        $repository = new CacheOperationRepository(app(CacheFactory::class), 'array');
        $key = $this->recordKey(self::OPERATION_ID);
        cache()->store('array')->put($key, [
            'version' => 2,
            'revision' => str_repeat('a', 32),
            'id' => self::OPERATION_ID,
            'type' => 'plugins.upgrade',
            'status' => 'running',
            'progress' => 25,
            'cancellable' => true,
            'actor_id' => 42,
            'website_ids' => [1],
            'result' => ['unsafe' => NAN],
            'error' => null,
            'created_at' => '2026-08-12T08:00:00Z',
            'updated_at' => '2026-08-12T08:00:00Z',
            'expires_at' => '2026-08-13T08:00:00Z',
        ], 86400);

        $this->assertNull($repository->find(self::OPERATION_ID));
        $this->assertNull(cache()->store('array')->get($key));
    }

    /**
     * Verify supported JSON scalar, list, map, and standard-object payloads keep stable wire values.
     *
     * @return void
     */
    public function test_json_safe_payload_shapes_remain_stable_on_the_wire(): void
    {
        $repository = new ValidationProbeOperationRepository();
        $service = new OperationService($repository, 86400);
        $queued = $service->queue('plugins.upgrade', 42);
        $service->start($queued->id);
        $object = new stdClass();
        $object->enabled = true;
        $payload = [
            'null' => null,
            'bool' => true,
            'integer' => 7,
            'float' => 2.5,
            'string' => 'ready',
            'list' => [1, '2'],
            'map' => ['version' => '7.0.0'],
            'object' => $object,
        ];

        $completed = $service->succeed($queued->id, $payload);
        $wire = json_decode(json_encode($completed, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame([
            'null' => null,
            'bool' => true,
            'integer' => 7,
            'float' => 2.5,
            'string' => 'ready',
            'list' => [1, '2'],
            'map' => ['version' => '7.0.0'],
            'object' => ['enabled' => true],
        ], $wire['result']);
    }

    /**
     * Build an operation with optional field replacements.
     *
     * @param  array<string, mixed>  $overrides
     *
     * @return \Wncms\Api\V2\Data\AsyncOperation
     */
    private function operation(array $overrides = []): AsyncOperation
    {
        $fields = array_merge([
            'id' => self::OPERATION_ID,
            'type' => 'plugins.upgrade',
            'status' => AsyncOperationStatus::Queued,
            'progress' => 0,
            'cancellable' => true,
            'actorId' => 42,
            'websiteIds' => [1, '2'],
            'result' => null,
            'error' => null,
            'createdAt' => '2026-08-12T08:00:00Z',
            'updatedAt' => '2026-08-12T08:00:00Z',
            'expiresAt' => '2026-08-13T08:00:00Z',
        ], $overrides);

        return new AsyncOperation(...$fields);
    }

    /**
     * Build the package operation-record cache key.
     *
     * @param  string  $id
     *
     * @return string
     */
    private function recordKey(string $id): string
    {
        return 'wncms:api-v2:operation:'.hash('sha256', $id);
    }
}

final class ValidationProbeOperationRepository implements AtomicOperationRepository
{
    /**
     * @var array<string, \Wncms\Api\V2\Data\AsyncOperation>
     */
    private array $operations = [];

    public int $writeCount = 0;

    /**
     * Save an operation for validator service tests.
     *
     * @param  \Wncms\Api\V2\Data\AsyncOperation  $operation
     * @param  int  $ttlSeconds
     *
     * @return void
     */
    public function save(AsyncOperation $operation, int $ttlSeconds): void
    {
        $this->operations[$operation->id] = $operation;
        $this->writeCount++;
    }

    /**
     * Find an operation for validator service tests.
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
     * Forget an operation for validator service tests.
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
     * Replace the expected operation for validator service tests.
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

    /**
     * Inject invalid state without counting a service write.
     *
     * @param  \Wncms\Api\V2\Data\AsyncOperation  $operation
     *
     * @return void
     */
    public function replaceWithoutValidation(AsyncOperation $operation): void
    {
        $this->operations[$operation->id] = $operation;
    }
}

final class UnsafeOperationPayload
{
    public string $value = 'unsafe';
}

final class ThrowingOperationPayload implements JsonSerializable
{
    /**
     * Throw if an unsafe object reaches JSON serialization.
     *
     * @return mixed
     */
    public function jsonSerialize(): mixed
    {
        throw new \RuntimeException('Unsafe JSON serialization was invoked');
    }
}
