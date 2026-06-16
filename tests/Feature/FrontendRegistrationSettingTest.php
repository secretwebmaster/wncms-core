<?php

namespace Wncms\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Wncms\Http\Controllers\Frontend\UserController;
use Wncms\Models\Website;
use Wncms\Tests\TestCase;

class FrontendRegistrationSettingTest extends TestCase
{
    protected mixed $originalDisableRegistration;
    protected Website $website;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureOptionsTableExists();
        $this->originalDisableRegistration = gss('disable_registration', true);
        $this->website = Website::firstOrFail();
        $this->clearThemeRegistrationOption();
    }

    protected function tearDown(): void
    {
        $this->clearThemeRegistrationOption();
        uss('disable_registration', $this->originalDisableRegistration ? 1 : 0);

        parent::tearDown();
    }

    public function test_frontend_registration_follows_system_setting_when_theme_option_is_not_set(): void
    {
        uss('disable_registration', 1);
        $this->assertFalse($this->registrationEnabled());

        uss('disable_registration', 0);
        $this->assertTrue($this->registrationEnabled());
    }

    public function test_theme_option_can_override_frontend_registration_setting(): void
    {
        uss('disable_registration', 1);
        $this->setThemeDisableRegistration(0);
        $this->assertTrue($this->registrationEnabled());

        uss('disable_registration', 0);
        $this->setThemeDisableRegistration(1);
        $this->assertFalse($this->registrationEnabled());
    }

    protected function registrationEnabled(): bool
    {
        $controller = app(UserController::class);
        $enabledRegistration = new \ReflectionMethod($controller, 'enabledRegistration');
        $enabledRegistration->setAccessible(true);

        return (bool) $enabledRegistration->invoke($controller);
    }

    protected function setThemeDisableRegistration(int $value): void
    {
        $this->website->setOption('disable_registration', (string) $value, 'theme', $this->website->theme);
        wncms()->cache()->tags(['websites'])->flush();
    }

    protected function clearThemeRegistrationOption(): void
    {
        if (isset($this->website)) {
            $this->website->deleteOption('disable_registration', 'theme', $this->website->theme);
        }

        wncms()->cache()->tags(['websites'])->flush();
    }

    protected function ensureOptionsTableExists(): void
    {
        if (Schema::hasTable('options')) {
            return;
        }

        Schema::create('options', function (Blueprint $table) {
            $table->id();
            $table->string('key', 191)->nullable();
            $table->text('value')->nullable();
            $table->morphs('optionable');
            $table->string('scope', 191)->nullable();
            $table->string('group', 191)->nullable();
            $table->unsignedInteger('sort')->nullable();
            $table->timestamps();

            $table->index('key');
            $table->index('scope');
            $table->index('group');
        });
    }
}
