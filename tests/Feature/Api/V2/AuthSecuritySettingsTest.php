<?php

namespace Wncms\Tests\Feature\Api\V2;

use Wncms\Auth\Api\V2\AuthSecurityConfig;
use Wncms\Database\Seeders\RolesSeeder;
use Wncms\Tests\TestCase;

class AuthSecuritySettingsTest extends TestCase
{
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
}
