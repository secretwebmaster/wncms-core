<?php

namespace Wncms\Http\Controllers\Api\V2\Backend;

use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Wncms\Api\V2\ApiContractRegistry;
use Wncms\Auth\Api\V2\AccessTokenService;
use Wncms\Auth\Api\V2\ApiCredential;
use Wncms\Auth\Api\V2\AuthSecurityConfig;
use Wncms\Auth\Api\V2\AuthenticationContext;
use Wncms\Auth\Api\V2\CredentialParser;
use Wncms\Auth\Api\V2\CsrfTokenService;
use Wncms\Auth\Api\V2\DummyPasswordHasher;
use Wncms\Auth\Api\V2\IssuedRefreshToken;
use Wncms\Auth\Api\V2\LoginThrottleService;
use Wncms\Auth\Api\V2\OriginPolicy;
use Wncms\Auth\Api\V2\RefreshTokenException;
use Wncms\Auth\Api\V2\RefreshTokenService;
use Wncms\Auth\Api\V2\SessionService;
use Wncms\Models\ApiSession;
use Wncms\Models\User;
use Wncms\Services\Security\SecurityEventService;

class AuthController extends ApiV2Controller
{
    /**
     * Create the interactive authentication controller.
     *
     * @param  \Wncms\Auth\Api\V2\AccessTokenService  $accessTokens
     * @param  \Wncms\Auth\Api\V2\RefreshTokenService  $refreshTokens
     * @param  \Wncms\Auth\Api\V2\SessionService  $sessions
     * @param  \Wncms\Auth\Api\V2\CredentialParser  $credentials
     * @param  \Wncms\Auth\Api\V2\DummyPasswordHasher  $dummyPasswords
     * @param  \Wncms\Auth\Api\V2\LoginThrottleService  $loginThrottle
     * @param  \Wncms\Services\Security\SecurityEventService  $events
     * @param  \Wncms\Api\V2\ApiContractRegistry  $contracts
     * @param  \Wncms\Auth\Api\V2\AuthSecurityConfig  $securityConfig
     * @param  \Wncms\Auth\Api\V2\OriginPolicy  $originPolicy
     * @param  \Wncms\Auth\Api\V2\CsrfTokenService  $csrfTokens
     */
    public function __construct(
        private AccessTokenService $accessTokens,
        private RefreshTokenService $refreshTokens,
        private SessionService $sessions,
        private CredentialParser $credentials,
        private DummyPasswordHasher $dummyPasswords,
        private LoginThrottleService $loginThrottle,
        private SecurityEventService $events,
        private ApiContractRegistry $contracts,
        private AuthSecurityConfig $securityConfig,
        private OriginPolicy $originPolicy,
        private CsrfTokenService $csrfTokens,
    ) {
    }

    /**
     * Authenticate an account and atomically issue one JSON interactive session.
     *
     * Unknown accounts execute the same password hash verification and return the same failure.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
            'remember_me' => ['nullable', 'boolean'],
        ]);
        $identifier = mb_strtolower(trim((string) $validated['email']));
        $userModel = wncms()->getModelClass('user');
        $user = $userModel::query()->where('email', $identifier)->first();
        $passwordHash = $user instanceof $userModel ? (string) $user->password : $this->dummyPasswords->material();
        $valid = Hash::check((string) $validated['password'], $passwordHash);

        if (!$valid || !$user instanceof $userModel || $this->userIsDisabled($user)) {
            $this->loginThrottle->recordFailure($identifier);
            $this->recordLoginFailure($request, $identifier);

            return $this->responseFactory()->failure(
                'authentication.invalid_credentials',
                __('auth.failed'),
                Response::HTTP_UNAUTHORIZED,
            );
        }

        $remembered = (bool) ($validated['remember_me'] ?? false)
            && (bool) config('wncms.auth_security.permanent_remember_enabled', false);
        $now = CarbonImmutable::now('UTC');
        $expiresAt = $remembered
            ? null
            : $now->addDays((int) config('wncms.auth_security.refresh_token_lifetime_days', 30));
        $deviceName = trim((string) ($validated['device_name'] ?? 'nextjs-admin')) ?: 'nextjs-admin';
        $sessionId = (string) Str::ulid();
        $transport = $this->securityConfig->refreshTransport();

        try {
            $issued = $this->events->withinTransaction(function () use ($user, $remembered, $expiresAt, $deviceName, $now, $sessionId, $transport): array {
                $sessionModel = wncms()->getModelClass('api_session');
                $session = $sessionModel::create([
                    'session_id' => $sessionId,
                    'user_id' => $user->getKey(),
                    'device_name' => $deviceName,
                    'refresh_transport' => $transport,
                    'remembered' => $remembered,
                    'last_activity_at' => $now,
                    'expires_at' => $expiresAt,
                ]);
                $access = $this->accessTokens->issue(
                    $user,
                    $session,
                    $this->interactiveAbilities(),
                    $this->websiteIds($user),
                );
                $refresh = $this->refreshTokens->issue($session);
                $csrf = $transport === 'cookie' ? $this->csrfTokens->issue($session) : null;

                return compact('session', 'access', 'refresh', 'csrf');
            }, [
                'type' => 'auth.login.succeeded',
                'severity' => 'info',
                'outcome' => 'succeeded',
                'context' => $this->loginEventContext($request, $identifier, $user, $sessionId),
            ]);
        } catch (\Throwable $exception) {
            return $this->securityAuditUnavailable($exception);
        }

        $this->loginThrottle->clearAccount($identifier);

        $response = $this->ok($this->credentialResponse(
            $issued['access'],
            $issued['refresh'],
            $issued['session'],
            $user,
        ), 'login_success');

        return $transport === 'cookie'
            ? $this->withRefreshCookies($response, $issued['refresh'], (string) $issued['csrf'])
            : $response;
    }

    /**
     * Rotate one JSON refresh credential into a new access/refresh pair.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(Request $request): JsonResponse
    {
        $refreshToken = $this->refreshCredential($request);
        if ($refreshToken === null) {
            return $this->responseFactory()->failure(
                'authentication.refresh_invalid',
                'Refresh credential is not valid',
                Response::HTTP_UNAUTHORIZED,
            );
        }

        $credential = $this->credentials->parse($refreshToken);

        try {
            $rotate = function () use ($credential): array {
                $pair = $this->refreshTokens->rotate($credential);
                $sessionModel = wncms()->getModelClass('api_session');
                $userModel = wncms()->getModelClass('user');
                $session = $sessionModel::query()->findOrFail($pair->refresh->model->session_id);
                $user = $userModel::query()->findOrFail($pair->refresh->model->user_id);
                $csrf = $this->securityConfig->refreshTransport() === 'cookie'
                    ? $this->csrfTokens->issue($session)
                    : null;

                return compact('pair', 'session', 'user', 'csrf');
            };
            $rotated = $this->securityConfig->refreshTransport() === 'cookie'
                ? DB::transaction($rotate)
                : $rotate();
            $response = $this->ok($this->credentialResponse(
                $rotated['pair']->access,
                $rotated['pair']->refresh,
                $rotated['session'],
                $rotated['user'],
            ));

            if ($this->securityConfig->refreshTransport() !== 'cookie') {
                return $response;
            }

            return $this->withRefreshCookies(
                $response,
                $rotated['pair']->refresh,
                (string) $rotated['csrf'],
            );
        } catch (RefreshTokenException $exception) {
            $this->recordRefreshFailure($request, $credential, $exception);

            return $this->responseFactory()->failure(
                $exception->errorCode,
                'Refresh credential is not valid',
                $exception->httpStatus,
            );
        } catch (\Throwable $exception) {
            return $this->securityAuditUnavailable($exception);
        }
    }

    /**
     * Idempotently revoke the interactive session identified by a JSON refresh token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $refreshToken = $this->refreshCredential($request);
        if ($refreshToken === null) {
            return $this->responseFactory()->failure(
                'authentication.refresh_invalid',
                'Refresh credential is not valid',
                Response::HTTP_UNAUTHORIZED,
            );
        }

        $credential = $this->credentials->parse($refreshToken);
        $session = $this->refreshTokens->sessionForLogout($credential);

        if ($session instanceof ApiSession) {
            try {
                $this->sessions->logout($session);
            } catch (\Throwable $exception) {
                return $this->securityAuditUnavailable($exception);
            }
        }

        $response = $this->ok(null, 'logout_success');

        return $this->securityConfig->refreshTransport() === 'cookie'
            ? $this->clearRefreshCookies($response)
            : $response;
    }

    /**
     * Revoke every interactive session for the current actor without touching service tokens.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $context = $this->authenticationContext($request);
        if (!$context instanceof AuthenticationContext
            || $context->credentialType() !== ApiCredential::TYPE_INTERACTIVE_ACCESS
            || !$context->actor() instanceof User) {
            return $this->responseFactory()->failure(
                'risk.credential_type_denied',
                'Interactive authentication is required',
                Response::HTTP_FORBIDDEN,
            );
        }

        $sessionModel = wncms()->getModelClass('api_session');
        $currentSession = $sessionModel::query()
            ->where('session_id', $context->sessionPublicId())
            ->where('user_id', $context->actor()->getAuthIdentifier())
            ->first();

        try {
            $count = $this->sessions->revokeAll($context->actor(), $currentSession?->getKey());
        } catch (\Throwable $exception) {
            return $this->securityAuditUnavailable($exception);
        }

        $response = $this->ok(['revoked_sessions' => $count], 'logout_all_success');

        return $this->securityConfig->refreshTransport() === 'cookie'
            ? $this->clearRefreshCookies($response)
            : $response;
    }

    /**
     * Return the current actor and safe interactive-session metadata.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user instanceof User) {
            return $this->responseFactory()->failure(
                'authentication.invalid_token',
                __('wncms::auth.unauthenticated'),
                Response::HTTP_UNAUTHORIZED,
            );
        }

        $context = $this->authenticationContext($request);

        return $this->ok(array_merge($this->userResponse($user), [
            'session' => $context?->sessionPublicId() === null
                ? null
                : ['id' => $context->sessionPublicId()],
        ]));
    }

    /**
     * Return the request-scoped immutable authentication context.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Wncms\Auth\Api\V2\AuthenticationContext|null
     */
    private function authenticationContext(Request $request): ?AuthenticationContext
    {
        $context = $request->attributes->get('wncms_api_v2_auth_context');

        return $context instanceof AuthenticationContext ? $context : null;
    }

    /**
     * Return a complete JSON credential response at its plaintext-once boundary.
     *
     * The `token` alias is a transitional compatibility field containing the same short-lived
     * access credential; it never restores the former PAT behavior.
     *
     * @param  array{token: string, expires_at: \Carbon\CarbonImmutable, model: \Wncms\Models\ApiAccessToken}  $access
     * @param  \Wncms\Auth\Api\V2\IssuedRefreshToken  $refresh
     * @param  \Wncms\Models\ApiSession  $session
     * @param  \Wncms\Models\User  $user
     * @return array<string, mixed>
     */
    private function credentialResponse(array $access, IssuedRefreshToken $refresh, ApiSession $session, User $user): array
    {
        $response = [
            'access_token' => $access['token'],
            'token' => $access['token'],
            'token_type' => 'Bearer',
            'access_expires_at' => $access['expires_at']->toAtomString(),
            'refresh_expires_at' => $refresh->expiresAt?->toAtomString(),
            'session' => $this->sessionResponse($session),
            'user' => $this->userResponse($user),
        ];

        if ($this->securityConfig->refreshTransport() === 'json') {
            $response['refresh_token'] = $refresh->token;
        }

        return $response;
    }

    /**
     * Resolve a refresh credential exclusively from the configured transport.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    private function refreshCredential(Request $request): ?string
    {
        if ($this->securityConfig->refreshTransport() === 'cookie') {
            $token = trim((string) $request->cookie(OriginPolicy::REFRESH_COOKIE, ''));

            return $token === '' ? null : $token;
        }

        $validated = $request->validate(['refresh_token' => ['required', 'string']]);
        $token = trim((string) $validated['refresh_token']);

        return $token === '' ? null : $token;
    }

    /**
     * Attach exact refresh and readable CSRF cookies to a response.
     *
     * @param  \Illuminate\Http\JsonResponse  $response
     * @param  \Wncms\Auth\Api\V2\IssuedRefreshToken  $refresh
     * @param  string  $csrf
     * @return \Illuminate\Http\JsonResponse
     */
    private function withRefreshCookies(JsonResponse $response, IssuedRefreshToken $refresh, string $csrf): JsonResponse
    {
        $options = $this->originPolicy->cookieOptions();
        $expiresAt = $refresh->expiresAt ?? 0;
        $response->headers->setCookie(Cookie::create(
            OriginPolicy::REFRESH_COOKIE,
            $refresh->token,
            $expiresAt,
            $options['path'],
            $options['domain'],
            $options['secure'],
            true,
            false,
            $options['same_site'],
        ));
        $response->headers->setCookie(Cookie::create(
            OriginPolicy::CSRF_COOKIE,
            $csrf,
            $expiresAt,
            $options['path'],
            $options['domain'],
            $options['secure'],
            false,
            false,
            $options['same_site'],
        ));

        return $response;
    }

    /**
     * Expire both Cookie-mode browser credentials with their configured scope.
     *
     * @param  \Illuminate\Http\JsonResponse  $response
     * @return \Illuminate\Http\JsonResponse
     */
    private function clearRefreshCookies(JsonResponse $response): JsonResponse
    {
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
     * Return safe session response metadata.
     *
     * @param  \Wncms\Models\ApiSession  $session
     * @return array<string, mixed>
     */
    private function sessionResponse(ApiSession $session): array
    {
        return [
            'id' => (string) $session->session_id,
            'device_name' => $session->device_name,
            'remembered' => (bool) $session->remembered,
            'refresh_transport' => (string) $session->refresh_transport,
            'expires_at' => $session->expires_at?->toAtomString(),
        ];
    }

    /**
     * Return safe actor response metadata.
     *
     * @param  \Wncms\Models\User  $user
     * @return array<string, mixed>
     */
    private function userResponse(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'roles' => method_exists($user, 'roles') ? $user->roles()->pluck('name')->all() : [],
        ];
    }

    /**
     * Return explicit operation abilities for an interactive credential.
     *
     * @return array<int, string>
     */
    private function interactiveAbilities(): array
    {
        $abilities = array_map(
            static fn ($operation): ?string => $operation->ability,
            $this->contracts->operations(),
        );

        return array_values(array_unique(array_filter($abilities, static fn (?string $ability): bool => $ability !== null)));
    }

    /**
     * Return stable website IDs currently accessible to the actor.
     *
     * @param  \Wncms\Models\User  $user
     * @return array<int, int>
     */
    private function websiteIds(User $user): array
    {
        return array_map('intval', $user->websites()->pluck('websites.id')->all());
    }

    /**
     * Determine whether an actor is disabled by current account policy.
     *
     * @param  \Wncms\Models\User  $user
     * @return bool
     */
    private function userIsDisabled(User $user): bool
    {
        return method_exists($user, 'hasRole') && $user->hasRole('suspended');
    }

    /**
     * Build allowlisted login event context.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $identifier
     * @param  \Wncms\Models\User  $user
     * @param  string  $sessionId
     * @return array<string, mixed>
     */
    private function loginEventContext(Request $request, string $identifier, User $user, string $sessionId): array
    {
        return [
            'surface' => 'api_v2',
            'request_id' => $request->attributes->get('wncms_api_v2_request_id'),
            'actor_type' => $user::class,
            'actor_id' => $user->getKey(),
            'session_id' => $sessionId,
            'ip' => (string) $request->ip(),
            'login_identifier' => $identifier,
            'user_agent' => (string) $request->userAgent(),
        ];
    }

    /**
     * Record a generic login failure without changing the external response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $identifier
     * @return void
     */
    private function recordLoginFailure(Request $request, string $identifier): void
    {
        try {
            $this->events->recordAggregate('auth.login.failed', 'warning', 'denied', [
                'surface' => 'api_v2',
                'request_id' => $request->attributes->get('wncms_api_v2_request_id'),
                'ip' => (string) $request->ip(),
                'login_identifier' => $identifier,
                'user_agent' => (string) $request->userAgent(),
                'error_code' => 'authentication.invalid_credentials',
                'http_status' => 401,
                'context' => ['reason' => 'invalid_credentials'],
            ]);
        } catch (\Throwable $exception) {
            Log::warning('WNCMS login failure event could not be persisted.', [
                'request_id' => $request->attributes->get('wncms_api_v2_request_id'),
                'exception' => $exception::class,
            ]);
        }
    }

    /**
     * Record a non-mutating refresh failure without credential plaintext.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Wncms\Auth\Api\V2\ApiCredential  $credential
     * @param  \Wncms\Auth\Api\V2\RefreshTokenException  $exception
     * @return void
     */
    private function recordRefreshFailure(Request $request, ApiCredential $credential, RefreshTokenException $exception): void
    {
        if ($exception->errorCode === 'authentication.refresh_reuse_detected') {
            return;
        }

        try {
            $this->events->record('auth.refresh.failed', 'warning', 'denied', [
                'surface' => 'api_v2',
                'request_id' => $request->attributes->get('wncms_api_v2_request_id'),
                'credential_type' => ApiCredential::TYPE_REFRESH,
                'credential_id' => $credential->publicId(),
                'error_code' => $exception->errorCode,
                'http_status' => $exception->httpStatus,
                'ip' => (string) $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);
        } catch (\Throwable $eventException) {
            Log::warning('WNCMS refresh failure event could not be persisted.', [
                'request_id' => $request->attributes->get('wncms_api_v2_request_id'),
                'exception' => $eventException::class,
            ]);
        }
    }
}
