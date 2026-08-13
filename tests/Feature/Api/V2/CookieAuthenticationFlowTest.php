<?php

namespace Wncms\Tests\Feature\Api\V2;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Cookie;
use Wncms\Auth\Api\V2\AuthSecurityConfig;
use Wncms\Models\ApiSession;
use Wncms\Models\User;
use Wncms\Models\Website;
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
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'https://api.example.test',
            'session.secure' => true,
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
     *
     * @return void
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
    }

    /**
     * Verify a validated shared domain and every supported SameSite policy are emitted exactly.
     *
     * @return void
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
     *
     * @return void
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
     * Verify refresh rotates both credentials and updates the session-bound CSRF hash.
     *
     * @return void
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
     * Verify a CSRF binding failure rolls back refresh consumption and replacement issuance.
     *
     * @return void
     */
    public function test_cookie_refresh_rotation_and_csrf_binding_are_atomic(): void
    {
        $login = $this->loginCookie();
        $refresh = (string) $login->getCookie(self::REFRESH_COOKIE, false)->getValue();
        $csrf = (string) $login->getCookie(self::CSRF_COOKIE, false)->getValue();
        $tokenId = explode('.', substr($refresh, strlen('wncms_rt_')), 2)[0];
        DB::unprepared("CREATE TEMP TRIGGER task7_csrf_update_failure BEFORE UPDATE OF csrf_hash ON api_sessions BEGIN SELECT RAISE(FAIL, 'injected csrf update failure'); END");

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
        $this->cookieExchange('/api/v2/backend/auth/refresh', $refresh, $csrf, $csrf)->assertOk();
    }

    /**
     * Verify missing, mismatched, and server-unbound CSRF submissions fail stably.
     *
     * @return void
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
     *
     * @return void
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
     *
     * @return void
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
     * Verify Cookie logout is idempotent and always clears both browser credentials.
     *
     * @return void
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
     * Verify logout-all and revoking the current session clear applicable Cookie credentials.
     *
     * @return void
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
     * @param  string  $uri
     * @param  string  $refresh
     * @param  string  $csrfCookie
     * @param  string  $csrfHeader
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
     * @return void
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
     *
     * @param  \Symfony\Component\HttpFoundation\Cookie|null  $cookie
     * @param  bool  $httpOnly
     * @param  string|null  $domain
     * @param  string  $sameSite
     * @return void
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
