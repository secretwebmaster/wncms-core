<?php

namespace Wncms\Tests\Feature\Api\V2;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\RateLimiter as CacheRateLimiter;
use Illuminate\Cache\Repository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Sleep;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\Attributes\DataProvider;
use Wncms\Auth\Api\V2\DummyPasswordHasher;
use Wncms\Auth\Api\V2\LoginThrottleService;
use Wncms\Models\ApiSession;
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
    #[DataProvider('supportedPasswordDrivers')]
    public function test_unknown_account_and_wrong_password_are_generic_for_each_real_hash_driver(string $driver, string $algorithm): void
    {
        if (!in_array($algorithm, password_algos(), true)) {
            $this->markTestSkipped("{$driver} is unavailable in this PHP runtime.");
        }

        config([
            'hashing.driver' => $driver,
            'hashing.bcrypt' => ['rounds' => 4, 'verify' => true],
            'hashing.argon' => ['memory' => 1024, 'threads' => 1, 'time' => 1, 'verify' => true],
        ]);
        app('hash')->forgetDrivers();
        try {
            $passwordHash = Hash::make($this->password);
        } catch (\RuntimeException $exception) {
            $this->markTestSkipped("{$driver} is advertised but unavailable: {$exception->getMessage()}");
        }
        $this->user->forceFill(['password' => $passwordHash])->save();

        $unknown = $this->login('unknown-'.uniqid().'@example.test', 'wrong', '203.0.113.10');
        $wrong = $this->login($this->user->email, 'wrong', '203.0.113.11');

        $unknown->assertUnauthorized()->assertJsonPath('meta.error_code', 'authentication.invalid_credentials');
        $wrong->assertUnauthorized()->assertJsonPath('meta.error_code', 'authentication.invalid_credentials');
        $this->assertSame($unknown->json('message'), $wrong->json('message'));
        $this->assertDatabaseMissing('api_sessions', ['user_id' => $this->user->id]);
    }

    /**
     * Supply real password drivers and their PHP algorithm identifiers.
     *
     * @return array<string, array{string, string}>
     */
    public static function supportedPasswordDrivers(): array
    {
        return [
            'bcrypt' => ['bcrypt', '2y'],
            'argon2i' => ['argon', 'argon2i'],
            'argon2id' => ['argon2id', 'argon2id'],
        ];
    }

    /**
     * Verify one singleton safely rotates its cached dummy material after driver changes.
     *
     * @return void
     */
    public function test_dummy_password_cache_is_scoped_to_the_current_hash_configuration(): void
    {
        config(['hashing.driver' => 'bcrypt', 'hashing.bcrypt' => ['rounds' => 4, 'verify' => true]]);
        app('hash')->forgetDrivers();
        $service = app(DummyPasswordHasher::class);
        $bcrypt = $service->material();
        $this->assertSame('bcrypt', password_get_info($bcrypt)['algoName']);

        config([
            'hashing.driver' => 'argon2id',
            'hashing.argon' => ['memory' => 1024, 'threads' => 1, 'time' => 1, 'verify' => true],
        ]);
        app('hash')->forgetDrivers();
        try {
            $argon = $service->material();
        } catch (\RuntimeException $exception) {
            $this->markTestSkipped('Argon2id is unavailable: '.$exception->getMessage());
        }

        $this->assertSame('argon2id', password_get_info($argon)['algoName']);
        $this->assertNotSame($bcrypt, $argon);
        $this->assertSame($argon, $service->material());
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

    /**
     * Verify post-commit limiter cleanup failure cannot discard an issued credential pair.
     *
     * @return void
     */
    public function test_successful_login_is_fail_open_when_limiter_cleanup_storage_throws(): void
    {
        $handler = new TestHandler();
        Log::getLogger()->pushHandler($handler);
        $store = new class extends ArrayStore
        {
            /**
             * Simulate an unavailable cache delete operation.
             *
             * @param  string  $key
             * @return bool
             */
            public function forget($key)
            {
                throw new \RuntimeException('Injected cache cleanup failure.');
            }
        };
        $this->app->instance(
            LoginThrottleService::class,
            new LoginThrottleService(new CacheRateLimiter(new Repository($store))),
        );

        try {
            $response = $this->login($this->user->email, $this->password, '203.0.113.50');
        } finally {
            Log::getLogger()->popHandler();
        }

        $response->assertOk()->assertJsonStructure(['data' => ['access_token', 'refresh_token', 'session']]);
        $this->assertSame(1, ApiSession::query()->where('user_id', $this->user->id)->whereNull('revoked_at')->count());
        $this->assertSame(1, DB::table('api_access_tokens')->where('user_id', $this->user->id)->whereNull('revoked_at')->count());
        $this->assertSame(1, DB::table('api_refresh_tokens')->where('user_id', $this->user->id)->whereNull('revoked_at')->count());

        $this->withToken((string) $response->json('data.access_token'))
            ->getJson('/api/v2/backend/auth/me')
            ->assertOk();
        $records = json_encode($handler->getRecords(), JSON_THROW_ON_ERROR);
        $this->assertStringContainsString('login limiter state could not be cleared', $records);
        $this->assertStringNotContainsString($this->user->email, $records);
        $this->assertStringNotContainsString($this->password, $records);
    }
}
