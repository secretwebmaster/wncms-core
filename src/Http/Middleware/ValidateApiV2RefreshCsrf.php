<?php

namespace Wncms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Wncms\Api\V2\ApiResponseFactory;
use Wncms\Auth\Api\V2\ApiCredential;
use Wncms\Auth\Api\V2\AuthSecurityConfig;
use Wncms\Auth\Api\V2\CredentialParser;
use Wncms\Auth\Api\V2\CsrfTokenService;
use Wncms\Auth\Api\V2\OriginPolicy;
use Wncms\Auth\Api\V2\RefreshTokenService;
use Wncms\Services\Security\SecurityEventService;

final class ValidateApiV2RefreshCsrf
{
    public const SESSION_ATTRIBUTE = 'wncms_api_v2_refresh_session';

    /**
     * Create the Cookie refresh CSRF guard.
     */
    public function __construct(
        private CredentialParser $credentials,
        private RefreshTokenService $refreshTokens,
        private CsrfTokenService $csrf,
        private ApiResponseFactory $responses,
        private SecurityEventService $events,
    ) {}

    /**
     * Validate double-submit and session-bound CSRF before refresh rotation or logout.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (AuthSecurityConfig::fromRuntime()->refreshTransport() !== 'cookie') {
            return $next($request);
        }

        $refresh = trim((string) $request->cookie(OriginPolicy::REFRESH_COOKIE, ''));
        $refreshToken = $refresh === ''
            ? null
            : $this->refreshTokens->refreshTokenForGuard($this->credentials->parse($refresh));
        $session = $refreshToken === null
            ? null
            : $this->refreshTokens->sessionForLogout($this->credentials->parse($refresh));
        if ($session === null || $session->refresh_transport !== 'cookie') {
            return $this->responses->failure(
                'authentication.refresh_invalid',
                'Refresh credential is not valid',
                Response::HTTP_UNAUTHORIZED,
            );
        }

        try {
            $this->csrf->assertValid(
                $refreshToken,
                (string) $request->cookie(OriginPolicy::CSRF_COOKIE, ''),
                (string) $request->header('X-WNCMS-CSRF', ''),
            );
        } catch (\RuntimeException $exception) {
            $this->recordDenial($request, $refreshToken->token_id, $session->session_id);

            return $this->responses->failure(
                'authentication.csrf_failed',
                'Refresh CSRF validation failed',
                Response::HTTP_FORBIDDEN,
            );
        }

        $request->attributes->set(self::SESSION_ATTRIBUTE, $session);

        return $next($request);
    }

    /**
     * Persist an allowlisted CSRF denial or emit a redacted fallback warning.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $credentialId
     * @param  string  $sessionId
     *
     * @return void
     */
    private function recordDenial(Request $request, string $credentialId, string $sessionId): void
    {
        try {
            $this->events->record('security.csrf.denied', 'warning', 'denied', [
                'surface' => 'api_v2',
                'request_id' => $request->attributes->get('wncms_api_v2_request_id'),
                'credential_type' => ApiCredential::TYPE_REFRESH,
                'credential_id' => $credentialId,
                'session_id' => $sessionId,
                'error_code' => 'authentication.csrf_failed',
                'http_status' => Response::HTTP_FORBIDDEN,
                'ip' => (string) $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'context' => ['reason' => 'csrf_failed'],
            ]);
        } catch (\Throwable $exception) {
            Log::warning('WNCMS Cookie security denial event could not be persisted.', [
                'event_type' => 'security.csrf.denied',
                'error_code' => 'authentication.csrf_failed',
                'request_id' => $request->attributes->get('wncms_api_v2_request_id'),
                'exception' => $exception::class,
            ]);
        }
    }
}
