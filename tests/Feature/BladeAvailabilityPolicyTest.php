<?php

namespace Wncms\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Wncms\Services\Security\BladeAvailabilityService;
use Wncms\Tests\TestCase;

class BladeAvailabilityPolicyTest extends TestCase
{
    use DatabaseTransactions;

    public function test_missing_is_enabled_and_invalid_or_unavailable_is_disabled_when_installed(): void
    {
        DB::table('settings')->where('key', 'blade_enabled')->delete();
        $service = app(BladeAvailabilityService::class);
        $this->assertSame('missing', $service->state()->status);
        $this->assertTrue($service->state()->enabled);

        DB::table('settings')->insert(['key' => 'blade_enabled', 'value' => 'invalid', 'created_at' => now(), 'updated_at' => now()]);
        $this->assertSame('invalid', $service->state()->status);
        $this->assertFalse($service->state()->enabled);
    }

    public function test_disabled_blade_returns_plain_404_before_controller_execution(): void
    {
        DB::table('settings')->updateOrInsert(['key' => 'blade_enabled'], ['value' => '0', 'created_at' => now(), 'updated_at' => now()]);

        $this->get('/panel/login')->assertNotFound()->assertHeader('content-type', 'text/plain; charset=UTF-8');
    }

    public function test_uninstalled_system_remains_available_for_installation(): void
    {
        config(['wncms.testing_is_installed' => false]);
        $state = app(BladeAvailabilityService::class)->state();
        $this->assertFalse($state->installed);
        $this->assertTrue($state->enabled);
    }

    public function test_only_emergency_cli_enable_may_bypass_an_unavailable_audit_store(): void
    {
        DB::table('settings')->updateOrInsert(['key' => 'blade_enabled'], ['value' => '0', 'created_at' => now(), 'updated_at' => now()]);
        config(['wncms-api-v2.auth_security.security_event_correlation.active_key_version' => null]);
        $service = app(BladeAvailabilityService::class);

        try {
            $service->enable('api_v2');
            $this->fail('The API-safe enable path must fail closed when audit persistence is unavailable.');
        } catch (\RuntimeException) {
            $this->assertFalse($service->state()->enabled);
        }

        $state = $service->emergencyEnable('cli');
        $this->assertTrue($state->enabled);
        $this->assertContains('audit_unavailable', $state->warnings);
    }
}
