<?php

namespace Wncms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Wncms\Api\V2\ApiResponseFactory;
use Wncms\Auth\Api\V2\AuthSecurityConfig;
use Wncms\Auth\Api\V2\OriginPolicy;
use Wncms\Services\Security\SecurityDenialRecorder;

final class ValidateApiV2RefreshOrigin
{
    /**
     * Create the Cookie Origin guard.
     */
    public function __construct(
        private OriginPolicy $origins,
        private ApiResponseFactory $responses,
        private SecurityDenialRecorder $denials,
    ) {}

    /**
     * Enforce exact Origin policy only while Cookie refresh mode is active.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (AuthSecurityConfig::fromRuntime()->refreshTransport() !== 'cookie') {
            return $next($request);
        }

        try {
            $this->origins->assertAllowed($request);
        } catch (\RuntimeException $exception) {
            $this->denials->record($request, 'security.origin.denied', 'authentication.origin_denied');

            return $this->responses->failure(
                'authentication.origin_denied',
                'Request Origin is not allowed',
                Response::HTTP_FORBIDDEN,
            );
        }

        return $next($request);
    }
}
