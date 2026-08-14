<?php

namespace Wncms\Tests\Feature\Api\V2;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Wncms\Models\User;
use Wncms\Models\Website;
use Wncms\Tests\TestCase;

class AuthSecurityAcceptanceTest extends TestCase
{
    private const REFRESH_COOKIE = '__Secure-wncms_refresh';
    private const CSRF_COOKIE = 'wncms_refresh_csrf';
    private array $snapshot = [];
    private User $user;
    private string $password = 'Acceptance-Password-123!';

    protected function setUp(): void
    {
        parent::setUp();
        foreach (DB::getSchemaBuilder()->getTableListing() as $table) $this->snapshot[$table] = DB::table($table)->get()->map(fn ($row) => (array) $row)->all();
        config([
            'app.url' => 'https://api.example.test', 'session.secure' => true,
            'cors.paths' => ['api/v2/backend/auth/*'], 'cors.allowed_origins' => ['https://admin.example.test'], 'cors.supports_credentials' => true,
            'wncms-api-v2.idempotency.store' => 'array',
            'wncms-api-v2.auth_security.security_event_correlation.active_key_version' => 'v1',
            'wncms-api-v2.auth_security.security_event_correlation.keys.v1' => [
                'ip' => str_repeat('i', 32), 'login_identifier' => str_repeat('l', 32), 'user_agent' => str_repeat('u', 32),
            ],
            'wncms.auth_security.login_progressive_delay_seconds' => [0],
        ]);
        foreach (['enable_api_access' => 1, 'api_access_whitelist' => '', 'api_refresh_transport' => 'json', 'blade_enabled' => 1] as $key => $value) uss($key, $value);
        $this->user = User::create(['username' => uniqid('accept-'), 'email' => uniqid('accept-').'@example.test', 'password' => Hash::make($this->password), 'email_verified_at' => now()]);
        $this->user->websites()->sync([Website::firstOrFail()->id]);
        foreach (['api_token_create', 'api_token_index', 'api_token_show', 'api_token_rotate', 'api_token_revoke', 'blade_mode_manage'] as $permission) {
            $this->user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
    }

    protected function tearDown(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');
        foreach (array_reverse(array_keys($this->snapshot)) as $table) DB::table($table)->delete();
        foreach ($this->snapshot as $table => $rows) if ($rows !== []) DB::table($table)->insert($rows);
        DB::statement('PRAGMA foreign_keys = ON');
        Cache::flush();
        parent::tearDown();
    }

    public function test_complete_json_admin_security_lifecycle_and_blade_recovery(): void
    {
        $login = $this->login()->assertOk()->json('data');
        $access = $login['access_token'];
        $this->withToken($access)->getJson('/api/v2/backend/auth/me')->assertOk();
        $this->postJson('/api/v2/backend/auth/refresh', ['refresh_token' => $login['refresh_token']])->assertOk();

        $created = $this->withToken($access)->withHeader('Idempotency-Key', 'accept-service-create')
            ->withHeader('X-WNCMS-Step-Up', $this->proof($access, 'backend.auth.service_tokens.store', 'service_token.create'))
            ->postJson('/api/v2/backend/auth/service-tokens', ['name' => 'acceptance', 'template' => 'read_only', 'website_ids' => [Website::firstOrFail()->id], 'expires_in_days' => 30])
            ->assertCreated()->json('data');
        $serviceId = $created['service_token']['id'];
        $rotated = $this->withToken($access)->withHeader('Idempotency-Key', 'accept-service-rotate')
            ->withHeader('X-WNCMS-Step-Up', $this->proof($access, 'backend.auth.service_tokens.rotate', 'service_token.rotate'))
            ->postJson('/api/v2/backend/auth/service-tokens/'.$serviceId.'/rotate')->assertOk()->json('data.service_token.id');
        $this->withToken($access)->withHeader('Idempotency-Key', 'accept-service-revoke')
            ->withHeader('X-WNCMS-Step-Up', $this->proof($access, 'backend.auth.service_tokens.destroy', 'service_token.revoke'))
            ->deleteJson('/api/v2/backend/auth/service-tokens/'.$rotated)->assertOk();

        $this->withToken($access)->withHeader('Idempotency-Key', 'accept-blade-disable')
            ->withHeader('X-WNCMS-Step-Up', $this->proof($access, 'backend.security.blade.update', 'blade.mode'))
            ->patchJson('/api/v2/backend/security/blade', ['enabled' => false])->assertOk();
        $this->get('/panel/login')->assertNotFound();
        $this->withToken($access)->getJson('/api/v2/backend/auth/me')->assertOk();
        $this->artisan('wncms:blade:enable')->assertSuccessful();
        $this->assertNotSame(404, $this->get('/panel/login')->getStatusCode());

        $this->withToken($access)->withHeader('Idempotency-Key', 'accept-password-change')
            ->withHeader('X-WNCMS-Step-Up', $this->proof($access, 'backend.auth.password.update', 'password.change'))
            ->patchJson('/api/v2/backend/auth/password', [
                'current_password' => $this->password, 'password' => 'Acceptance-New-456!', 'password_confirmation' => 'Acceptance-New-456!',
            ])->assertOk()->assertJsonPath('data.reauthentication_required', true);
        $this->withToken($access)->getJson('/api/v2/backend/auth/me')->assertUnauthorized();
    }

    public function test_cookie_transport_rotates_without_refresh_plaintext_leak(): void
    {
        foreach (['api_refresh_transport' => 'cookie', 'api_refresh_cookie_allowed_origins' => 'https://admin.example.test', 'api_refresh_cookie_same_site' => 'lax'] as $key => $value) uss($key, $value);
        $login = $this->withHeader('Origin', 'https://admin.example.test')->postJson('/api/v2/backend/auth/login', [
            'email' => $this->user->email, 'password' => $this->password, 'device_name' => 'accept-cookie',
        ])->assertOk()->assertJsonMissingPath('data.refresh_token');
        $refresh = (string) $login->getCookie(self::REFRESH_COOKIE, false)->getValue();
        $csrf = (string) $login->getCookie(self::CSRF_COOKIE, false)->getValue();
        $this->withHeader('Origin', 'https://admin.example.test')->withHeader('X-WNCMS-CSRF', $csrf)
            ->withUnencryptedCookies([self::REFRESH_COOKIE => $refresh, self::CSRF_COOKIE => $csrf])->withCredentials()
            ->postJson('/api/v2/backend/auth/refresh')->assertOk()->assertJsonMissingPath('data.refresh_token');
    }

    private function login()
    {
        return $this->postJson('/api/v2/backend/auth/login', ['email' => $this->user->email, 'password' => $this->password, 'device_name' => 'accept-json']);
    }

    private function proof(string $access, string $operation, string $purpose): string
    {
        return (string) $this->withToken($access)->postJson('/api/v2/backend/auth/reauthenticate', [
            'password' => $this->password, 'operation' => $operation, 'purpose' => $purpose,
        ])->assertOk()->json('data.proof');
    }
}
