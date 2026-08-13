<?php

namespace Wncms\Tests\Unit\Api\V2;

use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use Wncms\Auth\Api\V2\AuthSecurityConfig;
use Wncms\Auth\Api\V2\OriginPolicy;
use Wncms\Tests\TestCase;

class OriginPolicyTest extends TestCase
{
    /**
     * Verify Origin comparison uses the exact scheme, host, and effective port.
     */
    #[DataProvider('originMatrix')]
    public function test_origin_policy_matches_only_an_exact_allowed_origin(
        ?string $origin,
        ?string $referer,
        bool $refererFallback,
        bool $allowed,
    ): void {
        $policy = $this->policy([
            'api_refresh_cookie_allowed_origins' => "https://admin.example.test\nhttps://admin.example.test:8443",
            'api_refresh_cookie_referer_fallback' => $refererFallback,
        ]);
        $request = Request::create('https://api.example.test/api/v2/backend/auth/refresh', 'POST');
        if ($origin !== null) {
            $request->headers->set('Origin', $origin);
        }
        if ($referer !== null) {
            $request->headers->set('Referer', $referer);
        }

        try {
            $policy->assertAllowed($request);
            $actual = true;
        } catch (\RuntimeException $exception) {
            $this->assertSame('authentication.origin_denied', $exception->getMessage());
            $actual = false;
        }

        $this->assertSame($allowed, $actual);
    }

    /**
     * Provide exact Origin and explicit Referer fallback cases.
     *
     * @return array<string, array{0: string|null, 1: string|null, 2: bool, 3: bool}>
     */
    public static function originMatrix(): array
    {
        return [
            'exact default HTTPS port' => ['https://admin.example.test:443', null, false, true],
            'exact explicit port' => ['https://admin.example.test:8443', null, false, true],
            'scheme mismatch' => ['http://admin.example.test', null, false, false],
            'port mismatch' => ['https://admin.example.test:8444', null, false, false],
            'host suffix attack' => ['https://admin.example.test.attacker.test', null, false, false],
            'host prefix attack' => ['https://admin.example.testing', null, false, false],
            'wildcard value' => ['https://*.example.test', null, false, false],
            'null origin' => ['null', 'https://admin.example.test/path', true, false],
            'malformed origin' => ['https://admin.example.test/path', null, false, false],
            'missing origin' => [null, null, false, false],
            'referer disabled' => [null, 'https://admin.example.test/path', false, false],
            'referer exact fallback' => [null, 'https://admin.example.test/path?next=1', true, true],
            'referer wrong port' => [null, 'https://admin.example.test:8444/path', true, false],
        ];
    }

    /**
     * Verify cookie options preserve host-only and validated shared-domain policies.
     */
    public function test_cookie_options_are_secure_host_only_by_default_and_allow_a_validated_shared_domain(): void
    {
        config(['app.url' => 'https://api.example.test']);

        $hostOnly = $this->policy()->cookieOptions();
        $shared = $this->policy([
            'api_refresh_cookie_domain' => 'example.test',
            'api_refresh_cookie_same_site' => 'strict',
        ])->cookieOptions();

        $this->assertSame([
            'path' => '/api/v2/backend/auth',
            'domain' => null,
            'secure' => true,
            'same_site' => 'lax',
        ], $hostOnly);
        $this->assertSame('example.test', $shared['domain']);
        $this->assertSame('strict', $shared['same_site']);
    }

    /**
     * Verify invalid Cookie settings resolve to conservative values.
     */
    public function test_invalid_cookie_options_are_never_used_loosely(): void
    {
        config(['app.url' => 'https://api.example.test']);

        $options = $this->policy([
            'api_refresh_cookie_domain' => 'attacker.test',
            'api_refresh_cookie_same_site' => 'invalid',
        ])->cookieOptions();

        $this->assertNull($options['domain']);
        $this->assertSame('strict', $options['same_site']);
        $this->assertTrue($options['secure']);
    }

    /**
     * Verify SameSite=None fails closed when host credentialed CORS is unavailable.
     */
    public function test_same_site_none_requires_exact_host_credentialed_cors_configuration(): void
    {
        config([
            'app.url' => 'https://api.example.test',
            'session.secure' => true,
            'cors.paths' => ['api/v2/backend/auth/*'],
            'cors.allowed_origins' => [],
            'cors.supports_credentials' => false,
        ]);
        $values = [
            'api_refresh_transport' => 'cookie',
            'api_refresh_cookie_allowed_origins' => 'https://admin.example.test',
            'api_refresh_cookie_same_site' => 'none',
        ];

        $missing = AuthSecurityConfig::fromValues($values);
        $this->assertSame('json', $missing->refreshTransport());
        $this->assertArrayHasKey('api_refresh_cookie_same_site', $missing->validate());

        config([
            'cors.allowed_origins' => ['https://admin.example.test'],
            'cors.supports_credentials' => true,
        ]);
        $ready = AuthSecurityConfig::fromValues($values);
        $this->assertSame('cookie', $ready->refreshTransport());
        $this->assertArrayNotHasKey('api_refresh_cookie_same_site', $ready->validate());
    }

    /**
     * Build a policy from typed security configuration values.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function policy(array $overrides = []): OriginPolicy
    {
        return new OriginPolicy(AuthSecurityConfig::fromValues(array_merge([
            'api_refresh_transport' => 'cookie',
            'api_refresh_cookie_allowed_origins' => 'https://admin.example.test',
        ], $overrides)));
    }
}
