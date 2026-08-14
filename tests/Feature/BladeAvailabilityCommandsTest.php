<?php

namespace Wncms\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Wncms\Tests\TestCase;

class BladeAvailabilityCommandsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'wncms-api-v2.auth_security.security_event_correlation.active_key_version' => 'v1',
            'wncms-api-v2.auth_security.security_event_correlation.keys.v1' => [
                'ip' => str_repeat('i', 32), 'login_identifier' => str_repeat('l', 32), 'user_agent' => str_repeat('u', 32),
            ],
        ]);
    }

    public function test_disable_requires_force_and_enable_recovers_the_ui(): void
    {
        $this->artisan('wncms:blade:disable')->assertFailed();
        $this->artisan('wncms:blade:disable', ['--force' => true])->assertSuccessful();
        $this->assertSame('0', (string) gss('blade_enabled', null, false));

        $this->artisan('wncms:blade:status', ['--json' => true])->expectsOutputToContain('"enabled":false')->assertSuccessful();
        $this->artisan('wncms:blade:enable')->assertSuccessful();
        $this->assertSame('1', (string) gss('blade_enabled', null, false));
    }
}
