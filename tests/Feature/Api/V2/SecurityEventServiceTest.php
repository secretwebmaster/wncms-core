<?php

namespace Wncms\Tests\Feature\Api\V2;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Wncms\Models\ApiSecurityEvent;
use Wncms\Services\Security\SecurityEventRetentionService;
use Wncms\Services\Security\SecurityEventService;
use Wncms\Tests\TestCase;

class SecurityEventServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected SecurityEventService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'wncms-api-v2.auth_security.security_event_correlation' => [
                'active_key_version' => 'v1',
                'keys' => [
                    'v1' => [
                        'ip' => 'test-security-correlation-ip-key-123456',
                        'login_identifier' => 'test-security-correlation-login-key-123456',
                        'user_agent' => 'test-security-correlation-agent-key-123456',
                    ],
                ],
            ],
        ]);

        $this->service = app(SecurityEventService::class);
    }

    public function test_event_builder_discards_unknown_and_redacts_sensitive_fields(): void
    {
        $event = $this->service->record('auth.login.failed', 'warning', 'denied', [
            'request_id' => (string) Str::uuid(),
            'password' => 'CANARY-PASSWORD',
            'nested' => ['confirmation_token' => 'CANARY-CONFIRMATION'],
            'unexpected' => 'not-allowlisted',
        ]);

        $serialized = json_encode($event->toArray(), JSON_THROW_ON_ERROR);

        $this->assertStringNotContainsString('CANARY-', $serialized);
        $this->assertArrayNotHasKey('unexpected', $event->context ?? []);
        $this->assertSame('auth.login.failed', $event->event_type);
        $this->assertSame('warning', $event->severity);
        $this->assertSame('denied', $event->outcome);
    }

    public function test_credential_mutation_and_mandatory_event_commit_or_roll_back_together(): void
    {
        $beforeCount = ApiSecurityEvent::count();

        try {
            $this->service->withinTransaction(function (): void {
                DB::table('api_service_tokens')->insert([
                    'token_id' => 'transactional-service-token',
                    'token_hash' => hash('sha256', 'transactional-secret'),
                    'user_id' => 1,
                    'name' => 'Transaction test',
                    'ability_template' => 'read_only',
                    'abilities' => json_encode(['read']),
                    'website_ids' => json_encode([1]),
                    'expires_at' => now()->addDay(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }, [
                'type' => 'invalid.event.type',
                'severity' => 'warning',
                'outcome' => 'denied',
            ]);

            $this->fail('An invalid mandatory security event must reject the mutation.');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame('Unsupported security event type.', $e->getMessage());
        }

        $this->assertSame($beforeCount, ApiSecurityEvent::count());
        $this->assertDatabaseMissing('api_service_tokens', ['token_id' => 'transactional-service-token']);
    }

    public function test_event_context_keeps_only_allowlisted_nested_leaves(): void
    {
        $event = $this->service->record('auth.login.failed', 'warning', 'denied', [
            'context' => [
                'reason' => 'invalid_credentials',
                'aggregate' => [
                    'count' => 1,
                    'first_occurred_at' => '2026-08-13T00:00:00Z',
                    'last_occurred_at' => '2026-08-13T00:00:00Z',
                    'latest_request_id' => 'request-1',
                    'nested_unknown' => 'CANARY-NESTED-UNKNOWN',
                    'apiKey' => 'CANARY-API-KEY',
                ],
                'unknown' => 'CANARY-TOP-LEVEL-UNKNOWN',
            ],
        ]);

        $context = $event->context ?? [];

        $this->assertSame('invalid_credentials', $context['reason']);
        $this->assertSame([
            'count' => 1,
            'first_occurred_at' => '2026-08-13T00:00:00Z',
            'last_occurred_at' => '2026-08-13T00:00:00Z',
            'latest_request_id' => 'request-1',
        ], $context['aggregate']);
        $this->assertStringNotContainsString('CANARY-', json_encode($event->toArray(), JSON_THROW_ON_ERROR));
    }

    public function test_security_mutation_fails_closed_when_correlation_keys_are_missing(): void
    {
        config(['wncms-api-v2.auth_security.security_event_correlation' => []]);

        try {
            $this->service->withinTransaction(function (): void {
                DB::table('api_service_tokens')->insert([
                    'token_id' => 'missing-correlation-service-token',
                    'token_hash' => hash('sha256', 'missing-correlation-secret'),
                    'user_id' => 1,
                    'name' => 'Missing correlation key',
                    'ability_template' => 'read_only',
                    'abilities' => json_encode(['read']),
                    'website_ids' => json_encode([1]),
                    'expires_at' => now()->addDay(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }, [
                'type' => 'auth.service_token.created',
                'severity' => 'critical',
                'outcome' => 'succeeded',
            ]);

            $this->fail('A mandatory security mutation must fail without correlation keys.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Security event correlation keys are unavailable.', $e->getMessage());
        }

        $this->assertDatabaseMissing('api_service_tokens', ['token_id' => 'missing-correlation-service-token']);
    }

    public function test_aggregate_recording_updates_one_correlation_tuple_atomically(): void
    {
        CarbonImmutable::setTestNow('2026-08-13 00:00:00 UTC');
        $first = $this->service->recordAggregate('auth.login.failed', 'warning', 'denied', [
            'surface' => 'api_v2',
            'request_id' => 'aggregate-request-1',
            'ip' => '203.0.113.10',
            'login_identifier' => 'aggregate@example.test',
            'user_agent' => 'Aggregate test agent',
            'context' => ['reason' => 'invalid_credentials'],
        ]);
        $firstOccurredAt = $first->context['aggregate']['first_occurred_at'];
        CarbonImmutable::setTestNow('2026-08-13 00:01:00 UTC');
        $second = $this->service->recordAggregate('auth.login.failed', 'warning', 'denied', [
            'surface' => 'api_v2',
            'request_id' => 'aggregate-request-2',
            'ip' => '203.0.113.10',
            'login_identifier' => 'aggregate@example.test',
            'user_agent' => 'Aggregate test agent',
            'context' => ['reason' => 'invalid_credentials'],
        ]);

        $this->assertSame($first->getKey(), $second->getKey());
        $this->assertSame(1, ApiSecurityEvent::where('event_type', 'auth.login.failed')->count());
        $this->assertSame(2, $second->context['aggregate']['count']);
        $this->assertSame($firstOccurredAt, $second->context['aggregate']['first_occurred_at']);
        $this->assertSame('2026-08-13T00:01:00+00:00', $second->context['aggregate']['last_occurred_at']);
        $this->assertSame('aggregate-request-2', $second->context['aggregate']['latest_request_id']);
        CarbonImmutable::setTestNow();
    }

    public function test_ordinary_model_mutations_are_rejected_while_retention_prunes_expired_events(): void
    {
        $event = $this->service->record('auth.login.failed', 'warning', 'denied');

        try {
            $event->update(['severity' => 'critical']);
            $this->fail('Security events must reject ordinary updates.');
        } catch (\LogicException $e) {
            $this->assertSame('Security events are append-only.', $e->getMessage());
        }

        try {
            $event->delete();
            $this->fail('Security events must reject ordinary deletion.');
        } catch (\LogicException $e) {
            $this->assertSame('Security events are append-only.', $e->getMessage());
        }

        foreach (['update', 'delete'] as $operation) {
            try {
                $query = ApiSecurityEvent::query()->whereKey($event->getKey());
                $operation === 'update'
                    ? $query->update(['severity' => 'critical'])
                    : $query->delete();
                $this->fail("Security event query {$operation} must be rejected.");
            } catch (\LogicException $e) {
                $this->assertSame('Security events are append-only.', $e->getMessage());
            }
        }

        DB::table('api_security_events')->where('id', $event->getKey())->update(['occurred_at' => now()->subDays(91)]);
        $deleted = app(SecurityEventRetentionService::class)->prune(now()->subDays(90)->toImmutable(), 500);

        $this->assertSame(1, $deleted);
        $this->assertDatabaseMissing('api_security_events', ['id' => $event->getKey()]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Security event prune batch size must be between 1 and 500.');
        app(SecurityEventRetentionService::class)->prune(now()->toImmutable(), 501);
    }

    public function test_aggregate_unique_conflict_reloads_and_increments_the_existing_event(): void
    {
        $injected = false;
        $firstOccurredAt = null;
        $aggregateKey = hash('sha256', implode("\n", [
            'auth.login.failed',
            'warning',
            'denied',
            'api_v2',
            hash_hmac('sha256', '203.0.113.10', 'test-security-correlation-ip-key-123456'),
            hash_hmac('sha256', 'aggregate@example.test', 'test-security-correlation-login-key-123456'),
            hash_hmac('sha256', 'Aggregate test agent', 'test-security-correlation-agent-key-123456'),
            'v1',
        ]));
        DB::connection()->beforeExecuting(function (string $query, array $bindings, $connection) use (&$injected, &$firstOccurredAt, $aggregateKey): void {
            if ($injected || !str_contains(strtolower($query), 'insert into "api_security_events"')) {
                return;
            }

            $injected = true;
            $connection->insert($query, $bindings);
            $context = json_decode((string) DB::table('api_security_events')->where('aggregate_key', $aggregateKey)->value('context'), true);
            $firstOccurredAt = $context['aggregate']['first_occurred_at'];
        });

        $event = $this->service->recordAggregate('auth.login.failed', 'warning', 'denied', [
            'surface' => 'api_v2',
            'request_id' => 'aggregate-request-2',
            'ip' => '203.0.113.10',
            'login_identifier' => 'aggregate@example.test',
            'user_agent' => 'Aggregate test agent',
            'context' => ['reason' => 'invalid_credentials'],
        ]);

        $this->assertTrue($injected);
        $this->assertSame(1, ApiSecurityEvent::where('aggregate_key', $event->aggregate_key)->count());
        $this->assertSame(2, $event->context['aggregate']['count']);
        $this->assertSame($firstOccurredAt, $event->context['aggregate']['first_occurred_at']);
        $this->assertSame('aggregate-request-2', $event->context['aggregate']['latest_request_id']);
    }
}
