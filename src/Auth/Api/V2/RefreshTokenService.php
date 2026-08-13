<?php

namespace Wncms\Auth\Api\V2;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Wncms\Api\V2\ApiContractRegistry;
use Wncms\Models\ApiRefreshToken;
use Wncms\Models\ApiSession;
use Wncms\Models\User;
use Wncms\Services\Security\SecurityEventService;

final class RefreshTokenService
{
    /**
     * Create the refresh-token lifecycle service.
     */
    public function __construct(
        private TokenHasher $hasher,
        private AccessTokenService $accessTokens,
        private RefreshTokenConsumer $consumer,
        private SecurityEventService $events,
        private ApiContractRegistry $contracts,
    ) {}

    /**
     * Issue the initial one-time refresh credential for an active session.
     *
     * The caller owns the surrounding audited transaction used for login.
     */
    public function issue(ApiSession $session): IssuedRefreshToken
    {
        return $this->issueForFamily($session, (string) Str::ulid(), null);
    }

    /**
     * Atomically consume one refresh token and issue a replacement credential pair.
     *
     * A lost conditional update is handled as replay and revokes only this session family.
     *
     * @param  \Wncms\Auth\Api\V2\ApiCredential  $credential
     * @param  callable(\Wncms\Auth\Api\V2\RotatedCredentialPair, \Wncms\Models\ApiSession): void|null  $beforeSuccess
     *
     * @return \Wncms\Auth\Api\V2\RotatedCredentialPair
     *
     * @throws \Wncms\Auth\Api\V2\RefreshTokenException
     */
    public function rotate(ApiCredential $credential, ?callable $beforeSuccess = null): RotatedCredentialPair
    {
        $token = $this->resolve($credential);
        $session = $this->activeSession($token);

        if ($token->consumed_at !== null) {
            $this->revokeForReuse($token, $session);
        }

        if ($token->revoked_at !== null || $session->revoked_at !== null) {
            throw new RefreshTokenException('authentication.session_revoked');
        }

        if (($token->expires_at !== null && ! $token->expires_at->isFuture())
            || ($session->expires_at !== null && ! $session->expires_at->isFuture())) {
            throw new RefreshTokenException('authentication.refresh_expired');
        }

        try {
            return $this->events->withinTransaction(function () use ($token, $session, $beforeSuccess): RotatedCredentialPair {
                $now = CarbonImmutable::now('UTC');
                $replacementMaterial = $this->hasher->issue('wncms_rt');
                $refreshModel = wncms()->getModelClass('api_refresh_token');
                $this->consumer->consume(
                    $refreshModel,
                    $token->getKey(),
                    $replacementMaterial['public_id'],
                    $now,
                );

                $refresh = $this->persistIssued(
                    $session,
                    (string) $token->family_id,
                    (string) $token->token_id,
                    $replacementMaterial,
                );
                $user = $this->activeUser($token->user_id);
                $access = $this->accessTokens->issue(
                    $user,
                    $session,
                    $this->interactiveAbilities(),
                    $this->websiteIds($user),
                );

                $pair = new RotatedCredentialPair($access, $refresh);
                if ($beforeSuccess !== null) {
                    $beforeSuccess($pair, $session);
                }

                return $pair;
            }, [
                'type' => 'auth.refresh.succeeded',
                'severity' => 'info',
                'outcome' => 'succeeded',
                'context' => [
                    'surface' => 'api_v2',
                    'actor_type' => wncms()->getModelClass('user'),
                    'actor_id' => $token->user_id,
                    'credential_type' => ApiCredential::TYPE_REFRESH,
                    'credential_id' => $token->token_id,
                    'session_id' => $session->session_id,
                ],
            ]);
        } catch (RefreshTokenReuseException $exception) {
            $token->refresh();
            $this->revokeForReuse($token, $session);
        }
    }

    /**
     * Resolve a correctly hashed refresh credential's session for idempotent logout.
     *
     * Revoked and consumed credentials remain resolvable for logout only.
     *
     * @param  \Wncms\Auth\Api\V2\ApiCredential  $credential
     *
     * @return \Wncms\Models\ApiSession|null
     */
    public function sessionForLogout(ApiCredential $credential): ?ApiSession
    {
        try {
            $token = $this->resolve($credential);
        } catch (RefreshTokenException $exception) {
            return null;
        }

        $sessionModel = wncms()->getModelClass('api_session');
        $session = $sessionModel::query()->whereKey($token->session_id)->where('user_id', $token->user_id)->first();

        return $session instanceof $sessionModel ? $session : null;
    }

    /**
     * Resolve a correctly hashed refresh credential for its Cookie CSRF guard.
     *
     * Consumed credentials remain resolvable so a valid old proof can reach replay
     * detection, while arbitrary proof values still fail before rotation.
     *
     * @param  \Wncms\Auth\Api\V2\ApiCredential  $credential
     *
     * @return \Wncms\Models\ApiRefreshToken|null
     */
    public function refreshTokenForGuard(ApiCredential $credential): ?ApiRefreshToken
    {
        try {
            return $this->resolve($credential);
        } catch (RefreshTokenException $exception) {
            return null;
        }
    }

    /**
     * Resolve and verify one typed refresh credential.
     *
     *
     * @throws \Wncms\Auth\Api\V2\RefreshTokenException
     */
    private function resolve(ApiCredential $credential): ApiRefreshToken
    {
        if ($credential->type() !== ApiCredential::TYPE_REFRESH || $credential->publicId() === null) {
            throw new RefreshTokenException('authentication.refresh_invalid');
        }

        $refreshModel = wncms()->getModelClass('api_refresh_token');
        $token = $refreshModel::query()->where('token_id', $credential->publicId())->first();
        if (! $token instanceof $refreshModel
            || ! $this->hasher->matches($credential->plainText(), (string) $token->token_hash)) {
            throw new RefreshTokenException('authentication.refresh_invalid');
        }

        return $token;
    }

    /**
     * Resolve the token's exact session.
     *
     *
     * @throws \Wncms\Auth\Api\V2\RefreshTokenException
     */
    private function activeSession(ApiRefreshToken $token): ApiSession
    {
        $sessionModel = wncms()->getModelClass('api_session');
        $session = $sessionModel::query()->whereKey($token->session_id)->where('user_id', $token->user_id)->first();
        if (! $session instanceof $sessionModel
            || $session->refresh_transport !== AuthSecurityConfig::fromRuntime()->refreshTransport()) {
            throw new RefreshTokenException('authentication.refresh_invalid');
        }

        return $session;
    }

    /**
     * Revoke the replayed family/session atomically, then throw the typed reuse failure.
     */
    private function revokeForReuse(ApiRefreshToken $token, ApiSession $session): never
    {
        $this->events->withinTransaction(function () use ($session): void {
            $now = CarbonImmutable::now('UTC');
            $sessionModel = wncms()->getModelClass('api_session');
            $sessionModel::query()->whereKey($session->getKey())->whereNull('revoked_at')->update([
                'revoked_at' => $now,
                'revocation_reason' => 'refresh_reuse',
                'updated_at' => $now,
            ]);
            $this->revokeSessionCredentials($session, $now);
        }, [
            'type' => 'auth.refresh.reuse_detected',
            'severity' => 'critical',
            'outcome' => 'denied',
            'context' => [
                'surface' => 'api_v2',
                'actor_type' => wncms()->getModelClass('user'),
                'actor_id' => $token->user_id,
                'credential_type' => ApiCredential::TYPE_REFRESH,
                'credential_id' => $token->token_id,
                'session_id' => $session->session_id,
                'error_code' => 'authentication.refresh_reuse_detected',
                'http_status' => 401,
                'context' => ['reason' => 'refresh_reuse'],
            ],
        ]);

        throw new RefreshTokenReuseException;
    }

    /**
     * Revoke access and refresh credentials belonging only to one session.
     */
    private function revokeSessionCredentials(ApiSession $session, CarbonImmutable $now): void
    {
        $accessModel = wncms()->getModelClass('api_access_token');
        $refreshModel = wncms()->getModelClass('api_refresh_token');
        $accessModel::query()->where('session_id', $session->getKey())->whereNull('revoked_at')->update([
            'revoked_at' => $now,
            'updated_at' => $now,
        ]);
        $refreshModel::query()->where('session_id', $session->getKey())->whereNull('revoked_at')->update([
            'revoked_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * Persist one issued refresh token from pre-generated material.
     *
     * @param  array{plain_text: string, public_id: string, hash: string}  $material
     */
    private function persistIssued(ApiSession $session, string $familyId, ?string $parentTokenId, array $material): IssuedRefreshToken
    {
        $expiresAt = $session->expires_at === null
            ? null
            : CarbonImmutable::instance($session->expires_at);
        $refreshModel = wncms()->getModelClass('api_refresh_token');
        $model = $refreshModel::create([
            'token_id' => $material['public_id'],
            'token_hash' => $material['hash'],
            'user_id' => $session->user_id,
            'session_id' => $session->getKey(),
            'family_id' => $familyId,
            'parent_token_id' => $parentTokenId,
            'expires_at' => $expiresAt,
        ]);

        return new IssuedRefreshToken($material['plain_text'], $expiresAt, $model);
    }

    /**
     * Issue refresh material for one exact family.
     */
    private function issueForFamily(ApiSession $session, string $familyId, ?string $parentTokenId): IssuedRefreshToken
    {
        if ($session->revoked_at !== null
            || ($session->expires_at !== null && ! $session->expires_at->isFuture())) {
            throw new RefreshTokenException('authentication.session_revoked');
        }

        return $this->persistIssued($session, $familyId, $parentTokenId, $this->hasher->issue('wncms_rt'));
    }

    /**
     * Resolve one active account.
     */
    private function activeUser(mixed $userId): User
    {
        $userModel = wncms()->getModelClass('user');
        $user = $userModel::query()->find($userId);
        if (! $user instanceof $userModel || (method_exists($user, 'hasRole') && $user->hasRole('suspended'))) {
            throw new RefreshTokenException('authentication.refresh_invalid');
        }

        return $user;
    }

    /**
     * Return the explicit current operation ability catalog for interactive access.
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
     * @return array<int, int>
     */
    private function websiteIds(User $user): array
    {
        return array_map('intval', $user->websites()->pluck('websites.id')->all());
    }
}
