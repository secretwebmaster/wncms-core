<?php

namespace Wncms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Wncms\Api\V2\ApiResponseFactory;

class ApiV2Whitelist
{
    /**
     * Create the API v2 whitelist middleware.
     *
     * @param  \Wncms\Api\V2\ApiResponseFactory  $responses
     */
    public function __construct(protected ApiResponseFactory $responses)
    {
    }

    /**
     * Enforce the API feature switch and configured request whitelist.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!gss('enable_api_access')) {
            return $this->responses->failure(
                'authorization.denied',
                "API feature 'enable_api_access' is disabled",
                Response::HTTP_FORBIDDEN
            );
        }

        $entries = $this->getWhitelistEntries();
        if (empty($entries)) {
            return $next($request);
        }

        $ip = $request->ip();
        $domain = $this->getRequestDomain($request);

        if ($this->matches($entries, $ip, $domain)) {
            return $next($request);
        }

        return $this->responses->failure(
            'authorization.denied',
            'Request IP or domain is not in the API whitelist',
            Response::HTTP_FORBIDDEN
        );
    }

    protected function getWhitelistEntries(): array
    {
        $raw = (string) gss('api_access_whitelist', '');
        if (trim($raw) === '') {
            return [];
        }

        $entries = preg_split('/\r\n|\r|\n/', $raw) ?: [];

        return array_values(array_filter(array_map(function ($entry) {
            $entry = strtolower(trim((string) $entry));
            if ($entry === '') {
                return null;
            }

            $candidate = str_contains($entry, '://') ? $entry : "https://{$entry}";
            $host = parse_url($candidate, PHP_URL_HOST);

            return is_string($host) && trim($host) !== '' ? strtolower(trim($host)) : $entry;
        }, $entries)));
    }

    protected function getRequestDomain(Request $request): ?string
    {
        foreach (['Origin', 'Referer'] as $header) {
            $value = trim((string) $request->headers->get($header, ''));
            if ($value === '') {
                continue;
            }

            $host = parse_url($value, PHP_URL_HOST);
            if (is_string($host) && trim($host) !== '') {
                return strtolower(trim($host));
            }
        }

        return null;
    }

    protected function matches(array $entries, ?string $ip, ?string $domain): bool
    {
        foreach ($entries as $entry) {
            if (!empty($ip) && $entry === $ip) {
                return true;
            }

            if (!empty($domain) && $entry === $domain) {
                return true;
            }
        }

        return false;
    }
}
