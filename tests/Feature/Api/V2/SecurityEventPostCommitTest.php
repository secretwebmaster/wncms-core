<?php

namespace Wncms\Tests\Feature\Api\V2;

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
    private const SECOND_CONNECTION = 'task7_security_events_second';

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
        while (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        if ($this->secondConnectionConfigured) {
            $connection = DB::connection(self::SECOND_CONNECTION);
            while ($connection->transactionLevel() > 0) {
                $connection->rollBack();
            }
            $connection->disconnect();
            DB::purge(self::SECOND_CONNECTION);
        }
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
        return app(SecurityEventService::class)->withinTransaction(static fn (): null => null, [
            'type' => 'auth.refresh.succeeded',
            'severity' => 'info',
            'outcome' => 'succeeded',
            'context' => [
                'surface' => 'api_v2',
                'request_id' => $requestId,
            ],
        ], $connectionName);
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
}
