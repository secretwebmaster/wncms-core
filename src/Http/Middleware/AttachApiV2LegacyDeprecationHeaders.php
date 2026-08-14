<?php

namespace Wncms\Http\Middleware;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Wncms\Auth\Api\V2\ApiCredential;
use Wncms\Auth\Api\V2\AuthenticationContext;
use Wncms\Auth\Api\V2\AuthSecurityConfig;

final class AttachApiV2LegacyDeprecationHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $context = $request->attributes->get(ApiV2TokenAuth::AUTH_CONTEXT_ATTRIBUTE);
        if (! $context instanceof AuthenticationContext || $context->credentialType() !== ApiCredential::TYPE_LEGACY_PERSONAL_ACCESS_TOKEN) {
            return $response;
        }

        $cutoff = AuthSecurityConfig::fromRuntime()->legacyPersonalTokensCutoffAt();
        $response->headers->set('Deprecation', 'true');
        if (is_string($cutoff) && $cutoff !== '') {
            $response->headers->set('Sunset', CarbonImmutable::parse($cutoff)->utc()->toRfc7231String());
        }
        $response->headers->set('Link', '<'.url('/docs/api/authentication#scoped-service-tokens').'>; rel="deprecation"');
        $response->headers->set('X-WNCMS-Credential-Type', ApiCredential::TYPE_LEGACY_PERSONAL_ACCESS_TOKEN);

        return $response;
    }
}
