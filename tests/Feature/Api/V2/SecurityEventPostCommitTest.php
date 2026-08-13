<?php

namespace Wncms\Tests\Feature\Api\V2;

use Carbon\CarbonImmutable;
use Illuminate\Database\Events\TransactionCommitting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Wncms\Events\ApiSecurityEventRecorded;
use Wncms\Models\ApiSecurityEvent;
use Wncms\Services\Security\SecurityEventService;
use Wncms\Tests\TestCase;

class SecurityEventPostCommitTest extends TestCase
{
    public const SECOND_CONNECTION = 'task7_security_events_second';

    public const OVERRIDE_TABLE = 'task7_api_security_events';

    private bool $secondConnectionConfigured = false;

    /**
     * Configure mandatory correlation keys and clear test-owned events.
     */
    protected function setUp(): void
    {
        parent::setUp();

        config(['wncms-api-v2.auth_security.security_event_correlation' => [
            'active_key_version' => 'v1',
            'keys' => ['v1' => [
                'ip' => 'task7-post-commit-ip-key-1234567890',
                'login_identifier' => 'task7-post-commit-login-key-1234567890',
                'user_agent' => 'task7-post-commit-agent-key-1234567890',
            ]],
        ]]);
        DB::table('api_security_events')->where('request_id', 'like', 'task7-post-commit-%')->delete();
    }

    /**
     * Clear test-owned rows and any open manual transaction.
     */
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        while (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        if ($this->secondConnectionConfigured) {
            $connection = DB::connection(self::SECOND_CONNECTION);
            while ($connection->transactionLevel() > 0) {
                $connection->rollBack();
            }
            $connection->statement('DROP TABLE IF EXISTS '.self::OVERRIDE_TABLE);
            $connection->disconnect();
            DB::purge(self::SECOND_CONNECTION);
        }
        config(['wncms.models.api_security_event' => null]);
        $this->clearCachedModelClass('api_security_event');
        DB::table('api_security_events')->where('request_id', 'like', 'task7-post-commit-%')->delete();

        parent::tearDown();
    }

    /**
     * Verify an inner success produces no notification when the outer transaction rolls back.
     */
    public function test_nested_success_outer_rollback_dispatches_no_event_or_log(): void
    {
        Event::fake([ApiSecurityEventRecorded::class]);
        Log::spy();
        DB::beginTransaction();

        $this->recordInsideServiceTransaction('task7-post-commit-rollback');

        Event::assertNotDispatched(ApiSecurityEventRecorded::class);
        Log::shouldNotHaveReceived('info');
        DB::rollBack();
        Event::assertNotDispatched(ApiSecurityEventRecorded::class);
        Log::shouldNotHaveReceived('info');
        $this->assertDatabaseMissing('api_security_events', ['request_id' => 'task7-post-commit-rollback']);
    }

    /**
     * Verify the outermost successful commit emits exactly one notification and log.
     */
    public function test_outer_commit_dispatches_event_and_log_exactly_once(): void
    {
        Event::fake([ApiSecurityEventRecorded::class]);
        Log::spy();
        DB::beginTransaction();

        $this->recordInsideServiceTransaction('task7-post-commit-success');
        Event::assertNotDispatched(ApiSecurityEventRecorded::class);
        Log::shouldNotHaveReceived('info');
        DB::commit();

        Event::assertDispatchedTimes(ApiSecurityEventRecorded::class, 1);
        Log::shouldHaveReceived('info')->once();
        $this->assertDatabaseHas('api_security_events', ['request_id' => 'task7-post-commit-success']);
    }

    /**
     * Verify a commit-stage exception emits no success notification or log.
     */
    public function test_outer_commit_stage_failure_dispatches_no_event_or_log(): void
    {
        Event::fake([ApiSecurityEventRecorded::class]);
        Log::spy();
        Event::listen(TransactionCommitting::class, static function (): never {
            throw new \RuntimeException('injected commit-stage failure');
        });
        DB::beginTransaction();
        $this->recordInsideServiceTransaction('task7-post-commit-failure');

        try {
            DB::commit();
            $this->fail('The injected commit-stage failure must abort the outer commit.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('injected commit-stage failure', $exception->getMessage());
            DB::rollBack();
        }

        Event::assertNotDispatched(ApiSecurityEventRecorded::class);
        Log::shouldNotHaveReceived('info');
        $this->assertDatabaseMissing('api_security_events', ['request_id' => 'task7-post-commit-failure']);
    }

    /**
     * Verify aggregate increments retain evidence without logging every denial.
     */
    public function test_aggregate_notifies_only_for_the_first_persisted_row(): void
    {
        Event::fake([ApiSecurityEventRecorded::class]);
        Log::spy();
        $context = [
            'surface' => 'api_v2',
            'request_id' => 'task7-post-commit-aggregate-1',
            'ip' => '203.0.113.50',
            'login_identifier' => 'security.origin.denied',
            'user_agent' => 'Task7 aggregate notifier',
            'error_code' => 'authentication.origin_denied',
            'http_status' => 403,
        ];

        app(SecurityEventService::class)->recordAggregate('security.origin.denied', 'warning', 'denied', $context);
        app(SecurityEventService::class)->recordAggregate('security.origin.denied', 'warning', 'denied', [
            ...$context,
            'request_id' => 'task7-post-commit-aggregate-2',
        ]);

        Event::assertDispatchedTimes(ApiSecurityEventRecorded::class, 1);
        Log::shouldHaveReceived('info')->once();
        $event = ApiSecurityEvent::query()->where('request_id', 'task7-post-commit-aggregate-1')->firstOrFail();
        $this->assertSame(2, $event->context['aggregate']['count']);
    }

    /**
     * Verify a named-connection outer rollback discards its event and callback.
     */
    public function test_named_connection_outer_rollback_discards_event_and_notification(): void
    {
        $connection = $this->configureSecondConnection();
        Event::fake([ApiSecurityEventRecorded::class]);
        Log::spy();
        $connection->beginTransaction();

        $this->recordInsideServiceTransaction('task7-post-commit-named-rollback', self::SECOND_CONNECTION);

        Event::assertNotDispatched(ApiSecurityEventRecorded::class);
        Log::shouldNotHaveReceived('info');
        $this->assertSame(1, $connection->table('api_security_events')->where('request_id', 'task7-post-commit-named-rollback')->count());
        $connection->rollBack();

        Event::assertNotDispatched(ApiSecurityEventRecorded::class);
        Log::shouldNotHaveReceived('info');
        $this->assertSame(0, $connection->table('api_security_events')->where('request_id', 'task7-post-commit-named-rollback')->count());
        $this->assertDatabaseMissing('api_security_events', ['request_id' => 'task7-post-commit-named-rollback']);
    }

    /**
     * Verify a named-connection outer commit persists and notifies exactly once.
     */
    public function test_named_connection_outer_commit_persists_and_notifies_exactly_once(): void
    {
        $connection = $this->configureSecondConnection();
        Event::fake([ApiSecurityEventRecorded::class]);
        Log::spy();
        $connection->beginTransaction();

        $this->recordInsideServiceTransaction('task7-post-commit-named-success', self::SECOND_CONNECTION);
        Event::assertNotDispatched(ApiSecurityEventRecorded::class);
        Log::shouldNotHaveReceived('info');
        $connection->commit();

        Event::assertDispatchedTimes(ApiSecurityEventRecorded::class, 1);
        Event::assertDispatched(ApiSecurityEventRecorded::class, static fn (ApiSecurityEventRecorded $event): bool => $event->event->getConnectionName() === self::SECOND_CONNECTION);
        Log::shouldHaveReceived('info')->once();
        $this->assertSame(1, $connection->table('api_security_events')->where('request_id', 'task7-post-commit-named-success')->count());
    }

    /**
     * Verify a model override owns its default connection, custom table, and post-commit boundary.
     */
    public function test_model_override_default_connection_rolls_back_and_commits_with_exact_notification(): void
    {
        $connection = $this->configureOverrideModel();
        $coreCount = DB::table('api_security_events')->count();
        Event::fake([ApiSecurityEventRecorded::class]);
        Log::spy();
        $connection->beginTransaction();

        $this->recordInsideServiceTransaction('task7-post-commit-override-rollback');
        $this->assertSame(1, $connection->table(self::OVERRIDE_TABLE)->where('request_id', 'task7-post-commit-override-rollback')->count());
        Event::assertNotDispatched(ApiSecurityEventRecorded::class);
        $connection->rollBack();

        $this->assertSame(0, $connection->table(self::OVERRIDE_TABLE)->where('request_id', 'task7-post-commit-override-rollback')->count());
        Event::assertNotDispatched(ApiSecurityEventRecorded::class);
        Log::shouldNotHaveReceived('info');

        $connection->beginTransaction();
        $this->recordInsideServiceTransaction('task7-post-commit-override-success');
        Event::assertNotDispatched(ApiSecurityEventRecorded::class);
        $connection->commit();

        $this->assertSame(1, $connection->table(self::OVERRIDE_TABLE)->where('request_id', 'task7-post-commit-override-success')->count());
        $this->assertSame($coreCount, DB::table('api_security_events')->count());
        Event::assertDispatchedTimes(ApiSecurityEventRecorded::class, 1);
        Event::assertDispatched(ApiSecurityEventRecorded::class, static fn (ApiSecurityEventRecorded $event): bool => $event->event instanceof Task7ApiSecurityEventOverride
            && $event->event->getConnectionName() === self::SECOND_CONNECTION
            && $event->event->getTable() === self::OVERRIDE_TABLE);
        Log::shouldHaveReceived('info')->once();
    }

    /**
     * Verify aggregate reads and writes use the override model table and connection.
     */
    public function test_model_override_aggregate_uses_custom_table_and_leaves_core_table_untouched(): void
    {
        $connection = $this->configureOverrideModel();
        $coreCount = DB::table('api_security_events')->count();
        CarbonImmutable::setTestNow('2026-08-14 08:00:00 UTC');
        $context = [
            'surface' => 'api_v2',
            'request_id' => 'task7-post-commit-override-aggregate-1',
            'ip' => '203.0.113.71',
            'login_identifier' => 'override-aggregate@example.test',
            'user_agent' => 'Task7 override aggregate',
        ];

        $first = app(SecurityEventService::class)->recordAggregate('security.origin.denied', 'warning', 'denied', $context);
        $firstOccurredAt = $first->context['aggregate']['first_occurred_at'];
        CarbonImmutable::setTestNow('2026-08-14 08:01:00 UTC');
        $updated = app(SecurityEventService::class)->recordAggregate('security.origin.denied', 'warning', 'denied', [
            ...$context,
            'request_id' => 'task7-post-commit-override-aggregate-2',
        ]);

        $this->assertInstanceOf(Task7ApiSecurityEventOverride::class, $updated);
        $this->assertSame(2, $updated->context['aggregate']['count']);
        $this->assertSame($firstOccurredAt, $updated->context['aggregate']['first_occurred_at']);
        $this->assertSame('2026-08-14T08:01:00+00:00', $updated->context['aggregate']['last_occurred_at']);
        $this->assertSame(1, $connection->table(self::OVERRIDE_TABLE)->count());
        $this->assertSame($coreCount, DB::table('api_security_events')->count());
    }

    /**
     * Verify an invalid registry override fails before mutation or observability dispatch.
     */
    public function test_invalid_security_event_model_override_fails_closed_without_mutation_or_dispatch(): void
    {
        config(['wncms.models.api_security_event' => ['class' => Task7InvalidSecurityEventOverride::class]]);
        $this->clearCachedModelClass('api_security_event');
        Event::fake([ApiSecurityEventRecorded::class]);
        $mutated = false;

        try {
            app(SecurityEventService::class)->withinTransaction(function () use (&$mutated): void {
                $mutated = true;
            }, [
                'type' => 'auth.refresh.succeeded',
                'severity' => 'info',
                'outcome' => 'succeeded',
                'context' => ['request_id' => 'task7-post-commit-invalid-override'],
            ], null, [DB::connection()->getName()]);
            $this->fail('An invalid security-event model override must fail closed.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Invalid api_security_event model override.', $exception->getMessage());
        }

        $this->assertFalse($mutated);
        $this->assertDatabaseMissing('api_security_events', ['request_id' => 'task7-post-commit-invalid-override']);
        Event::assertNotDispatched(ApiSecurityEventRecorded::class);

        config(['wncms.models.api_security_event' => ['class' => Task7PlainEloquentSecurityEventOverride::class]]);
        $this->clearCachedModelClass('api_security_event');

        try {
            app(SecurityEventService::class)->record('auth.refresh.succeeded', 'info', 'succeeded', [
                'request_id' => 'task7-post-commit-invalid-eloquent-override',
            ]);
            $this->fail('A non-BaseModel Eloquent override must fail closed.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Invalid api_security_event model override.', $exception->getMessage());
        }

        $this->assertDatabaseMissing('api_security_events', ['request_id' => 'task7-post-commit-invalid-eloquent-override']);
        Event::assertNotDispatched(ApiSecurityEventRecorded::class);
    }

    /**
     * Verify a failing event listener cannot fail an already committed caller.
     */
    public function test_event_listener_failure_after_commit_is_non_fatal_and_redacted(): void
    {
        Log::spy();
        Event::listen(ApiSecurityEventRecorded::class, static function (): never {
            throw new \RuntimeException('CANARY-EVENT-LISTENER-CONTEXT');
        });

        $result = $this->recordInsideServiceTransaction('task7-post-commit-event-listener-failure');

        $this->assertNull($result);
        $this->assertDatabaseHas('api_security_events', ['request_id' => 'task7-post-commit-event-listener-failure']);
        Log::shouldHaveReceived('info')->once();
    }

    /**
     * Verify a failing log driver cannot fail an already committed caller.
     */
    public function test_log_driver_failure_after_commit_is_non_fatal_and_redacted(): void
    {
        Event::fake([ApiSecurityEventRecorded::class]);
        Log::shouldReceive('info')->once()->andThrow(new \RuntimeException('CANARY-LOG-DRIVER-CONTEXT'));

        $result = $this->recordInsideServiceTransaction('task7-post-commit-log-driver-failure');

        $this->assertNull($result);
        $this->assertDatabaseHas('api_security_events', ['request_id' => 'task7-post-commit-log-driver-failure']);
        Event::assertDispatchedTimes(ApiSecurityEventRecorded::class, 1);
    }

    /**
     * Persist one mandatory event from a nested service transaction.
     */
    private function recordInsideServiceTransaction(string $requestId, ?string $connectionName = null): mixed
    {
        $service = app(SecurityEventService::class);
        $requiredConnectionNames = $connectionName === null
            ? $service->modelConnectionNames(['api_security_event'])
            : [DB::connection($connectionName)->getName()];

        return $service->withinTransaction(static fn (): null => null, [
            'type' => 'auth.refresh.succeeded',
            'severity' => 'info',
            'outcome' => 'succeeded',
            'context' => [
                'surface' => 'api_v2',
                'request_id' => $requestId,
            ],
        ], $connectionName, $requiredConnectionNames);
    }

    /**
     * Configure an isolated named SQLite connection with the prepared schema.
     */
    private function configureSecondConnection(): \Illuminate\Database\Connection
    {
        $this->secondConnectionConfigured = true;
        config(['database.connections.'.self::SECOND_CONNECTION => [
            ...config('database.connections.sqlite'),
            'database' => config('database.connections.sqlite.database'),
        ]]);
        DB::purge(self::SECOND_CONNECTION);

        return DB::connection(self::SECOND_CONNECTION);
    }

    /**
     * Configure the registry override with a non-default connection and custom table.
     */
    private function configureOverrideModel(): \Illuminate\Database\Connection
    {
        $connection = $this->configureSecondConnection();
        $connection->statement('DROP TABLE IF EXISTS '.self::OVERRIDE_TABLE);
        $connection->statement('CREATE TABLE '.self::OVERRIDE_TABLE.' AS SELECT event_id, occurred_at, event_type, severity, outcome, surface, request_id, run_id, actor_type, actor_id, target_type, target_id, credential_type, credential_id, session_id, website_ids, error_code, http_status, ip_hash, login_identifier_hash, user_agent_hash, correlation_key_version, aggregate_key, mutation_audit_id, context, created_at, updated_at FROM api_security_events WHERE 0');
        $connection->statement('CREATE UNIQUE INDEX task7_security_event_event_id_unique ON '.self::OVERRIDE_TABLE.' (event_id)');
        $connection->statement('CREATE UNIQUE INDEX task7_security_event_aggregate_key_unique ON '.self::OVERRIDE_TABLE.' (aggregate_key)');
        config(['wncms.models.api_security_event' => ['class' => Task7ApiSecurityEventOverride::class]]);
        $this->clearCachedModelClass('api_security_event');

        return $connection;
    }

    /**
     * Forget a test-owned WNCMS registry entry.
     */
    private function clearCachedModelClass(string $key): void
    {
        $wncms = wncms();
        $reflection = new \ReflectionObject($wncms);
        $property = $reflection->getProperty('modelClassCache');
        $property->setAccessible(true);
        $cache = (array) $property->getValue($wncms);
        unset($cache[$key]);
        $property->setValue($wncms, $cache);
    }
}

/**
 * Test-only security event model with host-owned storage defaults.
 */
class Task7ApiSecurityEventOverride extends ApiSecurityEvent
{
    protected $connection = SecurityEventPostCommitTest::SECOND_CONNECTION;

    protected $table = SecurityEventPostCommitTest::OVERRIDE_TABLE;

    protected $primaryKey = 'event_id';

    public $incrementing = false;

    protected $keyType = 'string';
}

/**
 * Test-only Eloquent override with a mismatched model identity.
 */
class Task7InvalidSecurityEventOverride extends ApiSecurityEvent
{
    public static $modelKey = 'wrong_security_event';
}

/**
 * Test-only plain Eloquent override that does not satisfy the WNCMS model contract.
 */
class Task7PlainEloquentSecurityEventOverride extends \Illuminate\Database\Eloquent\Model
{
    public static $modelKey = 'api_security_event';
}
