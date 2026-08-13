<?php

namespace Wncms\Http\Controllers\Api\V2\Backend;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Wncms\Auth\Api\V2\ApiCredential;
use Wncms\Auth\Api\V2\AuthSecurityConfig;
use Wncms\Auth\Api\V2\AuthenticationContext;
use Wncms\Auth\Api\V2\OriginPolicy;
use Wncms\Auth\Api\V2\SessionService;
use Wncms\Models\ApiSession;
use Wncms\Models\User;

class SessionController extends ApiV2Controller
{
    /**
     * Create the session management controller.
     *
     * @param  \Wncms\Auth\Api\V2\SessionService  $sessions
     * @param  \Wncms\Auth\Api\V2\AuthSecurityConfig  $securityConfig
     * @param  \Wncms\Auth\Api\V2\OriginPolicy  $originPolicy
     */
    public function __construct(
        private SessionService $sessions,
        private AuthSecurityConfig $securityConfig,
        private OriginPolicy $originPolicy,
    ) {
    }

    /**
     * List the current actor's safe interactive-session metadata.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $context = $this->interactiveContext($request);
        if (!$context instanceof AuthenticationContext || !$context->actor() instanceof User) {
            return $this->credentialTypeDenied();
        }

        return $this->ok($this->sessions->listing($context->actor(), $context->sessionPublicId()));
    }

    /**
     * Revoke one session owned by the current actor.
     *
     * Unknown and cross-user public IDs return the same opaque 404 response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $sessionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $sessionId): JsonResponse
    {
        $context = $this->interactiveContext($request);
        if (!$context instanceof AuthenticationContext || !$context->actor() instanceof User) {
            return $this->credentialTypeDenied();
        }

        $sessionModel = wncms()->getModelClass('api_session');
        $session = $sessionModel::query()
            ->where('session_id', $sessionId)
            ->where('user_id', $context->actor()->getKey())
            ->first();
        if (!$session instanceof $sessionModel) {
            return $this->responseFactory()->failure(
                'resource.not_found',
                'Resource not found',
                Response::HTTP_NOT_FOUND,
            );
        }

        try {
            $this->sessions->revoke($session, 'self_service');
        } catch (\Throwable $exception) {
            return $this->securityAuditUnavailable($exception);
        }

        $response = $this->ok(null, 'session_revoked');
        if ($this->securityConfig->refreshTransport() !== 'cookie'
            || !hash_equals((string) $session->session_id, (string) $context->sessionPublicId())) {
            return $response;
        }

        $options = $this->originPolicy->cookieOptions();
        foreach ([OriginPolicy::REFRESH_COOKIE => true, OriginPolicy::CSRF_COOKIE => false] as $name => $httpOnly) {
            $response->headers->setCookie(Cookie::create(
                $name,
                '',
                1,
                $options['path'],
                $options['domain'],
                $options['secure'],
                $httpOnly,
                false,
                $options['same_site'],
            ));
        }

        return $response;
    }

    /**
     * Return an interactive context or null for non-interactive credentials.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Wncms\Auth\Api\V2\AuthenticationContext|null
     */
    private function interactiveContext(Request $request): ?AuthenticationContext
    {
        $context = $request->attributes->get('wncms_api_v2_auth_context');

        return $context instanceof AuthenticationContext
            && $context->credentialType() === ApiCredential::TYPE_INTERACTIVE_ACCESS
            ? $context
            : null;
    }

    /**
     * Return the stable denial for credential types that cannot manage sessions.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    private function credentialTypeDenied(): JsonResponse
    {
        return $this->responseFactory()->failure(
            'risk.credential_type_denied',
            'Interactive authentication is required',
            Response::HTTP_FORBIDDEN,
        );
    }
}
