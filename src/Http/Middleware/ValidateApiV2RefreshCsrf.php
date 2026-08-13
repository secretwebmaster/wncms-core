<?php

namespace Wncms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Wncms\Api\V2\ApiResponseFactory;
use Wncms\Auth\Api\V2\AuthSecurityConfig;
use Wncms\Auth\Api\V2\CredentialParser;
use Wncms\Auth\Api\V2\CsrfTokenService;
use Wncms\Auth\Api\V2\OriginPolicy;
use Wncms\Auth\Api\V2\RefreshTokenService;

final class ValidateApiV2RefreshCsrf
{
    public const SESSION_ATTRIBUTE = 'wncms_api_v2_refresh_session';

    /**
     * Create the Cookie refresh CSRF guard.
     *
     * @param  \Wncms\Auth\Api\V2\AuthSecurityConfig  $config
     * @param  \Wncms\Auth\Api\V2\CredentialParser  $credentials
     * @param  \Wncms\Auth\Api\V2\RefreshTokenService  $refreshTokens
     * @param  \Wncms\Auth\Api\V2\CsrfTokenService  $csrf
     * @param  \Wncms\Api\V2\ApiResponseFactory  $responses
     */
    public function __construct(
        private AuthSecurityConfig $config,
        private CredentialParser $credentials,
        private RefreshTokenService $refreshTokens,
        private CsrfTokenService $csrf,
        private ApiResponseFactory $responses,
    ) {
    }

    /**
     * Validate double-submit and session-bound CSRF before refresh rotation or logout.
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

        $refresh = trim((string) $request->cookie(OriginPolicy::REFRESH_COOKIE, ''));
        $session = $refresh === ''
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
                $session,
                (string) $request->cookie(OriginPolicy::CSRF_COOKIE, ''),
                (string) $request->header('X-WNCMS-CSRF', ''),
            );
        } catch (\RuntimeException $exception) {
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
