<?php

namespace Wncms\Tests\Feature\Api\V2;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Wncms\Auth\Api\V2\SessionService;
use Wncms\Auth\Api\V2\TokenHasher;
use Wncms\Models\ApiServiceToken;
use Wncms\Models\ApiSession;
use Wncms\Models\User;
use Wncms\Models\Website;
use Wncms\Tests\TestCase;

class SessionLifecycleTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private string $password = 'session-password';

    /**
     * Prepare an actor and mandatory event configuration.
     */
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'wncms-api-v2.idempotency.store' => 'array',
            'wncms-api-v2.auth_security.security_event_correlation' => [
                'active_key_version' => 'v1',
                'keys' => ['v1' => [
                    'ip' => 'task6-session-ip-correlation-key-123456',
                    'login_identifier' => 'task6-session-login-correlation-key-123456',
                    'user_agent' => 'task6-session-agent-correlation-key-123456',
                ]],
            ],
            'wncms.auth_security.access_token_lifetime_minutes' => 15,
            'wncms.auth_security.refresh_token_lifetime_days' => 30,
            'wncms.auth_security.refresh_transport' => 'json',
            'wncms.auth_security.permanent_remember_enabled' => false,
            'wncms.auth_security.login_account_attempts' => 50,
            'wncms.auth_security.login_ip_attempts' => 50,
            'wncms.auth_security.login_window_minutes' => 15,
            'wncms.auth_security.login_progressive_delay_seconds' => [0],
        ]);
        Cache::flush();
        Cache::flushLocks();
        uss('enable_api_access', 1);
        uss('api_access_whitelist', '');
        $this->user = User::create([
            'username' => 'session-user-'.uniqid(),
            'email' => 'session-user-'.uniqid().'@example.test',
            'password' => Hash::make($this->password),
            'email_verified_at' => now(),
        ]);
        $this->user->websites()->syncWithoutDetaching([Website::firstOrFail()->id]);
    }

    /**
     * Verify lists expose only opaque public IDs and safe session metadata.
     */
    public function test_session_list_exposes_opaque_ids_without_ip_or_token_fragments(): void
    {
        $login = $this->login('list-device');

        $response = $this->withToken($login['access_token'])->getJson('/api/v2/backend/auth/sessions');

        $response->assertOk()->assertJsonCount(1, 'data');
        $session = $response->json('data.0');
        $this->assertSame($login['session']['id'], $session['id']);
        $this->assertTrue($session['current']);
        $serialized = json_encode($session, JSON_THROW_ON_ERROR);
        foreach (['ip', 'token', 'hash', 'secret', 'csrf'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, strtolower($serialized));
        }
        $this->assertMatchesRegularExpression('/^[0-9A-HJKMNP-TV-Z]{26}$/', $session['id']);
    }

    /**
     * Verify only the owner can revoke an individual session.
     */
    public function test_individual_revoke_is_owner_authorized_and_cross_user_is_opaque_not_found(): void
    {
        $current = $this->login('current');
        $other = $this->login('other');
        $stranger = User::create([
            'username' => 'session-stranger-'.uniqid(),
            'email' => 'session-stranger-'.uniqid().'@example.test',
            'password' => Hash::make('stranger-password'),
            'email_verified_at' => now(),
        ]);
        $stranger->websites()->syncWithoutDetaching([Website::firstOrFail()->id]);
        $strangerLogin = $this->loginAs($stranger, 'stranger-password', 'stranger');

        $this->withToken($strangerLogin['access_token'])
            ->withHeader('Idempotency-Key', 'cross-user-revoke-01')
            ->deleteJson('/api/v2/backend/auth/sessions/'.$other['session']['id'])
            ->assertNotFound()
            ->assertJsonPath('meta.error_code', 'resource.not_found');
        $this->assertNull(ApiSession::query()->where('session_id', $other['session']['id'])->value('revoked_at'));

        $this->withToken($current['access_token'])
            ->withHeader('Idempotency-Key', 'revoke-owner-session-01')
            ->deleteJson('/api/v2/backend/auth/sessions/'.$other['session']['id'])
            ->assertOk();
        $this->assertNotNull(ApiSession::query()->where('session_id', $other['session']['id'])->value('revoked_at'));
    }

    /**
     * Verify JSON logout is idempotent for a syntactically valid refresh credential.
     */
    public function test_logout_is_idempotent_and_revokes_its_interactive_family(): void
    {
        $login = $this->login('logout-device');

        $this->postJson('/api/v2/backend/auth/logout', ['refresh_token' => $login['refresh_token']])->assertOk();
        $this->postJson('/api/v2/backend/auth/logout', ['refresh_token' => $login['refresh_token']])->assertOk();

        $this->withToken($login['access_token'])
            ->getJson('/api/v2/backend/auth/me')
            ->assertUnauthorized();
        $this->assertDatabaseHas('api_security_events', ['event_type' => 'auth.logout.succeeded']);
    }

    /**
     * Verify logout-all and revoke-all affect interactive sessions but never service tokens.
     */
    public function test_logout_all_excludes_service_tokens_and_revoke_all_can_except_one_session(): void
    {
        $first = $this->login('first');
        $second = $this->login('second');
        $material = app(TokenHasher::class)->issue('wncms_st');
        $serviceToken = ApiServiceToken::create([
            'token_id' => $material['public_id'],
            'token_hash' => $material['hash'],
            'user_id' => $this->user->id,
            'name' => 'unrelated service token',
            'ability_template' => 'read_only',
            'abilities' => ['links.read'],
            'website_ids' => [Website::firstOrFail()->id],
            'expires_at' => now()->addDay(),
        ]);

        $firstSession = ApiSession::query()->where('session_id', $first['session']['id'])->firstOrFail();
        $count = app(SessionService::class)->revokeAll($this->user, $firstSession->id);
        $this->assertSame(1, $count);
        $this->assertNull($firstSession->fresh()->revoked_at);
        $this->assertNotNull(ApiSession::query()->where('session_id', $second['session']['id'])->value('revoked_at'));
        $this->assertNull($serviceToken->fresh()->revoked_at);

        $this->withToken($first['access_token'])
            ->withHeader('Idempotency-Key', 'logout-all-session-01')
            ->postJson('/api/v2/backend/auth/logout-all')
            ->assertOk();
        $this->assertNull($firstSession->fresh()->revoked_at);
        $this->assertNull($serviceToken->fresh()->revoked_at);
    }

    /**
     * Verify logout-all preserves its requesting session so the same key can replay safely.
     */
    public function test_logout_all_replays_the_same_key_and_revokes_every_other_interactive_session(): void
    {
        $current = $this->login('logout-all-current');
        $other = $this->login('logout-all-other');
        $key = 'logout-all-replay-key-01';

        $first = $this->withToken($current['access_token'])
            ->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v2/backend/auth/logout-all');
        $first->assertOk()->assertJsonPath('data.revoked_sessions', 1);

        $this->withToken($current['access_token'])
            ->withHeader('Idempotency-Key', $key)
            ->postJson('http://changed-session-host.test/api/v2/backend/auth/logout-all')
            ->assertOk()
            ->assertHeader('Idempotency-Replayed', 'true')
            ->assertJsonPath('data.revoked_sessions', 1);

        $this->assertNull(ApiSession::query()->where('session_id', $current['session']['id'])->value('revoked_at'));
        $this->assertNotNull(ApiSession::query()->where('session_id', $other['session']['id'])->value('revoked_at'));
        $this->assertSame(1, DB::table('api_security_events')->where('event_type', 'auth.logout_all.succeeded')->count());
    }

    /**
     * Verify successful access updates activity metadata at most once per five minutes.
     */
    public function test_last_activity_updates_are_debounced_for_five_minutes(): void
    {
        CarbonImmutable::setTestNow('2026-08-13 10:00:00 UTC');
        $login = $this->login('activity');
        $session = ApiSession::query()->where('session_id', $login['session']['id'])->firstOrFail();

        CarbonImmutable::setTestNow('2026-08-13 10:01:00 UTC');
        $this->withToken($login['access_token'])->getJson('/api/v2/backend/auth/me')->assertOk();
        $firstActivity = $session->fresh()->last_activity_at;

        CarbonImmutable::setTestNow('2026-08-13 10:04:59 UTC');
        $this->withToken($login['access_token'])->getJson('/api/v2/backend/auth/me')->assertOk();
        $this->assertTrue($firstActivity->equalTo($session->fresh()->last_activity_at));

        CarbonImmutable::setTestNow('2026-08-13 10:06:01 UTC');
        $this->withToken($login['access_token'])->getJson('/api/v2/backend/auth/me')->assertOk();
        $this->assertTrue($session->fresh()->last_activity_at->equalTo(CarbonImmutable::now('UTC')));
        CarbonImmutable::setTestNow();
    }

    /**
     * Verify session mutations require a valid idempotency key before changing state.
     */
    public function test_session_mutations_reject_missing_and_invalid_idempotency_keys_without_revocation(): void
    {
        $current = $this->login('idempotency-current');
        $other = $this->login('idempotency-other');

        $this->withToken($current['access_token'])
            ->deleteJson('/api/v2/backend/auth/sessions/'.$other['session']['id'])
            ->assertBadRequest()
            ->assertJsonPath('meta.error_code', 'idempotency.key_missing');
        $this->withToken($current['access_token'])
            ->withHeader('Idempotency-Key', 'short')
            ->postJson('/api/v2/backend/auth/logout-all')
            ->assertBadRequest()
            ->assertJsonPath('meta.error_code', 'idempotency.key_invalid');

        $this->assertNull(ApiSession::query()->where('session_id', $current['session']['id'])->value('revoked_at'));
        $this->assertNull(ApiSession::query()->where('session_id', $other['session']['id'])->value('revoked_at'));
    }

    /**
     * Verify both session mutations publish stable operation and global-scope identities.
     */
    public function test_session_mutation_routes_publish_idempotency_contracts(): void
    {
        foreach ([
            'api.v2.backend.auth.logout_all' => 'backend.authentication.logout_all',
            'api.v2.backend.auth.sessions.destroy' => 'backend.authentication.sessions.destroy',
        ] as $routeName => $operationId) {
            $route = Route::getRoutes()->getByName($routeName);
            $this->assertNotNull($route);
            $this->assertSame($operationId, $route->defaults['api_operation_id'] ?? null);
            $this->assertSame('global:interactive-sessions', $route->defaults['api_website_identity'] ?? null);
            $this->assertContains('api_v2_idempotency', $route->gatherMiddleware());
        }
    }

    /**
     * Verify individual revoke replays once and rejects key reuse for another target or payload.
     */
    public function test_individual_revoke_replays_same_key_and_conflicts_on_target_or_payload_change(): void
    {
        $current = $this->login('replay-current');
        $first = $this->login('replay-first');
        $second = $this->login('replay-second');
        $key = 'session-revoke-replay-01';

        $this->withToken($current['access_token'])
            ->withHeader('Idempotency-Key', $key)
            ->deleteJson('/api/v2/backend/auth/sessions/'.$first['session']['id'], ['reason' => 'one'])
            ->assertOk();
        $this->withToken($current['access_token'])
            ->withHeader('Idempotency-Key', $key)
            ->deleteJson('/api/v2/backend/auth/sessions/'.$first['session']['id'], ['reason' => 'one'])
            ->assertOk()
            ->assertHeader('Idempotency-Replayed', 'true');
        $this->withToken($current['access_token'])
            ->withHeader('Idempotency-Key', $key)
            ->deleteJson('/api/v2/backend/auth/sessions/'.$second['session']['id'], ['reason' => 'one'])
            ->assertConflict()
            ->assertJsonPath('meta.error_code', 'idempotency.key_conflict');

        $payloadKey = 'session-revoke-payload-01';
        $this->withToken($current['access_token'])
            ->withHeader('Idempotency-Key', $payloadKey)
            ->deleteJson('/api/v2/backend/auth/sessions/'.$second['session']['id'], ['reason' => 'one'])
            ->assertOk();
        $this->withToken($current['access_token'])
            ->withHeader('Idempotency-Key', $payloadKey)
            ->deleteJson('/api/v2/backend/auth/sessions/'.$second['session']['id'], ['reason' => 'two'])
            ->assertConflict()
            ->assertJsonPath('meta.error_code', 'idempotency.key_conflict');
    }

    /**
     * Verify missing mandatory correlation keys roll back logout-all and individual revoke.
     */
    public function test_session_mutations_map_missing_audit_configuration_to_503_and_roll_back(): void
    {
        $current = $this->login('audit-config-current');
        $other = $this->login('audit-config-other');
        config(['wncms-api-v2.auth_security.security_event_correlation' => [
            'active_key_version' => null,
            'keys' => [],
        ]]);

        $this->withToken($current['access_token'])
            ->withHeader('Idempotency-Key', 'audit-config-revoke-01')
            ->deleteJson('/api/v2/backend/auth/sessions/'.$other['session']['id'])
            ->assertStatus(503)
            ->assertJsonPath('meta.error_code', 'security.audit_unavailable');
        $this->withToken($current['access_token'])
            ->withHeader('Idempotency-Key', 'audit-config-logout-01')
            ->postJson('/api/v2/backend/auth/logout-all')
            ->assertStatus(503)
            ->assertJsonPath('meta.error_code', 'security.audit_unavailable');

        $this->assertInteractivePairActive($current);
        $this->assertInteractivePairActive($other);
    }

    /**
     * Verify a real event insert failure rolls back logout-all and individual revoke.
     */
    public function test_session_mutations_map_event_persistence_failure_to_503_and_roll_back(): void
    {
        $current = $this->login('audit-insert-current');
        $other = $this->login('audit-insert-other');
        DB::unprepared("CREATE TEMP TRIGGER task6_security_event_insert_failure BEFORE INSERT ON api_security_events BEGIN SELECT RAISE(FAIL, 'injected event insert failure'); END");

        try {
            $this->withToken($current['access_token'])
                ->withHeader('Idempotency-Key', 'audit-insert-revoke-01')
                ->deleteJson('/api/v2/backend/auth/sessions/'.$other['session']['id'])
                ->assertStatus(503)
                ->assertJsonPath('meta.error_code', 'security.audit_unavailable');
            $this->withToken($current['access_token'])
                ->withHeader('Idempotency-Key', 'audit-insert-logout-01')
                ->postJson('/api/v2/backend/auth/logout-all')
                ->assertStatus(503)
                ->assertJsonPath('meta.error_code', 'security.audit_unavailable');
        } finally {
            DB::unprepared('DROP TRIGGER IF EXISTS task6_security_event_insert_failure');
        }

        $this->assertInteractivePairActive($current);
        $this->assertInteractivePairActive($other);
    }

    /**
     * Assert a login response still owns an active session/access/refresh triple.
     *
     * @param  array<string, mixed>  $login
     */
    private function assertInteractivePairActive(array $login): void
    {
        $session = ApiSession::query()->where('session_id', $login['session']['id'])->firstOrFail();
        $this->assertNull($session->revoked_at);
        $this->assertDatabaseHas('api_access_tokens', ['session_id' => $session->id, 'revoked_at' => null]);
        $this->assertDatabaseHas('api_refresh_tokens', ['session_id' => $session->id, 'revoked_at' => null]);
    }

    /**
     * Login the primary actor and return response data.
     *
     * @return array<string, mixed>
     */
    private function login(string $deviceName): array
    {
        return $this->loginAs($this->user, $this->password, $deviceName);
    }

    /**
     * Login one supplied actor and return response data.
     *
     * @return array<string, mixed>
     */
    private function loginAs(User $user, string $password, string $deviceName): array
    {
        auth()->forgetGuards();
        $response = $this->postJson('/api/v2/backend/auth/login', [
            'email' => $user->email,
            'password' => $password,
            'device_name' => $deviceName,
        ]);
        $response->assertOk()->assertJsonStructure(['data' => [
            'access_token', 'refresh_token', 'session',
        ]]);

        return $response->json('data');
    }
}
