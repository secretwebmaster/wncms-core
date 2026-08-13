<?php

namespace Wncms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Wncms\Api\V2\ApiResponseFactory;
use Wncms\Auth\Api\V2\AuthSecurityConfig;
use Wncms\Auth\Api\V2\OriginPolicy;

final class ValidateApiV2RefreshOrigin
{
    /**
     * Create the Cookie Origin guard.
     *
     * @param  \Wncms\Auth\Api\V2\AuthSecurityConfig  $config
     * @param  \Wncms\Auth\Api\V2\OriginPolicy  $origins
     * @param  \Wncms\Api\V2\ApiResponseFactory  $responses
     */
    public function __construct(
        private AuthSecurityConfig $config,
        private OriginPolicy $origins,
        private ApiResponseFactory $responses,
    ) {
    }

    /**
     * Enforce exact Origin policy only while Cookie refresh mode is active.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->config->refreshTransport() !== 'cookie') {
            return $next($request);
        }

        try {
            $this->origins->assertAllowed($request);
        } catch (\RuntimeException $exception) {
            return $this->responses->failure(
                'authentication.origin_denied',
                'Request Origin is not allowed',
                Response::HTTP_FORBIDDEN,
            );
        }

        return $next($request);
    }
}
