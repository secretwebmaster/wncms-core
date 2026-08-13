<?php

namespace Wncms\Tests\Feature\Api\V2;

use Carbon\CarbonImmutable;
use Fiber;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Wncms\Auth\Api\V2\CredentialParser;
use Wncms\Auth\Api\V2\AccessTokenService;
use Wncms\Auth\Api\V2\TokenHasher;
use Wncms\Auth\Api\V2\RefreshTokenReuseException;
use Wncms\Auth\Api\V2\RefreshTokenService;
use Wncms\Api\V2\ApiContractRegistry;
use Wncms\Models\ApiRefreshToken;
use Wncms\Models\ApiSession;
use Wncms\Models\User;
use Wncms\Models\Website;
use Wncms\Services\Security\SecurityEventService;
use Wncms\Tests\TestCase;

class JsonAuthenticationFlowTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private string $password = 'json-auth-password';

    /**
     * Prepare one interactive actor and mandatory event keys.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->configureSecurityKeys();
        config([
            'wncms.auth_security.access_token_lifetime_minutes' => 15,
            'wncms.auth_security.refresh_token_lifetime_days' => 30,
            'wncms.auth_security.refresh_transport' => 'json',
            'wncms.auth_security.permanent_remember_enabled' => false,
            'wncms.auth_security.login_account_attempts' => 50,
            'wncms.auth_security.login_ip_attempts' => 50,
            'wncms.auth_security.login_window_minutes' => 15,
            'wncms.auth_security.login_progressive_delay_seconds' => [0],
        ]);
        uss('enable_api_access', 1);
        uss('api_access_whitelist', '');

        $this->user = User::create([
            'username' => 'json-auth-'.uniqid(),
            'email' => 'json-auth-'.uniqid().'@example.test',
            'password' => Hash::make($this->password),
            'email_verified_at' => now(),
        ]);
        $website = Website::firstOrFail();
        $this->user->websites()->syncWithoutDetaching([$website->id]);
    }

    /**
     * Verify JSON login creates one owned session and one short/long credential pair.
     *
     * @return void
     */
    public function test_json_login_issues_hash_only_access_and_refresh_credentials_with_stable_defaults(): void
    {
        CarbonImmutable::setTestNow('2026-08-13 10:00:00 UTC');

        $response = $this->loginJson();

        $response->assertOk()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.id', $this->user->id)
            ->assertJsonStructure(['data' => [
                'access_token', 'access_expires_at', 'refresh_token', 'refresh_expires_at', 'session', 'user',
            ]]);

        $access = (string) $response->json('data.access_token');
        $refresh = (string) $response->json('data.refresh_token');
        $this->assertStringStartsWith('wncms_at_', $access);
        $this->assertStringStartsWith('wncms_rt_', $refresh);
        $this->assertSame($access, $response->json('data.token'));
        $this->assertSame('2026-08-13T10:15:00+00:00', $response->json('data.access_expires_at'));
        $this->assertSame('2026-09-12T10:00:00+00:00', $response->json('data.refresh_expires_at'));
        $this->assertSame(1, DB::table('api_sessions')->where('user_id', $this->user->id)->count());
        $this->assertSame(1, DB::table('api_access_tokens')->where('user_id', $this->user->id)->count());
        $this->assertSame(1, DB::table('api_refresh_tokens')->where('user_id', $this->user->id)->count());
        $this->assertFalse(DB::table('api_access_tokens')->where('token_hash', $access)->exists());
        $this->assertFalse(DB::table('api_refresh_tokens')->where('token_hash', $refresh)->exists());
        $this->assertSame(hash('sha256', $access), DB::table('api_access_tokens')->where('user_id', $this->user->id)->value('token_hash'));
        $this->assertSame(hash('sha256', $refresh), DB::table('api_refresh_tokens')->where('user_id', $this->user->id)->value('token_hash'));
        $this->assertDatabaseHas('api_security_events', ['event_type' => 'auth.login.succeeded']);

        CarbonImmutable::setTestNow();
    }

    /**
     * Verify permanent remember policy affects refresh/session expiry but never access expiry.
     *
     * @return void
     */
    public function test_permitted_remember_me_keeps_refresh_permanent_while_access_remains_short_lived(): void
    {
        CarbonImmutable::setTestNow('2026-08-13 10:00:00 UTC');
        config(['wncms.auth_security.permanent_remember_enabled' => true]);

        $response = $this->loginJson(['remember_me' => true]);

        $response->assertOk()
            ->assertJsonPath('data.access_expires_at', '2026-08-13T10:15:00+00:00')
            ->assertJsonPath('data.refresh_expires_at', null)
            ->assertJsonPath('data.session.remembered', true);
        $session = ApiSession::query()->where('user_id', $this->user->id)->latest('id')->firstOrFail();
        $this->assertTrue((bool) $session->remembered);
        $this->assertNull($session->expires_at);
        $this->assertNull(ApiRefreshToken::query()->where('session_id', $session->id)->value('expires_at'));

        CarbonImmutable::setTestNow();
    }

    /**
     * Verify a rotated refresh is one-time and replay revokes no other device family.
     *
     * @return void
     */
    public function test_reusing_rotated_refresh_revokes_only_its_session_family(): void
    {
        $first = $this->loginJson(['device_name' => 'first'])->json('data');
        $other = $this->loginJson(['device_name' => 'other'])->json('data');

        $this->refreshJson($first['refresh_token'])
            ->assertOk()
            ->assertJsonPath('data.session.id', $first['session']['id']);
        $this->refreshJson($first['refresh_token'])
            ->assertUnauthorized()
            ->assertJsonPath('meta.error_code', 'authentication.refresh_reuse_detected');
        $this->refreshJson($other['refresh_token'])->assertOk();

        $this->assertNotNull(ApiSession::query()->where('session_id', $first['session']['id'])->value('revoked_at'));
        $this->assertNull(ApiSession::query()->where('session_id', $other['session']['id'])->value('revoked_at'));
        $this->assertDatabaseHas('api_security_events', ['event_type' => 'auth.refresh.reuse_detected']);
    }

    /**
     * Verify the production atomic consume predicate has exactly one winner at a race barrier.
     *
     * This controlled race seam runs both contenders from the same pre-consume state. The
     * loser must observe the affected-row predicate and surface typed replay detection.
     *
     * @return void
     */
    public function test_two_racing_rotations_have_exactly_one_atomic_consume_winner(): void
    {
        $login = $this->loginJson()->json('data');
        $credential = app(CredentialParser::class)->parse($login['refresh_token']);
        $barrier = static function (): void {
            Fiber::suspend('ready-to-consume');
        };
        $service = static fn (): RefreshTokenService => new RefreshTokenService(
            app(TokenHasher::class),
            app(AccessTokenService::class),
            app(SecurityEventService::class),
            app(ApiContractRegistry::class),
            $barrier,
        );
        $contender = static function (RefreshTokenService $service) use ($credential): string {
            try {
                $service->rotate($credential);

                return 'success';
            } catch (RefreshTokenReuseException $exception) {
                return 'reuse';
            }
        };
        $first = new Fiber(fn (): string => $contender($service()));
        $second = new Fiber(fn (): string => $contender($service()));

        $this->assertSame('ready-to-consume', $first->start());
        $this->assertSame('ready-to-consume', $second->start());
        $first->resume();
        $second->resume();

        $outcomes = [$first->getReturn(), $second->getReturn()];

        sort($outcomes);
        $this->assertSame(['reuse', 'success'], $outcomes);
        $this->assertSame(1, ApiRefreshToken::query()->whereNotNull('consumed_at')->where('token_id', $credential->publicId())->count());
    }

    /**
     * Send one valid JSON login request.
     *
     * @param  array<string, mixed>  $overrides
     * @return \Illuminate\Testing\TestResponse
     */
    private function loginJson(array $overrides = [])
    {
        auth()->forgetGuards();

        $response = $this->postJson('/api/v2/backend/auth/login', array_merge([
            'email' => $this->user->email,
            'password' => $this->password,
            'device_name' => 'json-flow',
        ], $overrides));
        $response->assertJsonStructure(['data' => [
            'access_token', 'access_expires_at', 'refresh_token', 'refresh_expires_at', 'session', 'user',
        ]]);

        return $response;
    }

    /**
     * Rotate one JSON refresh credential.
     *
     * @param  string  $refreshToken
     * @return \Illuminate\Testing\TestResponse
     */
    private function refreshJson(string $refreshToken)
    {
        auth()->forgetGuards();

        return $this->postJson('/api/v2/backend/auth/refresh', ['refresh_token' => $refreshToken]);
    }

    /**
     * Configure mandatory versioned event-correlation keys.
     *
     * @return void
     */
    private function configureSecurityKeys(): void
    {
        config(['wncms-api-v2.auth_security.security_event_correlation' => [
            'active_key_version' => 'v1',
            'keys' => ['v1' => [
                'ip' => 'task6-json-ip-correlation-key-1234567890',
                'login_identifier' => 'task6-json-login-correlation-key-1234567890',
                'user_agent' => 'task6-json-agent-correlation-key-1234567890',
            ]],
        ]]);
    }
}
