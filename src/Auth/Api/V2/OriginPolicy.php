<?php

namespace Wncms\Auth\Api\V2;

use Illuminate\Http\Request;

final class OriginPolicy
{
    public const REFRESH_COOKIE = '__Secure-wncms_refresh';

    public const CSRF_COOKIE = 'wncms_refresh_csrf';

    public const COOKIE_PATH = '/api/v2/backend/auth';

    public const PERMANENT_COOKIE_LIFETIME_DAYS = 400;

    /**
     * Create the exact browser-Origin policy.
     */
    public function __construct(private ?AuthSecurityConfig $config = null) {}

    /**
     * Assert that a request came from one configured exact Origin.
     *
     * Referer is considered only when Origin is absent and the explicit compatibility
     * fallback is enabled. Comparison includes scheme, host, and effective port.
     *
     *
     * @throws \RuntimeException
     */
    public function assertAllowed(Request $request): void
    {
        $originHeader = $request->headers->get('Origin');
        $candidate = null;

        if (is_string($originHeader) && trim($originHeader) !== '') {
            $candidate = $this->canonicalOrigin($originHeader, false);
        } elseif ($originHeader === null && $this->securityConfig()->refreshCookieRefererFallback()) {
            $referer = $request->headers->get('Referer');
            $candidate = is_string($referer) ? $this->canonicalOrigin($referer, true) : null;
        }

        if ($candidate === null) {
            throw new \RuntimeException('authentication.origin_denied');
        }

        foreach ($this->securityConfig()->refreshCookieAllowedOrigins() as $allowedOrigin) {
            $allowed = $this->canonicalOrigin($allowedOrigin, false);
            if ($allowed !== null && hash_equals($allowed, $candidate)) {
                return;
            }
        }

        throw new \RuntimeException('authentication.origin_denied');
    }

    /**
     * Return validated browser Cookie options shared by refresh and CSRF cookies.
     *
     * @return array{path: string, domain: string|null, secure: true, same_site: string}
     */
    public function cookieOptions(): array
    {
        return [
            'path' => self::COOKIE_PATH,
            'domain' => $this->securityConfig()->refreshCookieDomain(),
            'secure' => true,
            'same_site' => $this->securityConfig()->refreshCookieSameSite(),
        ];
    }

    /**
     * Resolve explicit test configuration or a fresh runtime snapshot.
     */
    private function securityConfig(): AuthSecurityConfig
    {
        return $this->config ?? AuthSecurityConfig::fromRuntime();
    }

    /**
     * Canonicalize a URL to an exact scheme, host, and effective port.
     */
    private function canonicalOrigin(string $value, bool $allowPath): ?string
    {
        $value = trim($value);
        if ($value === '' || strtolower($value) === 'null' || str_contains($value, ',')) {
            return null;
        }

        $parts = parse_url($value);
        if (! is_array($parts)
            || ! isset($parts['scheme'], $parts['host'])
            || isset($parts['user'], $parts['pass'], $parts['fragment'])
            || (! $allowPath && isset($parts['path']))
            || (! $allowPath && isset($parts['query']))) {
            return null;
        }

        $scheme = strtolower((string) $parts['scheme']);
        $host = strtolower((string) $parts['host']);
        if (! in_array($scheme, ['http', 'https'], true)
            || $host === ''
            || str_contains($host, '*')) {
            return null;
        }

        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);
        if (! is_int($port) || $port < 1 || $port > 65535) {
            return null;
        }

        $displayHost = str_contains($host, ':') ? '['.$host.']' : $host;

        return $scheme.'://'.$displayHost.':'.$port;
    }
}
