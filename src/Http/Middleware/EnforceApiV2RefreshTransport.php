<?php

namespace Wncms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Wncms\Api\V2\ApiResponseFactory;
use Wncms\Auth\Api\V2\AuthSecurityConfig;
use Wncms\Auth\Api\V2\OriginPolicy;

final class EnforceApiV2RefreshTransport
{
    /**
     * Create the strict refresh-transport middleware.
     *
     * @param  \Wncms\Auth\Api\V2\AuthSecurityConfig  $config
     * @param  \Wncms\Api\V2\ApiResponseFactory  $responses
     */
    public function __construct(
        private AuthSecurityConfig $config,
        private ApiResponseFactory $responses,
    ) {
    }

    /**
     * Reject refresh material submitted through the non-configured channel.
     *
     * Login has no incoming refresh credential, so stale browser cookies do not prevent
     * users from establishing a session after an administrator changes transport mode.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->route()?->getName() === 'api.v2.backend.auth.login') {
            return $next($request);
        }

        $cookieMode = $this->config->refreshTransport() === 'cookie';
        $wrongChannel = $cookieMode
            ? $request->exists('refresh_token')
            : $request->cookies->has(OriginPolicy::REFRESH_COOKIE);

        if ($wrongChannel) {
            return $this->responses->failure(
                'authentication.refresh_transport_mismatch',
                'Refresh credential transport does not match the configured mode',
                Response::HTTP_BAD_REQUEST,
            );
        }

        return $next($request);
    }
}
