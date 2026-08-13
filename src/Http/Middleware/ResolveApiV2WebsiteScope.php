<?php

namespace Wncms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Wncms\Api\V2\ApiResponseFactory;
use Wncms\Auth\Api\V2\AuthenticationContext;
use Wncms\Auth\Api\V2\WebsiteScopeGuard;

final class ResolveApiV2WebsiteScope
{
    /**
     * Create the API v2 website-scope middleware.
     *
     * @param  \Wncms\Auth\Api\V2\WebsiteScopeGuard  $guard
     * @param  \Wncms\Api\V2\ApiResponseFactory  $responses
     */
    public function __construct(
        private WebsiteScopeGuard $guard,
        private ApiResponseFactory $responses,
    ) {
    }

    /**
     * Resolve an explicit website and enforce actor/token scope intersection.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $context = $request->attributes->get(ApiV2TokenAuth::AUTH_CONTEXT_ATTRIBUTE);
        if (! $context instanceof AuthenticationContext) {
            return $this->responses->failure(
                'authentication.invalid_token',
                'Authentication context is unavailable',
                Response::HTTP_UNAUTHORIZED
            );
        }

        $resolution = $this->guard->resolve($request, $context);
        if (! $resolution->isAllowed()) {
            $errorCode = $resolution->errorCode() ?? 'website.scope_denied';

            return $this->responses->failure(
                $errorCode,
                $errorCode === 'website.scope_missing' ? 'Website scope is required' : 'Website scope denied',
                Response::HTTP_FORBIDDEN
            );
        }

        return $next($request);
    }
}
