<?php

namespace Wncms\Tests\Feature\Api\V2;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Sleep;
use Wncms\Models\User;
use Wncms\Models\Website;
use Wncms\Tests\TestCase;

class LoginThrottleTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private string $password = 'throttle-password';

    /**
     * Prepare deterministic login limits and mandatory event keys.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('unused');
        Sleep::fake();
        config([
            'wncms-api-v2.auth_security.security_event_correlation' => [
                'active_key_version' => 'v1',
                'keys' => ['v1' => [
                    'ip' => 'task6-throttle-ip-correlation-key-123456',
                    'login_identifier' => 'task6-throttle-login-correlation-key-123456',
                    'user_agent' => 'task6-throttle-agent-correlation-key-123456',
                ]],
            ],
            'wncms.auth_security.access_token_lifetime_minutes' => 15,
            'wncms.auth_security.refresh_token_lifetime_days' => 30,
            'wncms.auth_security.refresh_transport' => 'json',
            'wncms.auth_security.permanent_remember_enabled' => false,
            'wncms.auth_security.login_account_attempts' => 2,
            'wncms.auth_security.login_ip_attempts' => 2,
            'wncms.auth_security.login_window_minutes' => 15,
            'wncms.auth_security.login_progressive_delay_seconds' => [1, 2, 4],
        ]);
        uss('enable_api_access', 1);
        uss('api_access_whitelist', '');
        $this->user = User::create([
            'username' => 'throttle-user-'.uniqid(),
            'email' => 'throttle-user-'.uniqid().'@example.test',
            'password' => Hash::make($this->password),
            'email_verified_at' => now(),
        ]);
        $this->user->websites()->syncWithoutDetaching([Website::firstOrFail()->id]);
    }

    /**
     * Verify unknown accounts and wrong passwords use the same hash-check and response path.
     *
     * @return void
     */
    public function test_unknown_account_and_wrong_password_are_generic_and_both_execute_password_hash_checks(): void
    {
        Hash::spy();

        $unknown = $this->login('unknown-'.uniqid().'@example.test', 'wrong', '203.0.113.10');
        $wrong = $this->login($this->user->email, 'wrong', '203.0.113.11');

        $unknown->assertUnauthorized()->assertJsonPath('meta.error_code', 'authentication.invalid_credentials');
        $wrong->assertUnauthorized()->assertJsonPath('meta.error_code', 'authentication.invalid_credentials');
        $this->assertSame($unknown->json('message'), $wrong->json('message'));
        Hash::shouldHaveReceived('check')->twice();
    }

    /**
     * Verify account and IP ceilings independently reject before another password validation.
     *
     * @return void
     */
    public function test_account_and_ip_limiters_are_independent(): void
    {
        $this->login($this->user->email, 'wrong', '203.0.113.20')->assertUnauthorized();
        $this->login($this->user->email, 'wrong', '203.0.113.21')->assertUnauthorized();
        $this->login($this->user->email, 'wrong', '203.0.113.22')
            ->assertTooManyRequests()
            ->assertJsonPath('meta.error_code', 'authentication.rate_limited');

        $ip = '203.0.113.30';
        $this->login('one-'.uniqid().'@example.test', 'wrong', $ip)->assertUnauthorized();
        $this->login('two-'.uniqid().'@example.test', 'wrong', $ip)->assertUnauthorized();
        $this->login('three-'.uniqid().'@example.test', 'wrong', $ip)
            ->assertTooManyRequests()
            ->assertJsonPath('meta.error_code', 'authentication.rate_limited');
        $this->assertDatabaseHas('api_security_events', ['event_type' => 'auth.login.throttled']);
    }

    /**
     * Verify delays progress per account and successful login clears account state only.
     *
     * @return void
     */
    public function test_progressive_delay_advances_and_success_clears_account_failure_state(): void
    {
        config([
            'wncms.auth_security.login_account_attempts' => 10,
            'wncms.auth_security.login_ip_attempts' => 10,
        ]);
        $this->login($this->user->email, 'wrong', '203.0.113.40')->assertUnauthorized();
        $this->login($this->user->email, 'wrong', '203.0.113.41')->assertUnauthorized();
        Sleep::assertSequence([
            Sleep::sleep(1),
            Sleep::sleep(2),
        ]);

        $this->login($this->user->email, $this->password, '203.0.113.42')->assertOk();
        $this->login($this->user->email, 'wrong', '203.0.113.43')->assertUnauthorized();
        Sleep::assertSequence([
            Sleep::sleep(1),
            Sleep::sleep(2),
            Sleep::sleep(1),
        ]);
    }

    /**
     * Send one JSON login attempt from a selected client IP.
     *
     * @param  string  $email
     * @param  string  $password
     * @param  string  $ip
     * @return \Illuminate\Testing\TestResponse
     */
    private function login(string $email, string $password, string $ip)
    {
        auth()->forgetGuards();

        return $this->withServerVariables(['REMOTE_ADDR' => $ip])->postJson('/api/v2/backend/auth/login', [
            'email' => $email,
            'password' => $password,
            'device_name' => 'throttle-test',
        ]);
    }
}
