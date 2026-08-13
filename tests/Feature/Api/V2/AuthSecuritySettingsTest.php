<?php

namespace Wncms\Tests\Feature\Api\V2;

use Illuminate\Auth\Middleware\Authorize;
use Spatie\Permission\Models\Permission;
use Wncms\Auth\Api\V2\AuthSecurityConfig;
use Wncms\Database\Seeders\RolesSeeder;
use Wncms\Models\User;
use Wncms\Tests\TestCase;

class AuthSecuritySettingsTest extends TestCase
{
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

        $this->assertEmpty(array_diff($expected, (new RolesSeeder())->special_permissions()));
    }

    /**
     * Verify the role seeder does not register duplicate security permissions.
     */
    public function test_roles_seeder_registers_each_security_permission_once(): void
    {
        $permissions = (new RolesSeeder())->special_permissions();

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
            'settings' => array_map(static fn($value) => is_bool($value) ? ($value ? '1' : '0') : $value, AuthSecurityConfig::defaultSettings()),
        ]);

        $save->assertRedirect();
        $save->assertSessionDoesntHaveErrors();
        $this->assertSame('15', gss('api_access_token_lifetime_minutes'));
        $this->assertSame('1', gss('blade_enabled'));
    }

    /**
     * Authenticate a user authorized to update settings.
     *
     * @return void
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
