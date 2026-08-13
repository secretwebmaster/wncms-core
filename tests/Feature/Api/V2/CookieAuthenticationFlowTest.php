<?php

namespace Wncms\Tests\Feature\Api\V2;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Cookie;
use Wncms\Auth\Api\V2\AuthSecurityConfig;
use Wncms\Events\ApiSecurityEventRecorded;
use Wncms\Models\ApiRefreshToken;
use Wncms\Models\ApiSession;
use Wncms\Models\User;
use Wncms\Models\Website;
use Wncms\Services\Security\SecurityDenialRecorder;
use Wncms\Tests\TestCase;

class CookieAuthenticationFlowTest extends TestCase
{
    use DatabaseTransactions;

    private const REFRESH_COOKIE = '__Secure-wncms_refresh';

    private const CSRF_COOKIE = 'wncms_refresh_csrf';

    private User $user;

    private string $password = 'cookie-auth-password';

    /**
     * Prepare one interactive actor and secure Cookie settings.
     */
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'https://api.example.test',
            'session.secure' => true,
            'cors.paths' => ['api/v2/backend/auth/*'],
            'cors.allowed_origins' => ['https://admin.example.test'],
            'cors.supports_credentials' => true,
            'wncms-api-v2.idempotency.store' => 'array',
            'wncms-api-v2.auth_security.security_event_correlation' => [
                'active_key_version' => 'v1',
                'keys' => ['v1' => [
                    'ip' => 'task7-cookie-ip-correlation-key-1234567890',
                    'login_identifier' => 'task7-cookie-login-correlation-key-1234567890',
                    'user_agent' => 'task7-cookie-agent-correlation-key-1234567890',
                ]],
            ],
        ]);
        $this->applySettings([
            'api_refresh_transport' => 'cookie',
            'api_refresh_cookie_allowed_origins' => 'https://admin.example.test',
            'api_refresh_cookie_domain' => '',
            'api_refresh_cookie_same_site' => 'lax',
            'api_refresh_cookie_referer_fallback' => false,
            'api_login_account_attempts' => 50,
            'api_login_ip_attempts' => 50,
            'api_login_progressive_delay_seconds' => '0',
        ]);
        uss('enable_api_access', 1);
        uss('api_access_whitelist', '');

        $this->user = User::create([
            'username' => 'cookie-auth-'.uniqid(),
            'email' => 'cookie-auth-'.uniqid().'@example.test',
            'password' => Hash::make($this->password),
            'email_verified_at' => now(),
        ]);
        $this->user->websites()->syncWithoutDetaching([Website::firstOrFail()->id]);
    }

    /**
     * Verify Cookie login issues exact cookies and binds only the CSRF hash to the session.
     */
    public function test_cookie_login_issues_exact_secure_cookies_without_refresh_plaintext(): void
    {
        $response = $this->loginCookie();

        $response->assertOk()
            ->assertJsonMissingPath('data.refresh_token')
            ->assertCookie(self::REFRESH_COOKIE, null, false)
            ->assertCookie(self::CSRF_COOKIE, null, false);
        $refresh = $response->getCookie(self::REFRESH_COOKIE, false);
        $csrf = $response->getCookie(self::CSRF_COOKIE, false);

        $this->assertCookiePolicy($refresh, true, null, 'lax');
        $this->assertCookiePolicy($csrf, false, null, 'lax');
        $this->assertStringStartsWith('wncms_rt_', (string) $refresh->getValue());
        $this->assertNotSame('', (string) $csrf->getValue());
        $this->assertStringNotContainsString((string) $refresh->getValue(), (string) $response->getContent());
        $session = ApiSession::query()->where('user_id', $this->user->id)->latest('id')->firstOrFail();
        $this->assertSame('cookie', $session->refresh_transport);
        $this->assertSame(hash('sha256', (string) $csrf->getValue()), $session->csrf_hash);
        $this->assertNotSame((string) $csrf->getValue(), $session->csrf_hash);
        $refreshModel = ApiRefreshToken::query()->where('session_id', $session->getKey())->firstOrFail();
        $this->assertSame(hash('sha256', (string) $csrf->getValue()), $refreshModel->csrf_hash);
    }

    /**
     * Verify a validated shared domain and every supported SameSite policy are emitted exactly.
     */
    #[DataProvider('sameSiteMatrix')]
    public function test_cookie_attributes_follow_the_validated_domain_and_same_site_matrix(string $sameSite): void
    {
        $this->applySettings([
            'api_refresh_cookie_domain' => 'example.test',
            'api_refresh_cookie_same_site' => $sameSite,
        ]);

        $response = $this->loginCookie(['device_name' => 'cookie-'.$sameSite]);

        $this->assertCookiePolicy($response->getCookie(self::REFRESH_COOKIE, false), true, 'example.test', $sameSite);
        $this->assertCookiePolicy($response->getCookie(self::CSRF_COOKIE, false), false, 'example.test', $sameSite);
    }

    /**
     * Provide each validated browser SameSite policy.
     *
     * @return array<string, array{0: string}>
     */
    public static function sameSiteMatrix(): array
    {
        return [
            'Strict' => ['strict'],
            'Lax' => ['lax'],
            'None' => ['none'],
        ];
    }

    /**
     * Verify login fails closed for missing, null, non-exact, and unapproved Origins.
     */
    public function test_cookie_login_enforces_exact_origin_and_explicit_referer_fallback(): void
    {
        foreach ([
            null,
            'null',
            'http://admin.example.test',
            'https://admin.example.test:8443',
            'https://admin.example.test.attacker.test',
        ] as $origin) {
            $request = $origin === null ? $this : $this->withHeader('Origin', $origin);
            $request->postJson('/api/v2/backend/auth/login', $this->loginPayload())
                ->assertForbidden()
                ->assertJsonPath('meta.error_code', 'authentication.origin_denied')
                ->assertCookieMissing(self::REFRESH_COOKIE)
                ->assertCookieMissing(self::CSRF_COOKIE);
        }

        $this->withHeader('Origin', 'https://admin.example.test:443')
            ->postJson('/api/v2/backend/auth/login', $this->loginPayload(['device_name' => 'exact-origin']))
            ->assertOk();

        $this->withoutHeader('Origin')
            ->withHeader('Referer', 'https://admin.example.test/login')
            ->postJson('/api/v2/backend/auth/login', $this->loginPayload(['device_name' => 'referer-disabled']))
            ->assertForbidden()
            ->assertJsonPath('meta.error_code', 'authentication.origin_denied');

        $this->applySettings(['api_refresh_cookie_referer_fallback' => true]);
        $this->withHeader('Referer', 'https://admin.example.test/login')
            ->postJson('/api/v2/backend/auth/login', $this->loginPayload(['device_name' => 'referer-enabled']))
            ->assertOk();
    }

    /**
     * Verify actual and preflight Cookie requests expose exact credentialed CORS only.
     */
    public function test_cookie_auth_has_exact_credentialed_cors_for_actual_and_preflight_requests(): void
    {
        $login = $this->loginCookie(['device_name' => 'cors-actual'])->assertOk();
        $login->assertHeader('Access-Control-Allow-Origin', 'https://admin.example.test')
            ->assertHeader('Access-Control-Allow-Credentials', 'true');
        $this->assertStringContainsString('Origin', (string) $login->headers->get('Vary'));

        $this->withHeaders([
            'Origin' => 'https://admin.example.test',
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'Content-Type, X-WNCMS-CSRF',
        ])->optionsJson('/api/v2/backend/auth/refresh')
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', 'https://admin.example.test')
            ->assertHeader('Access-Control-Allow-Credentials', 'true')
            ->assertHeader('Access-Control-Allow-Methods', 'GET, POST, DELETE, OPTIONS');

        $denied = $this->withHeaders([
            'Origin' => 'https://attacker.example.test',
            'Access-Control-Request-Method' => 'POST',
        ])->optionsJson('/api/v2/backend/auth/refresh');
        $denied->assertForbidden()->assertHeaderMissing('Access-Control-Allow-Origin');

        config([
            'cors.allowed_origins' => ['*', 'https://admin.example.test'],
            'cors.supports_credentials' => true,
        ]);
        $actualDenied = $this->withHeader('Origin', 'https://attacker.example.test')
            ->postJson('/api/v2/backend/auth/login', $this->loginPayload(['device_name' => 'cors-denied-actual']));
        $actualDenied->assertForbidden()
            ->assertHeaderMissing('Access-Control-Allow-Origin')
            ->assertHeaderMissing('Access-Control-Allow-Credentials');
    }

    /**
     * Verify Origin and CSRF denials persist allowlisted security events.
     */
    public function test_cookie_origin_and_csrf_denials_are_audited(): void
    {
        $this->withoutHeader('Origin')
            ->postJson('/api/v2/backend/auth/login', $this->loginPayload(['device_name' => 'origin-audit']))
            ->assertForbidden();

        $login = $this->loginCookie(['device_name' => 'csrf-audit']);
        $refresh = (string) $login->getCookie(self::REFRESH_COOKIE, false)->getValue();
        $this->cookieExchange('/api/v2/backend/auth/refresh', $refresh, 'invalid-proof', 'invalid-proof')
            ->assertForbidden();

        $this->assertDatabaseHas('api_security_events', [
            'event_type' => 'security.origin.denied',
            'error_code' => 'authentication.origin_denied',
            'http_status' => 403,
        ]);
        $this->assertDatabaseHas('api_security_events', [
            'event_type' => 'security.csrf.denied',
            'error_code' => 'authentication.csrf_failed',
            'http_status' => 403,
        ]);
    }

    /**
     * Verify denial audit failure falls back to a redacted structured warning.
     */
    public function test_cookie_denial_audit_failure_keeps_403_and_logs_no_origin_plaintext(): void
    {
        RateLimiter::clear(SecurityDenialRecorder::FALLBACK_GLOBAL_KEY);
        Log::spy();
        DB::unprepared("CREATE TEMP TRIGGER task7_denial_audit_failure BEFORE INSERT ON api_security_events BEGIN SELECT RAISE(FAIL, 'injected denial audit failure'); END");

        try {
            $this->withHeader('Origin', 'https://CANARY-ORIGIN.attacker.test')
                ->postJson('/api/v2/backend/auth/login', $this->loginPayload(['device_name' => 'denial-fallback']))
                ->assertForbidden()
                ->assertJsonPath('meta.error_code', 'authentication.origin_denied');
        } finally {
            DB::unprepared('DROP TRIGGER IF EXISTS task7_denial_audit_failure');
        }

        Log::shouldHaveReceived('warning')->withArgs(static function (string $message, array $context): bool {
            return $message === 'WNCMS Cookie security denial event could not be persisted.'
                && ($context['event_type'] ?? null) === 'security.origin.denied'
                && ($context['error_code'] ?? null) === 'authentication.origin_denied'
                && ! str_contains(json_encode($context, JSON_THROW_ON_ERROR), 'CANARY-ORIGIN');
        })->once();
    }

    /**
     * Verify missing correlation keys preserve a redacted Origin denial response.
     */
    public function test_cookie_origin_denial_with_missing_correlation_keys_stays_redacted_403(): void
    {
        config(['wncms-api-v2.auth_security.security_event_correlation' => []]);
        RateLimiter::clear(SecurityDenialRecorder::FALLBACK_GLOBAL_KEY);
        Log::spy();

        $this->withHeader('Origin', 'https://CANARY-MISSING-KEY.attacker.test')
            ->postJson('/api/v2/backend/auth/login', $this->loginPayload(['device_name' => 'missing-correlation']))
            ->assertForbidden()
            ->assertJsonPath('meta.error_code', 'authentication.origin_denied')
            ->assertHeaderMissing('Access-Control-Allow-Origin')
            ->assertHeaderMissing('Access-Control-Allow-Credentials');

        Log::shouldHaveReceived('warning')->withArgs(static function (string $message, array $context): bool {
            return $message === 'WNCMS Cookie security denial event could not be persisted.'
                && ! str_contains(json_encode($context, JSON_THROW_ON_ERROR), 'CANARY-MISSING-KEY');
        })->once();
    }

    /**
     * Verify refresh rotates both credentials and updates the session-bound CSRF hash.
     */
    public function test_cookie_refresh_requires_double_submit_csrf_and_rotates_both_cookies(): void
    {
        $login = $this->loginCookie();
        $oldRefresh = (string) $login->getCookie(self::REFRESH_COOKIE, false)->getValue();
        $oldCsrf = (string) $login->getCookie(self::CSRF_COOKIE, false)->getValue();

        $response = $this->cookieExchange('/api/v2/backend/auth/refresh', $oldRefresh, $oldCsrf, $oldCsrf);

        $response->assertOk()->assertJsonMissingPath('data.refresh_token');
        $newRefresh = (string) $response->getCookie(self::REFRESH_COOKIE, false)->getValue();
        $newCsrf = (string) $response->getCookie(self::CSRF_COOKIE, false)->getValue();
        $this->assertNotSame($oldRefresh, $newRefresh);
        $this->assertNotSame($oldCsrf, $newCsrf);
        $this->assertStringNotContainsString($newRefresh, (string) $response->getContent());
        $this->assertSame(hash('sha256', $newCsrf), ApiSession::query()->where('user_id', $this->user->id)->latest('id')->value('csrf_hash'));
    }

    /**
     * Verify a consumed refresh keeps its own proof long enough to trigger scoped reuse revocation.
     */
    public function test_cookie_refresh_replay_requires_its_original_proof_and_revokes_only_that_session(): void
    {
        $first = $this->loginCookie(['device_name' => 'replayed-cookie']);
        $firstRefresh = (string) $first->getCookie(self::REFRESH_COOKIE, false)->getValue();
        $firstCsrf = (string) $first->getCookie(self::CSRF_COOKIE, false)->getValue();
        $firstSessionId = (string) $first->json('data.session.id');
        $second = $this->loginCookie(['device_name' => 'independent-cookie']);
        $secondRefresh = (string) $second->getCookie(self::REFRESH_COOKIE, false)->getValue();
        $secondCsrf = (string) $second->getCookie(self::CSRF_COOKIE, false)->getValue();
        $secondSessionId = (string) $second->json('data.session.id');

        $this->cookieExchange('/api/v2/backend/auth/refresh', $firstRefresh, $firstCsrf, $firstCsrf)->assertOk();
        $this->cookieExchange('/api/v2/backend/auth/refresh', $firstRefresh, 'random-old-proof', 'random-old-proof')
            ->assertForbidden()
            ->assertJsonPath('meta.error_code', 'authentication.csrf_failed');
        $this->cookieExchange('/api/v2/backend/auth/refresh', $firstRefresh, $firstCsrf, $firstCsrf)
            ->assertUnauthorized()
            ->assertJsonPath('meta.error_code', 'authentication.refresh_reuse_detected');

        $this->assertNotNull(ApiSession::query()->where('session_id', $firstSessionId)->value('revoked_at'));
        $this->assertNull(ApiSession::query()->where('session_id', $secondSessionId)->value('revoked_at'));
        $this->cookieExchange('/api/v2/backend/auth/refresh', $secondRefresh, $secondCsrf, $secondCsrf)->assertOk();
    }

    /**
     * Verify a CSRF binding failure rolls back refresh consumption and replacement issuance.
     */
    public function test_cookie_refresh_rotation_and_csrf_binding_are_atomic(): void
    {
        $login = $this->loginCookie();
        $refresh = (string) $login->getCookie(self::REFRESH_COOKIE, false)->getValue();
        $csrf = (string) $login->getCookie(self::CSRF_COOKIE, false)->getValue();
        $tokenId = explode('.', substr($refresh, strlen('wncms_rt_')), 2)[0];
        Event::fake([ApiSecurityEventRecorded::class]);
        Log::spy();
        DB::unprepared("CREATE TEMP TRIGGER task7_csrf_update_failure BEFORE UPDATE OF csrf_hash ON api_refresh_tokens BEGIN SELECT RAISE(FAIL, 'injected csrf update failure'); END");

        try {
            $this->cookieExchange('/api/v2/backend/auth/refresh', $refresh, $csrf, $csrf)
                ->assertStatus(503)
                ->assertJsonPath('meta.error_code', 'security.audit_unavailable')
                ->assertJsonMissingPath('data.refresh_token');
        } finally {
            DB::unprepared('DROP TRIGGER IF EXISTS task7_csrf_update_failure');
        }

        $this->assertNull(DB::table('api_refresh_tokens')->where('token_id', $tokenId)->value('consumed_at'));
        $this->assertSame(1, DB::table('api_refresh_tokens')->where('session_id', ApiSession::query()->where('user_id', $this->user->id)->latest('id')->value('id'))->count());
        Event::assertNotDispatched(ApiSecurityEventRecorded::class, static fn (ApiSecurityEventRecorded $event): bool => $event->event->event_type === 'auth.refresh.succeeded');
        Log::shouldNotHaveReceived('info');
        $this->cookieExchange('/api/v2/backend/auth/refresh', $refresh, $csrf, $csrf)->assertOk();
    }

    /**
     * Verify missing, mismatched, and server-unbound CSRF submissions fail stably.
     */
    public function test_cookie_refresh_rejects_missing_mismatched_and_server_unbound_csrf(): void
    {
        $login = $this->loginCookie();
        $refresh = (string) $login->getCookie(self::REFRESH_COOKIE, false)->getValue();
        $csrf = (string) $login->getCookie(self::CSRF_COOKIE, false)->getValue();

        foreach ([
            'missing header' => [$csrf, ''],
            'missing cookie' => ['', $csrf],
            'double submit mismatch' => [$csrf, 'different-token'],
            'server binding mismatch' => ['different-token', 'different-token'],
        ] as [$cookie, $header]) {
            $this->cookieExchange('/api/v2/backend/auth/refresh', $refresh, $cookie, $header)
                ->assertForbidden()
                ->assertJsonPath('meta.error_code', 'authentication.csrf_failed')
                ->assertJsonMissingPath('data.refresh_token');
        }
    }

    /**
     * Verify JSON and Cookie transports never consume credentials from the other channel.
     */
    public function test_refresh_transport_mismatch_is_rejected_without_refresh_plaintext(): void
    {
        $cookieLogin = $this->loginCookie();
        $cookieRefresh = (string) $cookieLogin->getCookie(self::REFRESH_COOKIE, false)->getValue();
        $csrf = (string) $cookieLogin->getCookie(self::CSRF_COOKIE, false)->getValue();

        $this->withHeader('Origin', 'https://admin.example.test')
            ->withUnencryptedCookies([self::REFRESH_COOKIE => $cookieRefresh, self::CSRF_COOKIE => $csrf])
            ->withCredentials()
            ->withHeader('X-WNCMS-CSRF', $csrf)
            ->postJson('/api/v2/backend/auth/refresh', ['refresh_token' => $cookieRefresh])
            ->assertBadRequest()
            ->assertJsonPath('meta.error_code', 'authentication.refresh_transport_mismatch')
            ->assertJsonMissingPath('data.refresh_token');

        $this->applySettings(['api_refresh_transport' => 'json']);
        $this->withUnencryptedCookie(self::REFRESH_COOKIE, $cookieRefresh)
            ->withCredentials()
            ->postJson('/api/v2/backend/auth/refresh')
            ->assertBadRequest()
            ->assertJsonPath('meta.error_code', 'authentication.refresh_transport_mismatch')
            ->assertJsonMissingPath('data.refresh_token');
    }

    /**
     * Verify JSON mode sets no refresh cookies and retains the body-token contract.
     */
    public function test_json_mode_neither_sets_nor_requires_cookie_credentials(): void
    {
        $this->applySettings(['api_refresh_transport' => 'json']);

        $login = $this->postJson('/api/v2/backend/auth/login', $this->loginPayload());

        $login->assertOk()
            ->assertJsonStructure(['data' => ['refresh_token']])
            ->assertCookieMissing(self::REFRESH_COOKIE)
            ->assertCookieMissing(self::CSRF_COOKIE);
        $this->postJson('/api/v2/backend/auth/refresh', [
            'refresh_token' => $login->json('data.refresh_token'),
        ])->assertOk()
            ->assertJsonStructure(['data' => ['refresh_token']])
            ->assertCookieMissing(self::REFRESH_COOKIE)
            ->assertCookieMissing(self::CSRF_COOKIE);
    }

    /**
     * Verify one process observes a JSON-to-Cookie change through the real refresh service.
     */
    public function test_same_process_json_then_cookie_uses_fresh_transport_configuration(): void
    {
        $this->applySettings(['api_refresh_transport' => 'json']);
        $json = $this->postJson('/api/v2/backend/auth/login', $this->loginPayload(['device_name' => 'json-first']));
        $this->postJson('/api/v2/backend/auth/refresh', [
            'refresh_token' => $json->json('data.refresh_token'),
        ])->assertOk();

        $this->applySettings(['api_refresh_transport' => 'cookie']);
        $cookie = $this->loginCookie(['device_name' => 'cookie-second']);
        $refresh = (string) $cookie->getCookie(self::REFRESH_COOKIE, false)->getValue();
        $csrf = (string) $cookie->getCookie(self::CSRF_COOKIE, false)->getValue();
        $this->cookieExchange('/api/v2/backend/auth/refresh', $refresh, $csrf, $csrf)->assertOk();
    }

    /**
     * Verify one process observes a Cookie-to-JSON change through the real refresh service.
     */
    public function test_same_process_cookie_then_json_uses_fresh_transport_configuration(): void
    {
        $cookie = $this->loginCookie(['device_name' => 'cookie-first']);
        $refresh = (string) $cookie->getCookie(self::REFRESH_COOKIE, false)->getValue();
        $csrf = (string) $cookie->getCookie(self::CSRF_COOKIE, false)->getValue();
        $this->cookieExchange('/api/v2/backend/auth/refresh', $refresh, $csrf, $csrf)->assertOk();

        $this->applySettings(['api_refresh_transport' => 'json']);
        $this->withCredentials = false;
        $this->unencryptedCookies = [];
        $this->defaultCookies = [];
        $json = $this->postJson('/api/v2/backend/auth/login', $this->loginPayload(['device_name' => 'json-second']));
        $this->postJson('/api/v2/backend/auth/refresh', [
            'refresh_token' => $json->json('data.refresh_token'),
        ])->assertOk();
    }

    /**
     * Verify Cookie logout is idempotent and always clears both browser credentials.
     */
    public function test_cookie_logout_clears_both_cookies_without_returning_refresh_plaintext(): void
    {
        $login = $this->loginCookie();
        $refresh = (string) $login->getCookie(self::REFRESH_COOKIE, false)->getValue();
        $csrf = (string) $login->getCookie(self::CSRF_COOKIE, false)->getValue();

        $response = $this->cookieExchange('/api/v2/backend/auth/logout', $refresh, $csrf, $csrf);

        $response->assertOk()
            ->assertCookieExpired(self::REFRESH_COOKIE)
            ->assertCookieExpired(self::CSRF_COOKIE)
            ->assertJsonMissingPath('data.refresh_token');
        $this->assertStringNotContainsString($refresh, (string) $response->getContent());
    }

    /**
     * Verify permanent credentials receive a bounded persistent browser-cookie horizon.
     */
    public function test_permanent_cookie_credentials_are_persistent_but_bounded(): void
    {
        $this->applySettings(['api_permanent_remember_enabled' => true]);

        $response = $this->loginCookie([
            'device_name' => 'permanent-cookie',
            'remember_me' => true,
        ])->assertOk();
        $refresh = $response->getCookie(self::REFRESH_COOKIE, false);
        $csrf = $response->getCookie(self::CSRF_COOKIE, false);

        $this->assertNull($response->json('data.refresh_expires_at'));
        $this->assertGreaterThan(now()->addDays(365)->getTimestamp(), $refresh->getExpiresTime());
        $this->assertLessThanOrEqual(now()->addDays(400)->getTimestamp(), $refresh->getExpiresTime());
        $this->assertSame($refresh->getExpiresTime(), $csrf->getExpiresTime());
    }

    /**
     * Verify repeated logout remains successful and clears exact Cookie attributes every time.
     */
    public function test_repeated_cookie_logout_is_idempotent_and_preserves_clear_attributes(): void
    {
        $login = $this->loginCookie(['device_name' => 'repeat-logout']);
        $refresh = (string) $login->getCookie(self::REFRESH_COOKIE, false)->getValue();
        $csrf = (string) $login->getCookie(self::CSRF_COOKIE, false)->getValue();

        foreach ([1, 2] as $attempt) {
            $response = $this->cookieExchange('/api/v2/backend/auth/logout', $refresh, $csrf, $csrf);
            $response->assertOk()->assertJsonMissingPath('data.refresh_token');
            $refreshClear = $response->getCookie(self::REFRESH_COOKIE, false);
            $csrfClear = $response->getCookie(self::CSRF_COOKIE, false);
            $this->assertCookiePolicy($refreshClear, true, null, 'lax');
            $this->assertCookiePolicy($csrfClear, false, null, 'lax');
            $this->assertLessThanOrEqual(1, $refreshClear->getExpiresTime(), "attempt {$attempt}");
            $this->assertLessThanOrEqual(1, $csrfClear->getExpiresTime(), "attempt {$attempt}");
        }
    }

    /**
     * Verify logout-all and revoking the current session clear applicable Cookie credentials.
     */
    public function test_authenticated_session_revocations_clear_current_cookie_credentials(): void
    {
        $first = $this->loginCookie(['device_name' => 'logout-all-cookie']);
        $this->withToken((string) $first->json('data.access_token'))
            ->withHeader('Idempotency-Key', 'task7-cookie-logout-all-01')
            ->postJson('/api/v2/backend/auth/logout-all')
            ->assertOk()
            ->assertCookieExpired(self::REFRESH_COOKIE)
            ->assertCookieExpired(self::CSRF_COOKIE);

        $second = $this->loginCookie(['device_name' => 'revoke-current-cookie']);
        $sessionId = (string) $second->json('data.session.id');
        $this->withToken((string) $second->json('data.access_token'))
            ->withHeader('Idempotency-Key', 'task7-cookie-revoke-current-01')
            ->deleteJson('/api/v2/backend/auth/sessions/'.$sessionId)
            ->assertOk()
            ->assertCookieExpired(self::REFRESH_COOKIE)
            ->assertCookieExpired(self::CSRF_COOKIE);
    }

    /**
     * Send one valid Cookie-mode login request.
     *
     * @param  array<string, mixed>  $overrides
     * @return \Illuminate\Testing\TestResponse
     */
    private function loginCookie(array $overrides = [])
    {
        auth()->forgetGuards();

        return $this->withHeader('Origin', 'https://admin.example.test')
            ->postJson('/api/v2/backend/auth/login', $this->loginPayload($overrides));
    }

    /**
     * Return the valid login request body.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function loginPayload(array $overrides = []): array
    {
        return array_merge([
            'email' => $this->user->email,
            'password' => $this->password,
            'device_name' => 'cookie-flow',
        ], $overrides);
    }

    /**
     * Send one Cookie refresh or logout exchange.
     *
     * @return \Illuminate\Testing\TestResponse
     */
    private function cookieExchange(string $uri, string $refresh, string $csrfCookie, string $csrfHeader)
    {
        auth()->forgetGuards();
        $request = $this->withHeader('Origin', 'https://admin.example.test')
            ->withUnencryptedCookies([
                self::REFRESH_COOKIE => $refresh,
                self::CSRF_COOKIE => $csrfCookie,
            ])
            ->withCredentials();
        if ($csrfHeader !== '') {
            $request = $request->withHeader('X-WNCMS-CSRF', $csrfHeader);
        } else {
            $request = $request->withoutHeader('X-WNCMS-CSRF');
        }

        return $request->postJson($uri);
    }

    /**
     * Persist test settings and forget lazy typed configuration services.
     *
     * @param  array<string, mixed>  $settings
     */
    private function applySettings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            uss($key, $value);
        }

        $this->app->forgetInstance(AuthSecurityConfig::class);
    }

    /**
     * Assert exact browser Cookie flags.
     */
    private function assertCookiePolicy(?Cookie $cookie, bool $httpOnly, ?string $domain, string $sameSite): void
    {
        $this->assertNotNull($cookie);
        $this->assertSame('/api/v2/backend/auth', $cookie->getPath());
        $this->assertSame($domain, $cookie->getDomain());
        $this->assertTrue($cookie->isSecure());
        $this->assertSame($httpOnly, $cookie->isHttpOnly());
        $this->assertSame($sameSite, $cookie->getSameSite());
    }
}
