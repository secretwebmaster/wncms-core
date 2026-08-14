<?php

namespace Wncms\Http\Middleware;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Wncms\Api\V2\ApiResponseFactory;
use Wncms\Api\V2\ApiContractRegistry;
use Wncms\Auth\Api\V2\AccessTokenService;
use Wncms\Auth\Api\V2\ApiCredential;
use Wncms\Auth\Api\V2\AuthenticationContext;
use Wncms\Auth\Api\V2\CredentialParser;
use Wncms\Auth\Api\V2\TokenHasher;
use Wncms\Models\ApiServiceToken;
use Wncms\Models\User;

class ApiV2TokenAuth
{
    public const AUTH_CONTEXT_ATTRIBUTE = 'wncms_api_v2_auth_context';

    /**
     * Create the API v2 credential authentication middleware.
     *
     * @param  \Wncms\Api\V2\ApiResponseFactory  $responses
     * @param  \Wncms\Auth\Api\V2\CredentialParser  $parser
     * @param  \Wncms\Auth\Api\V2\AccessTokenService  $accessTokens
     * @param  \Wncms\Auth\Api\V2\TokenHasher  $hasher
     */
    public function __construct(
        protected ApiResponseFactory $responses,
        private CredentialParser $parser,
        private AccessTokenService $accessTokens,
        private TokenHasher $hasher,
        private ApiContractRegistry $contracts,
        private \Wncms\Auth\Api\V2\LegacyPersonalTokenAuthenticator $legacyTokens,
    ) {
    }

    /**
     * Authenticate a strictly typed API v2 bearer credential.
     *
     * Request-body `api_token` is intentionally rejected and recognized WNCMS prefixes never fall back.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $bearer = trim((string) $request->bearerToken());
        if ($bearer === '') {
            return $this->unauthorized('authentication.missing_token', 'Missing bearer token');
        }

        $credential = $this->parser->parse($bearer);

        try {
            $context = match ($credential->type()) {
                ApiCredential::TYPE_INTERACTIVE_ACCESS => $this->accessTokens->authenticate($credential),
                ApiCredential::TYPE_SERVICE_TOKEN => $this->authenticateServiceToken($credential),
                ApiCredential::TYPE_LEGACY_PERSONAL_ACCESS_TOKEN => $this->legacyTokens->authenticate($credential, $this->operation($request)),
                default => throw new AuthenticationException('authentication.invalid_token'),
            };
        } catch (AuthenticationException $exception) {
            $errorCode = str_starts_with($exception->getMessage(), 'authentication.')
                ? $exception->getMessage()
                : 'authentication.invalid_token';

            return $this->unauthorized($errorCode, 'Invalid bearer token');
        }

        $request->attributes->set(self::AUTH_CONTEXT_ATTRIBUTE, $context);
        auth()->setUser($context->actor());

        return $next($request);
    }

    /**
     * Authenticate an already-issued service token without implementing its Task 6 lifecycle.
     *
     * @param  \Wncms\Auth\Api\V2\ApiCredential  $credential
     * @return \Wncms\Auth\Api\V2\AuthenticationContext
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    private function authenticateServiceToken(ApiCredential $credential): AuthenticationContext
    {
        if ($credential->publicId() === null) {
            throw new AuthenticationException('authentication.invalid_token');
        }

        $modelClass = wncms()->getModelClass('api_service_token');
        $token = $modelClass::query()->where('token_id', $credential->publicId())->first();
        if (! $token instanceof ApiServiceToken || ! $this->hasher->matches($credential->plainText(), (string) $token->token_hash)) {
            throw new AuthenticationException('authentication.invalid_token');
        }

        if ($token->revoked_at !== null) {
            throw new AuthenticationException('authentication.token_revoked');
        }

        if ($token->expires_at !== null && ! $token->expires_at->isFuture()) {
            throw new AuthenticationException('authentication.access_token_expired');
        }

        $user = $this->activeUser($token->user_id);

        $context = new AuthenticationContext(
            $user,
            ApiCredential::TYPE_SERVICE_TOKEN,
            (string) $token->token_id,
            null,
            (array) $token->abilities,
            array_map('intval', (array) $token->website_ids),
        );

        $this->touchServiceToken($token);

        return $context;
    }

    /** Debounce best-effort service-token usage metadata to one write per five minutes. */
    private function touchServiceToken(ApiServiceToken $token): void
    {
        try {
            $now = CarbonImmutable::now('UTC');
            $modelClass = wncms()->getModelClass('api_service_token');
            $modelClass::query()
                ->whereKey($token->getKey())
                ->where(function ($query) use ($now): void {
                    $query->whereNull('last_used_at')->orWhere('last_used_at', '<=', $now->subMinutes(5));
                })
                ->update(['last_used_at' => $now, 'updated_at' => $now]);
        } catch (\Throwable $exception) {
            Log::warning('WNCMS service-token activity metadata could not be updated.', [
                'credential_id' => $token->token_id,
                'exception' => $exception::class,
            ]);
        }
    }

    /** Resolve the exact formal operation before a legacy credential is considered. */
    private function operation(Request $request): \Wncms\Api\V2\Data\ApiOperationContract
    {
        $route = $request->route();
        $id = $route instanceof \Illuminate\Routing\Route ? (string) ($route->defaults['api_operation_id'] ?? '') : '';
        $operation = $this->contracts->operation($id);
        if ($operation === null) throw new AuthenticationException('authentication.invalid_token');

        return $operation;
    }

    /**
     * Resolve one active WNCMS actor.
     *
     * @param  mixed  $userId
     * @return \Wncms\Models\User
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    private function activeUser(mixed $userId): User
    {
        $userModel = wncms()->getModelClass('user');
        $user = $userModel::query()->find($userId);
        if (! $user instanceof User || (method_exists($user, 'hasRole') && $user->hasRole('suspended'))) {
            throw new AuthenticationException('authentication.invalid_token');
        }

        return $user;
    }

    /**
     * Build a token authentication failure response.
     *
     * @param  string  $errorCode
     * @param  string  $message
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function unauthorized(string $errorCode, string $message): Response
    {
        return $this->responses->failure($errorCode, $message, Response::HTTP_UNAUTHORIZED);
    }
}
