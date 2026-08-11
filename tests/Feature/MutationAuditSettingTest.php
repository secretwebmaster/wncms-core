<?php

namespace Wncms\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use ReflectionMethod;
use Wncms\Providers\WncmsServiceProvider;
use Wncms\Tests\TestCase;

class MutationAuditSettingTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Verify the global mutation audit switch is an Admin setting and defaults off.
     *
     * @return void
     */
    public function test_mutation_audit_setting_is_registered_under_admin_and_defaults_disabled(): void
    {
        $adminFields = collect(config('wncms-system-settings.admin.tab_content'));

        $this->assertTrue($adminFields->contains(
            fn(array $field): bool => $field === ['type' => 'switch', 'name' => 'enable_mutation_audit']
        ));
        $this->assertFalse((bool) config('wncms.mutation_audit.enabled', false));
    }

    /**
     * Verify saving the setting updates the next boot's runtime audit config.
     *
     * @return void
     */
    public function test_mutation_audit_setting_saves_and_boots_runtime_config(): void
    {
        $this->withoutMiddleware();

        $this->put(route('settings.update'), [
            'settings' => ['enable_mutation_audit' => '1'],
        ])->assertRedirect();

        $this->assertSame('1', (string) gss('enable_mutation_audit', false, false));

        config(['wncms.mutation_audit.enabled' => false]);
        $provider = new WncmsServiceProvider($this->app);
        $method = new ReflectionMethod($provider, 'loadSystemSettings');
        $method->invoke($provider);

        $this->assertTrue(config('wncms.mutation_audit.enabled'));
    }

    /**
     * Verify all shipped locales describe the global mutation audit setting.
     *
     * @return void
     */
    public function test_mutation_audit_setting_has_all_default_locale_labels(): void
    {
        foreach (['en', 'zh_CN', 'zh_TW', 'ja'] as $locale) {
            $words = require dirname(__DIR__, 2)."/lang/{$locale}/word.php";

            $this->assertNotEmpty($words['enable_mutation_audit'] ?? null, $locale);
            $this->assertNotEmpty($words['enable_mutation_audit_description'] ?? null, $locale);
        }
    }
}
