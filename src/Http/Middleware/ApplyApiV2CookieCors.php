<?php

namespace Wncms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Wncms\Api\V2\ApiResponseFactory;
use Wncms\Auth\Api\V2\AuthSecurityConfig;
use Wncms\Auth\Api\V2\OriginPolicy;
use Wncms\Services\Security\SecurityDenialRecorder;

final class ApplyApiV2CookieCors
{
    /**
     * Create the narrow credentialed-CORS boundary for browser auth endpoints.
     *
     * @param  \Wncms\Services\Security\SecurityEventService  $events
     */
    public function __construct(
        private OriginPolicy $origins,
        private ApiResponseFactory $responses,
        private SecurityDenialRecorder $denials,
    ) {}

    /**
     * Apply exact credentialed CORS and terminate valid browser preflights.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $config = AuthSecurityConfig::fromRuntime();
        if (! $request->is('api/v2/backend/auth/*') || ! $config->cookieTransportConfigured()) {
            return $next($request);
        }

        $origin = trim((string) $request->headers->get('Origin', ''));
        if ($config->refreshTransport() !== 'cookie') {
            $this->denials->record($request, 'security.origin.denied', 'authentication.origin_denied');

            return $this->removeCorsReflection($this->responses->failure(
                'authentication.origin_denied',
                'Request Origin is not allowed',
                Response::HTTP_FORBIDDEN,
            ));
        }

        if ($origin === '') {
            return $next($request);
        }

        try {
            $this->origins->assertAllowed($request);
        } catch (\RuntimeException $exception) {
            $this->denials->record($request, 'security.origin.denied', 'authentication.origin_denied');

            $response = $this->responses->failure(
                'authentication.origin_denied',
                'Request Origin is not allowed',
                Response::HTTP_FORBIDDEN,
            );

            return $this->removeCorsReflection($response);
        }

        $response = $request->isMethod('OPTIONS')
            ? response()->noContent()
            : $next($request);
        $response->headers->set('Access-Control-Allow-Origin', $origin);
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Authorization, Content-Type, Idempotency-Key, X-WNCMS-CSRF');
        $response->setVary('Origin', false);

        return $response;
    }

    /**
     * Remove every response header that could authorize a denied browser Origin.
     */
    private function removeCorsReflection(Response $response): Response
    {
        foreach ([
            'Access-Control-Allow-Origin',
            'Access-Control-Allow-Credentials',
            'Access-Control-Allow-Methods',
            'Access-Control-Allow-Headers',
            'Access-Control-Expose-Headers',
            'Access-Control-Max-Age',
        ] as $header) {
            $response->headers->remove($header);
        }

        return $response;
    }
}
