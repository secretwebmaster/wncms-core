<?php

namespace Wncms\Tests\Feature\Api\V2;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\Process\Process;
use Wncms\Auth\Api\V2\AccessTokenService;
use Wncms\Auth\Api\V2\CredentialParser;
use Wncms\Models\ApiRefreshToken;
use Wncms\Models\ApiSecurityEvent;
use Wncms\Models\ApiSession;
use Wncms\Models\User;
use Wncms\Models\Website;
use Wncms\Tests\TestCase;

class JsonAuthenticationFlowTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    private string $password = 'json-auth-password';

    public const EVENT_CONNECTION = 'task7_atomic_event';

    public const EVENT_TABLE = 'task7_atomic_security_events';

    private ?string $eventDatabase = null;

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
     * Remove test-owned model overrides and isolated event storage.
     */
    protected function tearDown(): void
    {
        config([
            'wncms.models.api_security_event' => null,
            'wncms.models.api_session' => null,
            'wncms.models.api_access_token' => null,
            'wncms.models.api_refresh_token' => null,
        ]);
        foreach (['api_security_event', 'api_session', 'api_access_token', 'api_refresh_token'] as $modelKey) {
            $this->clearCachedModelClass($modelKey);
        }
        if ($this->eventDatabase !== null) {
            DB::connection(self::EVENT_CONNECTION)->disconnect();
            DB::purge(self::EVENT_CONNECTION);
            if (is_file($this->eventDatabase)) {
                unlink($this->eventDatabase);
            }
        }

        parent::tearDown();
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
     * Verify two operating-system processes race the production consume predicate safely.
     *
     * Each process owns an independent SQLite connection, observes the token as unconsumed,
     * reaches a database-visible file barrier, and then competes on the conditional update.
     *
     * @return void
     */
    public function test_two_racing_rotations_have_exactly_one_atomic_consume_winner(): void
    {
        if (!class_exists(Process::class) || !function_exists('proc_open')) {
            $this->markTestSkipped('Independent process execution is unavailable.');
        }

        $directory = sys_get_temp_dir().'/wncms-refresh-race-'.bin2hex(random_bytes(8));
        mkdir($directory, 0700, true);
        $database = $directory.'/race.sqlite';
        $pdo = new \PDO('sqlite:'.$database);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec('PRAGMA journal_mode=WAL');
        $pdo->exec('CREATE TABLE api_refresh_tokens (id INTEGER PRIMARY KEY AUTOINCREMENT, token_id TEXT NOT NULL, consumed_at TEXT NULL, revoked_at TEXT NULL, replaced_by_token_id TEXT NULL, updated_at TEXT NULL)');
        $pdo->exec('CREATE TABLE refresh_race_barrier (contender TEXT PRIMARY KEY, state TEXT NOT NULL)');
        $pdo->exec("INSERT INTO api_refresh_tokens (token_id) VALUES ('race-token')");
        $tokenId = (int) $pdo->lastInsertId();
        $worker = dirname(__DIR__, 3).'/Support/RefreshTokenRaceWorker.php';
        $processes = [];

        try {
            foreach (['first', 'second'] as $contender) {
                $process = new Process([
                    PHP_BINARY,
                    $worker,
                    dirname(__DIR__, 4),
                    $database,
                    $contender,
                    (string) $tokenId,
                ]);
                $process->start();
                $processes[$contender] = $process;
            }

            $deadline = microtime(true) + 10;
            while ((int) $pdo->query("SELECT COUNT(*) FROM refresh_race_barrier WHERE state = 'ready'")->fetchColumn() !== 2 && microtime(true) < $deadline) {
                usleep(10_000);
            }
            $this->assertSame(2, (int) $pdo->query("SELECT COUNT(*) FROM refresh_race_barrier WHERE state = 'ready'")->fetchColumn());
            $pdo->exec("INSERT INTO refresh_race_barrier (contender, state) VALUES ('coordinator', 'start')");

            $outcomes = [];
            foreach ($processes as $process) {
                $process->wait();
                $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput().$process->getOutput());
                $outcomes[] = trim($process->getOutput());
            }

            sort($outcomes);
            $this->assertSame(['reuse', 'success'], $outcomes);
            $this->assertSame(1, (int) $pdo->query('SELECT COUNT(*) FROM api_refresh_tokens WHERE consumed_at IS NOT NULL')->fetchColumn());
        } finally {
            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $process->stop();
                }
            }
            foreach (glob($directory.'/*') ?: [] as $file) {
                unlink($file);
            }
            rmdir($directory);
        }
    }

    /**
     * Verify login, refresh, authentication activity, and revocation honor model overrides.
     *
     * @return void
     */
    public function test_interactive_lifecycle_resolves_all_applicable_models_through_the_registry(): void
    {
        config([
            'wncms.models.api_session' => ['class' => Task6ApiSessionOverride::class],
            'wncms.models.api_access_token' => ['class' => Task6ApiAccessTokenOverride::class],
            'wncms.models.api_refresh_token' => ['class' => Task6ApiRefreshTokenOverride::class],
        ]);
        foreach (['api_session', 'api_access_token', 'api_refresh_token'] as $modelKey) {
            $this->clearCachedModelClass($modelKey);
        }
        Task6ApiSessionOverride::resetTracking();
        Task6ApiAccessTokenOverride::resetTracking();
        Task6ApiRefreshTokenOverride::resetTracking();

        $login = $this->loginJson(['device_name' => 'registry-override'])->json('data');

        $this->assertSame(1, Task6ApiSessionOverride::$creates);
        $this->assertSame(1, Task6ApiAccessTokenOverride::$creates);
        $this->assertSame(1, Task6ApiRefreshTokenOverride::$creates);

        $this->refreshJson($login['refresh_token'])->assertOk();
        $this->assertSame(2, Task6ApiAccessTokenOverride::$creates);
        $this->assertSame(2, Task6ApiRefreshTokenOverride::$creates);

        DB::table('api_sessions')
            ->where('session_id', $login['session']['id'])
            ->update(['last_activity_at' => now()->subMinutes(10)]);
        Task6ApiSessionOverride::$builderUpdates = 0;
        Task6ApiAccessTokenOverride::$builderUpdates = 0;
        $credential = app(CredentialParser::class)->parse($login['access_token']);
        app(AccessTokenService::class)->authenticate($credential);
        $this->assertGreaterThanOrEqual(1, Task6ApiSessionOverride::$builderUpdates);
        $this->assertGreaterThanOrEqual(1, Task6ApiAccessTokenOverride::$builderUpdates);

        $session = Task6ApiSessionOverride::query()->where('session_id', $login['session']['id'])->firstOrFail();
        Task6ApiSessionOverride::$builderUpdates = 0;
        app(\Wncms\Auth\Api\V2\SessionService::class)->revoke($session, 'registry-test');
        $this->assertGreaterThanOrEqual(1, Task6ApiSessionOverride::$builderUpdates);
    }

    /**
     * Verify an event-model connection mismatch rejects login before credential mutation.
     */
    public function test_login_maps_cross_connection_audit_preflight_to_503_without_mutation(): void
    {
        $this->configureIsolatedEventOverride();
        config([
            'wncms.models.api_session' => ['class' => Task6ApiSessionOverride::class],
            'wncms.models.api_access_token' => ['class' => Task6ApiAccessTokenOverride::class],
            'wncms.models.api_refresh_token' => ['class' => Task6ApiRefreshTokenOverride::class],
        ]);
        foreach (['api_session', 'api_access_token', 'api_refresh_token'] as $modelKey) {
            $this->clearCachedModelClass($modelKey);
        }
        Task6ApiSessionOverride::resetTracking();
        Task6ApiAccessTokenOverride::resetTracking();
        Task6ApiRefreshTokenOverride::resetTracking();
        $sessionCount = DB::table('api_sessions')->count();
        $accessCount = DB::table('api_access_tokens')->count();
        $refreshCount = DB::table('api_refresh_tokens')->count();

        auth()->forgetGuards();
        $this->postJson('/api/v2/backend/auth/login', [
            'email' => $this->user->email,
            'password' => $this->password,
            'device_name' => 'cross-connection-preflight',
        ])->assertStatus(503)
            ->assertJsonPath('meta.error_code', 'security.audit_unavailable');

        $this->assertSame(0, Task6ApiSessionOverride::$creates);
        $this->assertSame(0, Task6ApiAccessTokenOverride::$creates);
        $this->assertSame(0, Task6ApiRefreshTokenOverride::$creates);
        $this->assertSame($sessionCount, DB::table('api_sessions')->count());
        $this->assertSame($accessCount, DB::table('api_access_tokens')->count());
        $this->assertSame($refreshCount, DB::table('api_refresh_tokens')->count());
        $this->assertSame(0, DB::connection(self::EVENT_CONNECTION)->table(self::EVENT_TABLE)->count());
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

    /**
     * Configure an isolated security-event model connection for atomicity preflight.
     */
    private function configureIsolatedEventOverride(): void
    {
        $this->eventDatabase = tempnam(sys_get_temp_dir(), 'wncms-atomic-event-');
        config(['database.connections.'.self::EVENT_CONNECTION => [
            ...config('database.connections.sqlite'),
            'database' => $this->eventDatabase,
        ]]);
        DB::purge(self::EVENT_CONNECTION);
        DB::connection(self::EVENT_CONNECTION)->statement('CREATE TABLE '.self::EVENT_TABLE.' (event_id TEXT PRIMARY KEY, request_id TEXT NULL)');
        config(['wncms.models.api_security_event' => ['class' => Task7AtomicSecurityEventOverride::class]]);
        $this->clearCachedModelClass('api_security_event');
    }

    /**
     * Forget one WNCMS model resolution cache entry after changing its test override.
     *
     * @param  string  $key
     * @return void
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
 * Track builder mutations executed through a configured API model override.
 */
class Task6TrackingBuilder extends Builder
{
    /**
     * Track updates before delegating to Eloquent.
     *
     * @param  array<string, mixed>  $values
     * @return int
     */
    public function update(array $values)
    {
        $modelClass = $this->getModel()::class;
        $modelClass::$builderUpdates++;

        return parent::update($values);
    }
}

/**
 * Test-only API session registry override.
 */
class Task6ApiSessionOverride extends ApiSession
{
    public static int $creates = 0;

    public static int $builderUpdates = 0;

    protected $table = 'api_sessions';

    protected static function booted(): void
    {
        static::creating(static function (): void {
            self::$creates++;
        });
    }

    public function newEloquentBuilder($query): Builder
    {
        return new Task6TrackingBuilder($query);
    }

    public static function resetTracking(): void
    {
        self::$creates = 0;
        self::$builderUpdates = 0;
    }
}

/**
 * Test-only API access-token registry override.
 */
class Task6ApiAccessTokenOverride extends \Wncms\Models\ApiAccessToken
{
    public static int $creates = 0;

    public static int $builderUpdates = 0;

    protected $table = 'api_access_tokens';

    protected static function booted(): void
    {
        static::creating(static function (): void {
            self::$creates++;
        });
    }

    public function newEloquentBuilder($query): Builder
    {
        return new Task6TrackingBuilder($query);
    }

    public static function resetTracking(): void
    {
        self::$creates = 0;
        self::$builderUpdates = 0;
    }
}

/**
 * Test-only API refresh-token registry override.
 */
class Task6ApiRefreshTokenOverride extends ApiRefreshToken
{
    public static int $creates = 0;

    public static int $builderUpdates = 0;

    protected $table = 'api_refresh_tokens';

    protected static function booted(): void
    {
        static::creating(static function (): void {
            self::$creates++;
        });
    }

    public function newEloquentBuilder($query): Builder
    {
        return new Task6TrackingBuilder($query);
    }

    public static function resetTracking(): void
    {
        self::$creates = 0;
        self::$builderUpdates = 0;
    }
}

/**
 * Test-only security-event override on an isolated named connection.
 */
class Task7AtomicSecurityEventOverride extends ApiSecurityEvent
{
    protected $connection = JsonAuthenticationFlowTest::EVENT_CONNECTION;

    protected $table = JsonAuthenticationFlowTest::EVENT_TABLE;
}
