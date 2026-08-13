<?php

namespace Wncms\Auth\Api\V2;

use Carbon\CarbonImmutable;
use Illuminate\Auth\AuthenticationException;
use Wncms\Models\ApiAccessToken;
use Wncms\Models\ApiSession;
use Wncms\Models\User;

final class AccessTokenService
{
    /**
     * Create the access-token service.
     *
     * @param  \Wncms\Auth\Api\V2\TokenHasher  $hasher
     */
    public function __construct(private TokenHasher $hasher)
    {
    }

    /**
     * Issue one short-lived interactive access token.
     *
     * Plaintext is returned only at this response boundary; persistence contains its SHA-256 hash.
     *
     * @param  \Wncms\Models\User  $user
     * @param  \Wncms\Models\ApiSession  $session
     * @param  array<int, string>  $abilities
     * @param  array<int, int|string>  $websiteIds
     * @return array{token: string, expires_at: \Carbon\CarbonImmutable, model: \Wncms\Models\ApiAccessToken}
     */
    public function issue(User $user, ApiSession $session, array $abilities, array $websiteIds): array
    {
        if ((string) $session->user_id !== (string) $user->getKey()) {
            throw new AuthenticationException('authentication.invalid_token');
        }

        if ($session->revoked_at !== null) {
            throw new AuthenticationException('authentication.token_revoked');
        }

        if ($session->expires_at !== null && ! $session->expires_at->isFuture()) {
            throw new AuthenticationException('authentication.invalid_token');
        }

        $material = $this->hasher->issue('wncms_at');
        $expiresAt = CarbonImmutable::now('UTC')->addMinutes(
            (int) config('wncms.auth_security.access_token_lifetime_minutes', 15)
        );
        $modelClass = wncms()->getModelClass('api_access_token');
        $model = $modelClass::create([
            'token_id' => $material['public_id'],
            'token_hash' => $material['hash'],
            'user_id' => $user->getKey(),
            'session_id' => $session->getKey(),
            'abilities' => $this->normalizeAbilities($abilities),
            'website_ids' => $this->normalizeWebsiteIds($websiteIds),
            'expires_at' => $expiresAt,
        ]);

        return [
            'token' => $material['plain_text'],
            'expires_at' => $expiresAt,
            'model' => $model,
        ];
    }

    /**
     * Authenticate one strictly typed interactive access credential.
     *
     * @param  \Wncms\Auth\Api\V2\ApiCredential  $credential
     * @return \Wncms\Auth\Api\V2\AuthenticationContext
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function authenticate(ApiCredential $credential): AuthenticationContext
    {
        if ($credential->type() !== ApiCredential::TYPE_INTERACTIVE_ACCESS || $credential->publicId() === null) {
            throw new AuthenticationException('authentication.invalid_token');
        }

        $modelClass = wncms()->getModelClass('api_access_token');
        $token = $modelClass::query()->where('token_id', $credential->publicId())->first();
        if (! $token instanceof $modelClass || ! $this->hasher->matches($credential->plainText(), (string) $token->token_hash)) {
            throw new AuthenticationException('authentication.invalid_token');
        }

        if ($token->revoked_at !== null) {
            throw new AuthenticationException('authentication.token_revoked');
        }

        if ($token->expires_at === null || ! $token->expires_at->isFuture()) {
            throw new AuthenticationException('authentication.access_token_expired');
        }

        $userModel = wncms()->getModelClass('user');
        $user = $userModel::query()->find($token->user_id);
        if (! $user instanceof $userModel || $this->userIsDisabled($user)) {
            throw new AuthenticationException('authentication.invalid_token');
        }

        $sessionModel = wncms()->getModelClass('api_session');
        $session = $sessionModel::query()
            ->whereKey($token->session_id)
            ->where('user_id', $user->getKey())
            ->first();
        if (! $session instanceof $sessionModel) {
            throw new AuthenticationException('authentication.invalid_token');
        }

        if ($session->revoked_at !== null) {
            throw new AuthenticationException('authentication.token_revoked');
        }

        if ($session->expires_at !== null && ! $session->expires_at->isFuture()) {
            throw new AuthenticationException('authentication.invalid_token');
        }

        return new AuthenticationContext(
            $user,
            ApiCredential::TYPE_INTERACTIVE_ACCESS,
            (string) $token->token_id,
            (string) $session->session_id,
            $this->normalizeAbilities((array) $token->abilities),
            $this->normalizeWebsiteIds((array) $token->website_ids),
        );
    }

    /**
     * Determine whether an actor is disabled by the current WNCMS account policy.
     *
     * @param  \Wncms\Models\User  $user
     * @return bool
     */
    private function userIsDisabled(User $user): bool
    {
        return method_exists($user, 'hasRole') && $user->hasRole('suspended');
    }

    /**
     * Normalize exact credential abilities without granting defaults.
     *
     * @param  array<int, mixed>  $abilities
     * @return array<int, string>
     */
    private function normalizeAbilities(array $abilities): array
    {
        $abilities = array_map(static fn (mixed $ability): string => trim((string) $ability), $abilities);

        return array_values(array_unique(array_filter($abilities, static fn (string $ability): bool => $ability !== '')));
    }

    /**
     * Normalize website scope to stable positive database identifiers.
     *
     * @param  array<int, mixed>  $websiteIds
     * @return array<int, int>
     */
    private function normalizeWebsiteIds(array $websiteIds): array
    {
        $websiteIds = array_map(static fn (mixed $websiteId): int => (int) $websiteId, $websiteIds);

        return array_values(array_unique(array_filter($websiteIds, static fn (int $websiteId): bool => $websiteId > 0)));
    }
}
