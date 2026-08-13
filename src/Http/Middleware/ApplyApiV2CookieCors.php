<?php

namespace Wncms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Wncms\Api\V2\ApiResponseFactory;
use Wncms\Auth\Api\V2\AuthSecurityConfig;
use Wncms\Auth\Api\V2\OriginPolicy;
use Wncms\Services\Security\SecurityEventService;

final class ApplyApiV2CookieCors
{
    /**
     * Create the narrow credentialed-CORS boundary for browser auth endpoints.
     *
     * @param  \Wncms\Auth\Api\V2\OriginPolicy  $origins
     * @param  \Wncms\Api\V2\ApiResponseFactory  $responses
     * @param  \Wncms\Services\Security\SecurityEventService  $events
     */
    public function __construct(
        private OriginPolicy $origins,
        private ApiResponseFactory $responses,
        private SecurityEventService $events,
    ) {}

    /**
     * Apply exact credentialed CORS and terminate valid browser preflights.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('api/v2/backend/auth/*')
            || AuthSecurityConfig::fromRuntime()->refreshTransport() !== 'cookie') {
            return $next($request);
        }

        $origin = trim((string) $request->headers->get('Origin', ''));
        if ($origin === '') {
            return $next($request);
        }

        try {
            $this->origins->assertAllowed($request);
        } catch (\RuntimeException $exception) {
            if (! $request->isMethod('OPTIONS')) {
                return $next($request);
            }

            $this->recordOriginDenial($request);

            return $this->responses->failure(
                'authentication.origin_denied',
                'Request Origin is not allowed',
                Response::HTTP_FORBIDDEN,
            );
        }

        $response = $request->isMethod('OPTIONS')
            ? response()->noContent()
            : $next($request);
        $response->headers->set('Access-Control-Allow-Origin', $origin);
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Allow-Methods', 'POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, X-WNCMS-CSRF');
        $response->setVary('Origin', false);

        return $response;
    }

    /**
     * Persist a preflight Origin denial or emit a redacted structured fallback.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return void
     */
    private function recordOriginDenial(Request $request): void
    {
        try {
            $this->events->record('security.origin.denied', 'warning', 'denied', [
                'surface' => 'api_v2',
                'request_id' => $request->attributes->get('wncms_api_v2_request_id'),
                'error_code' => 'authentication.origin_denied',
                'http_status' => Response::HTTP_FORBIDDEN,
                'ip' => (string) $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'context' => ['reason' => 'origin_denied'],
            ]);
        } catch (\Throwable $exception) {
            Log::warning('WNCMS Cookie security denial event could not be persisted.', [
                'event_type' => 'security.origin.denied',
                'error_code' => 'authentication.origin_denied',
                'request_id' => $request->attributes->get('wncms_api_v2_request_id'),
                'exception' => $exception::class,
            ]);
        }
    }
}
