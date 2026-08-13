<?php

namespace Wncms\Tests\Feature\Api\V2;

use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Wncms\Auth\Api\V2\AuthSecurityConfig;
use Wncms\Database\Seeders\RolesSeeder;
use Wncms\Models\User;
use Wncms\Tests\TestCase;

class AuthSecuritySettingsTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Remove persisted authentication security settings after every request test.
     */
    protected function tearDown(): void
    {
        foreach (AuthSecurityConfig::settingKeys() as $settingKey) {
            wncms()->setting()->delete($settingKey);
        }

        parent::tearDown();
    }

    /**
     * Verify the approved runtime defaults remain stable.
     */
    public function test_auth_security_defaults_are_stable(): void
    {
        $config = AuthSecurityConfig::fromRuntime();

        $this->assertSame('json', $config->refreshTransport());
        $this->assertSame(15, $config->accessLifetimeMinutes());
        $this->assertSame(30, $config->refreshLifetimeDays());
        $this->assertSame('direct', $config->highRiskMode());
        $this->assertSame(300, $config->stepUpLifetimeSeconds());
    }

    /**
     * Verify the role seeder registers the exact authentication security permissions.
     */
    public function test_roles_seeder_registers_exact_security_permissions(): void
    {
        $expected = [
            'api_token_create',
            'api_token_create_cross_site',
            'api_token_create_permanent',
            'api_token_index',
            'api_token_show',
            'api_token_rotate',
            'api_token_revoke',
            'security_event_index',
            'security_event_show',
            'blade_mode_manage',
        ];

        $this->assertEmpty(array_diff($expected, (new RolesSeeder)->special_permissions()));
    }

    /**
     * Verify the role seeder does not register duplicate security permissions.
     */
    public function test_roles_seeder_registers_each_security_permission_once(): void
    {
        $permissions = (new RolesSeeder)->special_permissions();

        $this->assertSame($permissions, array_values(array_unique($permissions)));
    }

    /**
     * Verify every authentication security setting label exists in each default locale.
     */
    public function test_auth_security_setting_labels_exist_in_every_default_locale(): void
    {
        $keys = array_merge(['api_security'], AuthSecurityConfig::settingKeys());

        foreach (['en', 'zh_CN', 'zh_TW', 'ja'] as $locale) {
            $words = trans('wncms::word', [], $locale);

            foreach ($keys as $key) {
                $this->assertArrayHasKey($key, $words, "Missing {$key} in {$locale}.");
            }
        }
    }

    /**
     * Verify settings updates reject invalid security values without writing any submitted setting.
     */
    public function test_settings_update_rejects_invalid_security_values_atomically(): void
    {
        uss('api_access_token_lifetime_minutes', '15');
        uss('blade_enabled', '1');
        $this->authenticateSettingsEditor();

        $response = $this->put(route('settings.update'), [
            'settings' => [
                'api_access_token_lifetime_minutes' => '0',
                'blade_enabled' => '0',
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('api_access_token_lifetime_minutes');
        $this->assertSame('15', gss('api_access_token_lifetime_minutes'));
        $this->assertSame('1', gss('blade_enabled'));
    }

    /**
     * Verify rejected settings input is rendered after the validation redirect.
     */
    public function test_rejected_settings_input_is_rendered_after_validation_redirect(): void
    {
        $this->authenticateSettingsEditor();

        $response = $this->from(route('settings.index'))->followingRedirects()->put(route('settings.update'), [
            'settings' => [
                'api_access_token_lifetime_minutes' => '0',
            ],
        ]);

        $response->assertOk();
        $response->assertSee('name="settings[api_access_token_lifetime_minutes]" value="0"', false);
    }

    /**
     * Verify Cookie transport cannot be persisted without exact allowed Origins.
     */
    public function test_settings_update_rejects_cookie_transport_without_allowed_origins(): void
    {
        uss('api_refresh_transport', 'json');
        $this->authenticateSettingsEditor();

        $response = $this->put(route('settings.update'), [
            'settings' => [
                'api_refresh_transport' => 'cookie',
                'api_refresh_cookie_allowed_origins' => '',
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('api_refresh_cookie_allowed_origins');
        $this->assertSame('json', gss('api_refresh_transport'));
    }

    /**
     * Verify absent stable settings are hydrated before the generic form renders and saves.
     */
    public function test_settings_form_hydrates_absent_security_defaults_before_save(): void
    {
        $this->authenticateSettingsEditor();

        $response = $this->get(route('settings.index'));

        $response->assertOk();
        $response->assertViewHas('settings', function (array $settings) {
            return $settings['api_access_token_lifetime_minutes'] === 15
                && $settings['api_refresh_transport'] === 'json'
                && $settings['blade_enabled'] === true;
        });

        $save = $this->put(route('settings.update'), [
            'settings' => array_map(static fn ($value) => is_bool($value) ? ($value ? '1' : '0') : $value, AuthSecurityConfig::defaultSettings()),
        ]);

        $save->assertRedirect();
        $save->assertSessionDoesntHaveErrors();
        $this->assertSame('15', gss('api_access_token_lifetime_minutes'));
        $this->assertSame('1', gss('blade_enabled'));
    }

    /**
     * Verify a transport-boundary save atomically revokes interactive credentials only.
     */
    public function test_transport_boundary_setting_revokes_interactive_credentials_but_not_service_tokens(): void
    {
        $this->configureSecurityEventCorrelation();
        $this->authenticateSettingsEditor();
        uss('api_refresh_transport', 'json');
        $ids = $this->seedBoundaryCredentials('setting-boundary-success');

        $this->put(route('settings.update'), [
            'settings' => [
                'api_refresh_transport' => 'cookie',
                'api_refresh_cookie_allowed_origins' => 'https://admin.example.test',
            ],
        ])->assertRedirect()->assertSessionDoesntHaveErrors();

        $this->assertSame('cookie', gss('api_refresh_transport'));
        $this->assertNotNull(DB::table('api_sessions')->where('id', $ids['session'])->value('revoked_at'));
        $this->assertNotNull(DB::table('api_access_tokens')->where('id', $ids['access'])->value('revoked_at'));
        $this->assertNotNull(DB::table('api_refresh_tokens')->where('id', $ids['refresh'])->value('revoked_at'));
        $this->assertNull(DB::table('api_service_tokens')->where('id', $ids['service'])->value('revoked_at'));
        $this->assertDatabaseHas('api_security_events', [
            'event_type' => 'security.auth_policy.changed',
            'outcome' => 'succeeded',
        ]);
    }

    /**
     * Verify setting, revocation, and mandatory audit roll back together.
     */
    public function test_transport_boundary_setting_fails_closed_when_audit_persistence_fails(): void
    {
        $this->configureSecurityEventCorrelation();
        $this->authenticateSettingsEditor();
        uss('api_refresh_transport', 'json');
        $ids = $this->seedBoundaryCredentials('setting-boundary-failure');
        DB::unprepared("CREATE TEMP TRIGGER task7_setting_audit_failure BEFORE INSERT ON api_security_events BEGIN SELECT RAISE(FAIL, 'injected setting audit failure'); END");

        try {
            $this->put(route('settings.update'), [
                'settings' => [
                    'api_refresh_transport' => 'cookie',
                    'api_refresh_cookie_allowed_origins' => 'https://admin.example.test',
                ],
            ])->assertStatus(503);
        } finally {
            DB::unprepared('DROP TRIGGER IF EXISTS task7_setting_audit_failure');
        }

        $this->assertSame('json', gss('api_refresh_transport', null, false));
        $this->assertNull(DB::table('api_sessions')->where('id', $ids['session'])->value('revoked_at'));
        $this->assertNull(DB::table('api_access_tokens')->where('id', $ids['access'])->value('revoked_at'));
        $this->assertNull(DB::table('api_refresh_tokens')->where('id', $ids['refresh'])->value('revoked_at'));
        $this->assertNull(DB::table('api_service_tokens')->where('id', $ids['service'])->value('revoked_at'));
    }

    /**
     * Verify a Cookie policy-only change revokes Cookie sessions but preserves JSON sessions.
     */
    public function test_cookie_policy_setting_change_revokes_only_cookie_sessions(): void
    {
        $this->configureSecurityEventCorrelation();
        $this->authenticateSettingsEditor();
        uss('api_refresh_transport', 'cookie');
        uss('api_refresh_cookie_allowed_origins', 'https://admin.example.test');
        uss('api_refresh_cookie_referer_fallback', '0');
        $userId = User::where('email', 'admin@demo.com')->value('id');
        $cookieSession = DB::table('api_sessions')->insertGetId([
            'session_id' => 'setting-cookie-policy-cookie',
            'user_id' => $userId,
            'refresh_transport' => 'cookie',
            'remembered' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $jsonSession = DB::table('api_sessions')->insertGetId([
            'session_id' => 'setting-cookie-policy-json',
            'user_id' => $userId,
            'refresh_transport' => 'json',
            'remembered' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->put(route('settings.update'), [
            'settings' => ['api_refresh_cookie_referer_fallback' => '1'],
        ])->assertRedirect()->assertSessionDoesntHaveErrors();

        $this->assertNotNull(DB::table('api_sessions')->where('id', $cookieSession)->value('revoked_at'));
        $this->assertNull(DB::table('api_sessions')->where('id', $jsonSession)->value('revoked_at'));
    }

    /**
     * Seed one complete interactive family and one isolated service credential.
     *
     * @return array{session: int, access: int, refresh: int, service: int}
     */
    private function seedBoundaryCredentials(string $prefix): array
    {
        $userId = User::where('email', 'admin@demo.com')->value('id');
        $session = DB::table('api_sessions')->insertGetId([
            'session_id' => $prefix.'-session',
            'user_id' => $userId,
            'refresh_transport' => 'json',
            'remembered' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $access = DB::table('api_access_tokens')->insertGetId([
            'token_id' => $prefix.'-access',
            'token_hash' => hash('sha256', $prefix.'-access-secret'),
            'user_id' => $userId,
            'session_id' => $session,
            'abilities' => '[]',
            'website_ids' => '[]',
            'expires_at' => now()->addHour(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $refresh = DB::table('api_refresh_tokens')->insertGetId([
            'token_id' => $prefix.'-refresh',
            'token_hash' => hash('sha256', $prefix.'-refresh-secret'),
            'user_id' => $userId,
            'session_id' => $session,
            'family_id' => $prefix.'-family',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $service = DB::table('api_service_tokens')->insertGetId([
            'token_id' => $prefix.'-service',
            'token_hash' => hash('sha256', $prefix.'-service-secret'),
            'user_id' => $userId,
            'name' => 'Task 7 boundary service token',
            'ability_template' => 'read_only',
            'abilities' => '[]',
            'website_ids' => '[]',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return compact('session', 'access', 'refresh', 'service');
    }

    /**
     * Configure deterministic test-only event-correlation keys.
     */
    private function configureSecurityEventCorrelation(): void
    {
        config(['wncms-api-v2.auth_security.security_event_correlation' => [
            'active_key_version' => 'v1',
            'keys' => ['v1' => [
                'ip' => 'task7-settings-ip-correlation-key-1234567890',
                'login_identifier' => 'task7-settings-login-correlation-key-1234567890',
                'user_agent' => 'task7-settings-agent-correlation-key-1234567890',
            ]],
        ]]);
    }

    /**
     * Authenticate a user authorized to update settings.
     */
    private function authenticateSettingsEditor(): void
    {
        $this->withoutMiddleware(Authorize::class);
        $admin = User::where('email', 'admin@demo.com')->firstOrFail();
        Permission::findOrCreate('setting_edit', 'web');
        Permission::findOrCreate('setting_index', 'web');
        $admin->givePermissionTo(['setting_edit', 'setting_index']);
        $this->actingAs($admin);
    }
}
