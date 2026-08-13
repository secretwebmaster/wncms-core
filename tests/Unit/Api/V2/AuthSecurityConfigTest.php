<?php

namespace Wncms\Tests\Unit\Api\V2;

use Wncms\Auth\Api\V2\AuthSecurityConfig;
use Wncms\Providers\WncmsServiceProvider;
use Wncms\Tests\TestCase;

class AuthSecurityConfigTest extends TestCase
{
    protected mixed $originalAppUrl;

    protected mixed $originalSessionSecure;

    /**
     * Preserve runtime configuration changed by validation scenarios.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->originalAppUrl = config('app.url');
        $this->originalSessionSecure = config('session.secure');
    }

    /**
     * Reset security settings after each validation scenario.
     */
    protected function tearDown(): void
    {
        foreach (AuthSecurityConfig::settingKeys() as $settingKey) {
            wncms()->setting()->delete($settingKey);
        }

        config([
            'app.url' => $this->originalAppUrl,
            'session.secure' => $this->originalSessionSecure,
        ]);

        parent::tearDown();
    }

    /**
     * Verify every stable default is exposed through a typed getter.
     */
    public function test_it_exposes_all_stable_defaults_through_typed_getters(): void
    {
        $config = AuthSecurityConfig::fromRuntime();

        $this->assertSame(15, $config->accessLifetimeMinutes());
        $this->assertSame(30, $config->refreshLifetimeDays());
        $this->assertSame('json', $config->refreshTransport());
        $this->assertFalse($config->permanentRememberEnabled());
        $this->assertNull($config->refreshCookieDomain());
        $this->assertSame('lax', $config->refreshCookieSameSite());
        $this->assertSame([], $config->refreshCookieAllowedOrigins());
        $this->assertFalse($config->refreshCookieRefererFallback());
        $this->assertSame(5, $config->loginAccountAttempts());
        $this->assertSame(30, $config->loginIpAttempts());
        $this->assertSame(15, $config->loginWindowMinutes());
        $this->assertSame([1, 2, 4, 8, 16, 30], $config->loginProgressiveDelaySeconds());
        $this->assertSame('direct', $config->highRiskMode());
        $this->assertSame(300, $config->actionPlanLifetimeSeconds());
        $this->assertSame(300, $config->stepUpLifetimeSeconds());
        $this->assertTrue($config->bladeEnabled());
        $this->assertFalse($config->legacyPersonalTokensEnabled());
        $this->assertNull($config->legacyPersonalTokensCutoffAt());
        $this->assertSame(90, $config->securityEventRetentionDays());
        $this->assertSame([], $config->validate());
    }

    /**
     * Verify cookie transport fails validation unless exact origins are configured.
     */
    public function test_cookie_transport_requires_exact_allowed_origins(): void
    {
        uss('api_refresh_transport', 'cookie');

        $errors = AuthSecurityConfig::fromRuntime()->validate();

        $this->assertSame(
            'At least one exact allowed origin is required when Cookie refresh transport is enabled.',
            $errors['api_refresh_cookie_allowed_origins']
        );
    }

    /**
     * Verify SameSite=None needs secure HTTPS cookie settings and exact origins.
     */
    public function test_same_site_none_requires_https_compatible_settings(): void
    {
        uss('api_refresh_cookie_same_site', 'none');
        uss('api_refresh_cookie_allowed_origins', 'https://console.example.test');
        config([
            'app.url' => 'http://localhost',
            'session.secure' => false,
        ]);

        $errors = AuthSecurityConfig::fromRuntime()->validate();

        $this->assertSame(
            'SameSite=None requires HTTPS, secure cookies, and exact host credentialed CORS for API auth paths.',
            $errors['api_refresh_cookie_same_site']
        );
    }

    /**
     * Verify an explicit UTC legacy-token cutoff is accepted.
     */
    public function test_utc_legacy_personal_token_cutoff_is_valid(): void
    {
        uss('api_legacy_personal_tokens_cutoff_at', '2026-08-13T00:00:00Z');

        $errors = AuthSecurityConfig::fromRuntime()->validate();

        $this->assertArrayNotHasKey('api_legacy_personal_tokens_cutoff_at', $errors);
    }

    /**
     * Verify an invalid legacy personal-token cutoff is never exposed to runtime consumers.
     */
    public function test_invalid_legacy_personal_token_cutoff_fails_closed_to_null(): void
    {
        uss('api_legacy_personal_tokens_cutoff_at', 'not-a-date');

        $config = AuthSecurityConfig::fromRuntime();

        $this->assertNull($config->legacyPersonalTokensCutoffAt());
        $this->assertNull($config->toArray()['legacy_personal_tokens_cutoff_at']);
        $this->assertArrayHasKey('api_legacy_personal_tokens_cutoff_at', $config->validate());
    }

    /**
     * Verify validated settings are mapped under the WNCMS runtime configuration namespace.
     */
    public function test_provider_maps_validated_auth_security_settings_to_runtime_config(): void
    {
        $provider = new class($this->app) extends WncmsServiceProvider
        {
            public function applyAuthSecuritySettings(): void
            {
                $this->loadAuthSecuritySettings();
            }
        };

        $provider->applyAuthSecuritySettings();

        $this->assertTrue(config('wncms.auth_security.valid'));
        $this->assertSame('json', config('wncms.auth_security.refresh_transport'));
        $this->assertSame(15, config('wncms.auth_security.access_token_lifetime_minutes'));
    }

    /**
     * Verify invalid security settings map to conservative runtime values.
     */
    public function test_invalid_runtime_values_fail_closed_without_requiring_consumers_to_check_validity(): void
    {
        uss('blade_enabled', 'invalid');
        uss('api_refresh_transport', 'cookie');
        uss('api_refresh_cookie_allowed_origins', '');

        $config = AuthSecurityConfig::fromRuntime();
        $runtime = $config->toArray();

        $this->assertFalse($config->bladeEnabled());
        $this->assertSame('json', $config->refreshTransport());
        $this->assertFalse($runtime['blade_enabled']);
        $this->assertSame('json', $runtime['refresh_transport']);
        $this->assertFalse($runtime['valid']);
    }
}
