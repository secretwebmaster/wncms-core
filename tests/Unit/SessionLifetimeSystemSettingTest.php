<?php

namespace Wncms\Tests\Unit;

use Wncms\Providers\WncmsServiceProvider;
use Wncms\Tests\TestCase;

class SessionLifetimeSystemSettingTest extends TestCase
{
    protected function tearDown(): void
    {
        wncms()->setting()->delete('session_lifetime');
        config(['session.lifetime' => 120]);

        parent::tearDown();
    }

    public function test_it_overrides_session_lifetime_from_system_settings(): void
    {
        config(['session.lifetime' => 120]);
        uss('session_lifetime', '45');

        $provider = new class($this->app) extends WncmsServiceProvider
        {
            public function applySessionSettings(): void
            {
                $this->loadSessionSettings();
            }
        };

        $provider->applySessionSettings();

        $this->assertSame(45, config('session.lifetime'));
    }

    public function test_it_falls_back_to_existing_session_config_when_setting_is_invalid(): void
    {
        config(['session.lifetime' => 120]);
        uss('session_lifetime', '0');

        $provider = new class($this->app) extends WncmsServiceProvider
        {
            public function applySessionSettings(): void
            {
                $this->loadSessionSettings();
            }
        };

        $provider->applySessionSettings();

        $this->assertSame(120, config('session.lifetime'));
    }
}
