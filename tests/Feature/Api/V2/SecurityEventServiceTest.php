<?php

namespace Wncms\Tests\Feature\Api\V2;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
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
            ], null, $this->service->modelConnectionNames(['api_service_token']));

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
            ], null, $this->service->modelConnectionNames(['api_service_token']));

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

    public function test_append_only_event_builders_reject_all_ordinary_mutation_entrypoints(): void
    {
        $operations = [
            'model_touch' => fn (ApiSecurityEvent $event) => $event->touch(),
            'model_touch_quietly' => fn (ApiSecurityEvent $event) => $event->touchQuietly(),
            'eloquent_force_delete' => fn (ApiSecurityEvent $event) => ApiSecurityEvent::query()->whereKey($event->getKey())->forceDelete(),
            'eloquent_touch' => fn (ApiSecurityEvent $event) => ApiSecurityEvent::query()->whereKey($event->getKey())->touch(),
            'eloquent_increment_each' => fn (ApiSecurityEvent $event) => ApiSecurityEvent::query()->whereKey($event->getKey())->incrementEach(['http_status' => 1]),
            'eloquent_decrement_each' => fn (ApiSecurityEvent $event) => ApiSecurityEvent::query()->whereKey($event->getKey())->decrementEach(['http_status' => 1]),
            'eloquent_update_or_insert' => fn (ApiSecurityEvent $event) => ApiSecurityEvent::query()->updateOrInsert(['id' => $event->getKey()], ['severity' => 'critical']),
            'eloquent_upsert' => fn (ApiSecurityEvent $event) => ApiSecurityEvent::query()->upsert([
                ['id' => $event->getKey(), 'severity' => 'critical'],
            ], ['id'], ['severity']),
            'to_base_insert_or_ignore' => fn (ApiSecurityEvent $event) => ApiSecurityEvent::query()->toBase()->insertOrIgnore([]),
            'to_base_insert_or_ignore_returning' => fn (ApiSecurityEvent $event) => ApiSecurityEvent::query()->toBase()->insertOrIgnoreReturning([]),
            'to_base_insert_using' => fn (ApiSecurityEvent $event) => ApiSecurityEvent::query()->toBase()->insertUsing([], DB::query()->selectRaw('1')),
            'to_base_insert_or_ignore_using' => fn (ApiSecurityEvent $event) => ApiSecurityEvent::query()->toBase()->insertOrIgnoreUsing([], DB::query()->selectRaw('1')),
            'to_base_update' => fn (ApiSecurityEvent $event) => ApiSecurityEvent::query()->whereKey($event->getKey())->toBase()->update(['severity' => 'critical']),
            'to_base_update_from' => fn (ApiSecurityEvent $event) => ApiSecurityEvent::query()->whereKey($event->getKey())->toBase()->updateFrom([]),
            'to_base_delete' => fn (ApiSecurityEvent $event) => ApiSecurityEvent::query()->whereKey($event->getKey())->toBase()->delete(),
            'to_base_update_or_insert' => fn (ApiSecurityEvent $event) => ApiSecurityEvent::query()->toBase()->updateOrInsert(['id' => $event->getKey()], ['severity' => 'critical']),
            'to_base_increment' => fn (ApiSecurityEvent $event) => ApiSecurityEvent::query()->whereKey($event->getKey())->toBase()->increment('http_status'),
            'to_base_decrement' => fn (ApiSecurityEvent $event) => ApiSecurityEvent::query()->whereKey($event->getKey())->toBase()->decrement('http_status'),
            'to_base_increment_each' => fn (ApiSecurityEvent $event) => ApiSecurityEvent::query()->whereKey($event->getKey())->toBase()->incrementEach(['http_status' => 1]),
            'to_base_decrement_each' => fn (ApiSecurityEvent $event) => ApiSecurityEvent::query()->whereKey($event->getKey())->toBase()->decrementEach(['http_status' => 1]),
            'to_base_upsert' => fn (ApiSecurityEvent $event) => ApiSecurityEvent::query()->toBase()->upsert([
                ['id' => $event->getKey(), 'severity' => 'critical'],
            ], ['id'], ['severity']),
            'to_base_truncate' => fn (ApiSecurityEvent $event) => ApiSecurityEvent::query()->toBase()->truncate(),
            'get_query_update' => fn (ApiSecurityEvent $event) => ApiSecurityEvent::query()->whereKey($event->getKey())->getQuery()->update(['severity' => 'critical']),
            'get_query_delete' => fn (ApiSecurityEvent $event) => ApiSecurityEvent::query()->whereKey($event->getKey())->getQuery()->delete(),
        ];

        foreach ($operations as $name => $operation) {
            $event = $this->service->record('auth.login.failed', 'warning', 'denied');

            try {
                $operation($event);
                $this->fail("Security event {$name} mutation must be rejected.");
            } catch (\LogicException $e) {
                $this->assertSame('Security events are append-only.', $e->getMessage());
            }

            $this->assertDatabaseHas('api_security_events', ['id' => $event->getKey()]);
        }
    }

    public function test_aggregate_event_remains_atomic_with_an_outer_mutation_transaction(): void
    {
        $beforeCount = ApiSecurityEvent::count();

        try {
            DB::transaction(function (): void {
                DB::table('api_service_tokens')->insert([
                    'token_id' => 'atomic-aggregate-service-token',
                    'token_hash' => hash('sha256', 'atomic-aggregate-secret'),
                    'user_id' => 1,
                    'name' => 'Aggregate transaction test',
                    'ability_template' => 'read_only',
                    'abilities' => json_encode(['read']),
                    'website_ids' => json_encode([1]),
                    'expires_at' => now()->addDay(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->service->recordAggregate('auth.login.failed', 'warning', 'denied', [
                    'surface' => 'api_v2',
                    'request_id' => 'atomic-aggregate-request',
                    'ip' => '203.0.113.11',
                    'login_identifier' => 'atomic@example.test',
                    'user_agent' => 'Atomic aggregate test agent',
                ]);

                throw new \RuntimeException('Roll back aggregate mutation.');
            });

            $this->fail('The outer mutation transaction must roll back the aggregate event.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Roll back aggregate mutation.', $e->getMessage());
        }

        $this->assertSame($beforeCount, ApiSecurityEvent::count());
        $this->assertDatabaseMissing('api_service_tokens', ['token_id' => 'atomic-aggregate-service-token']);
    }

    #[DataProvider('aggregateInsertQueryProvider')]
    public function test_aggregate_insert_query_matcher_is_driver_neutral_and_exact(
        string $query,
        array $bindings,
        string $aggregateKey,
        bool $expected
    ): void {
        $this->assertSame($expected, self::isAggregateInsertQuery($query, $bindings, $aggregateKey));
    }

    /**
     * Provide aggregate insert queries from each supported database grammar.
     *
     * @return array<string, array{string, array<int, string>, string, bool}>
     */
    public static function aggregateInsertQueryProvider(): array
    {
        $aggregateKey = str_repeat('a', 64);

        return [
            'sqlite insert or ignore' => [
                'insert or ignore into "api_security_events" ("event_id", "aggregate_key") values (?, ?)',
                ['event-id', $aggregateKey],
                $aggregateKey,
                true,
            ],
            'mysql insert ignore' => [
                'insert ignore into `api_security_events` (`event_id`, `aggregate_key`) values (?, ?)',
                ['event-id', $aggregateKey],
                $aggregateKey,
                true,
            ],
            'postgresql on conflict do nothing' => [
                "insert into \"api_security_events\" (\"event_id\", \"aggregate_key\")\nvalues (?, ?) on conflict do nothing",
                ['event-id', $aggregateKey],
                $aggregateKey,
                true,
            ],
            'postgresql unquoted identifiers' => [
                'insert into api_security_events (aggregate_key, event_id) values (?, ?) on conflict do nothing',
                [$aggregateKey, 'event-id'],
                $aggregateKey,
                true,
            ],
            'ordinary insert into target table' => [
                'insert into "api_security_events" ("event_id", "aggregate_key") values (?, ?)',
                ['event-id', $aggregateKey],
                $aggregateKey,
                false,
            ],
            'postgresql conflict update' => [
                'insert into "api_security_events" ("event_id", "aggregate_key") values (?, ?) on conflict ("aggregate_key") do update set "event_id" = excluded."event_id"',
                ['event-id', $aggregateKey],
                $aggregateKey,
                false,
            ],
            'insert-or-ignore into another table' => [
                'insert or ignore into "api_service_tokens" ("token_id", "aggregate_key") values (?, ?)',
                ['token-id', $aggregateKey],
                $aggregateKey,
                false,
            ],
            'aggregate table without expected key binding' => [
                'insert or ignore into "api_security_events" ("event_id", "aggregate_key") values (?, ?)',
                ['event-id', str_repeat('b', 64)],
                $aggregateKey,
                false,
            ],
            'expected key bound to a different column' => [
                'insert or ignore into "api_security_events" ("event_id", "aggregate_key") values (?, ?)',
                [$aggregateKey, str_repeat('b', 64)],
                $aggregateKey,
                false,
            ],
            'expected key bound without aggregate key column' => [
                'insert or ignore into "api_security_events" ("event_id", "request_id") values (?, ?)',
                ['event-id', $aggregateKey],
                $aggregateKey,
                false,
            ],
        ];
    }

    public function test_aggregate_insert_conflict_is_ignored_and_updates_the_existing_event(): void
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
        CarbonImmutable::setTestNow('2026-08-13 00:00:00 UTC');
        DB::connection()->beforeExecuting(function (string $query, array $bindings, $connection) use (&$injected, &$firstOccurredAt, $aggregateKey): void {
            if ($injected || !self::isAggregateInsertQuery($query, $bindings, $aggregateKey)) {
                return;
            }

            $injected = true;
            DB::table('api_security_events')->insert([
                'event_id' => (string) Str::uuid(),
                'occurred_at' => '2026-08-13 00:00:00',
                'event_type' => 'auth.login.failed',
                'severity' => 'warning',
                'outcome' => 'denied',
                'surface' => 'api_v2',
                'request_id' => 'aggregate-request-1',
                'ip_hash' => hash_hmac('sha256', '203.0.113.10', 'test-security-correlation-ip-key-123456'),
                'login_identifier_hash' => hash_hmac('sha256', 'aggregate@example.test', 'test-security-correlation-login-key-123456'),
                'user_agent_hash' => hash_hmac('sha256', 'Aggregate test agent', 'test-security-correlation-agent-key-123456'),
                'correlation_key_version' => 'v1',
                'aggregate_key' => $aggregateKey,
                'context' => json_encode([
                    'aggregate' => [
                        'count' => 1,
                        'first_occurred_at' => '2026-08-13T00:00:00+00:00',
                        'last_occurred_at' => '2026-08-13T00:00:00+00:00',
                        'latest_request_id' => 'aggregate-request-1',
                    ],
                ], JSON_THROW_ON_ERROR),
                'created_at' => '2026-08-13 00:00:00',
                'updated_at' => '2026-08-13 00:00:00',
            ]);
            $context = json_decode((string) DB::table('api_security_events')->where('aggregate_key', $aggregateKey)->value('context'), true);
            $firstOccurredAt = $context['aggregate']['first_occurred_at'];
        });

        CarbonImmutable::setTestNow('2026-08-13 00:01:00 UTC');
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
        $this->assertSame('2026-08-13T00:01:00+00:00', $event->context['aggregate']['last_occurred_at']);
        $this->assertSame('aggregate-request-2', $event->context['aggregate']['latest_request_id']);
        CarbonImmutable::setTestNow();
    }

    /**
     * Determine whether a database query is the expected aggregate insert-or-ignore operation.
     *
     * This test seam accepts one row of placeholders so each column maps directly to one binding.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  string  $aggregateKey
     *
     * @return bool
     */
    private static function isAggregateInsertQuery(string $query, array $bindings, string $aggregateKey): bool
    {
        $normalized = strtolower((string) preg_replace('/\s+/', ' ', trim($query)));
        $identifier = '(?:"[^"]+"|`[^`]+`|\[[^\]]+\]|[a-z_][a-z0-9_$]*)';
        $table = '(?:"api_security_events"|`api_security_events`|\[api_security_events\]|api_security_events)';
        $columns = $identifier.'(?:\s*,\s*'.$identifier.')*';
        $placeholders = '\?(?:\s*,\s*\?)*';
        $pattern = '/^insert (?:(?<ignore>or ignore|ignore) )?into '.$table
            .'\s*\(\s*(?<columns>'.$columns.')\s*\)\s*values\s*\(\s*(?<values>'.$placeholders.')\s*\)(?<suffix>.*)$/';

        if (preg_match($pattern, $normalized, $matches) !== 1) {
            return false;
        }

        $suffix = trim($matches['suffix']);
        $usesIgnoreGrammar = $matches['ignore'] !== '' && $suffix === '';
        $usesDoNothingGrammar = $matches['ignore'] === ''
            && preg_match('/^on conflict\s+do nothing(?:\s+returning\b.*)?$/', $suffix) === 1;

        if (!$usesIgnoreGrammar && !$usesDoNothingGrammar) {
            return false;
        }

        preg_match_all('/'.$identifier.'/', $matches['columns'], $columnMatches);
        $columnNames = array_map(self::normalizeSqlIdentifier(...), $columnMatches[0]);
        $aggregateKeyIndex = array_search('aggregate_key', $columnNames, true);
        $placeholderCount = substr_count($matches['values'], '?');

        if ($aggregateKeyIndex === false || count($columnNames) !== $placeholderCount || count($bindings) !== $placeholderCount) {
            return false;
        }

        return $bindings[$aggregateKeyIndex] === $aggregateKey;
    }

    /**
     * Normalize a quoted or unquoted SQL identifier for matcher comparison.
     *
     * @param  string  $identifier
     *
     * @return string
     */
    private static function normalizeSqlIdentifier(string $identifier): string
    {
        return strtolower(trim(trim($identifier), '"`[]'));
    }
}
