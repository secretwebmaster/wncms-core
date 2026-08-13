<?php

namespace Wncms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Wncms\Api\V2\ApiResponseFactory;
use Wncms\Auth\Api\V2\ApiCredential;
use Wncms\Auth\Api\V2\AuthSecurityConfig;
use Wncms\Auth\Api\V2\CredentialParser;
use Wncms\Auth\Api\V2\CsrfTokenService;
use Wncms\Auth\Api\V2\OriginPolicy;
use Wncms\Auth\Api\V2\RefreshTokenService;
use Wncms\Services\Security\SecurityDenialRecorder;

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
        private SecurityDenialRecorder $denials,
    ) {}

    /**
     * Validate double-submit and session-bound CSRF before refresh rotation or logout.
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
            $this->denials->record($request, 'security.csrf.denied', 'authentication.csrf_failed', [
                'credential_type' => ApiCredential::TYPE_REFRESH,
                'credential_id' => $refreshToken->token_id,
                'session_id' => $session->session_id,
            ]);

            return $this->responses->failure(
                'authentication.csrf_failed',
                'Refresh CSRF validation failed',
                Response::HTTP_FORBIDDEN,
            );
        }

        $request->attributes->set(self::SESSION_ATTRIBUTE, $session);

        return $next($request);
    }
}
