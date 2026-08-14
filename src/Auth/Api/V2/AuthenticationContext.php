<?php

namespace Wncms\Auth\Api\V2;

use Illuminate\Contracts\Auth\Authenticatable;

final readonly class AuthenticationContext
{
    private int|string|null $actorId;

    /**
     * Create immutable request authentication state for authorization, audit, and idempotency consumers.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $actor
     * @param  string  $credentialType
     * @param  string|null  $credentialPublicId
     * @param  string|null  $sessionPublicId
     * @param  array<int, string>  $abilities
     * @param  array<int, int|string>  $websiteIds
     */
    public function __construct(
        private Authenticatable $actor,
        private string $credentialType,
        private ?string $credentialPublicId,
        private ?string $sessionPublicId,
        private array $abilities,
        private array $websiteIds,
    ) {
        $identifier = $actor->getAuthIdentifier();
        $this->actorId = is_int($identifier) || is_string($identifier) ? $identifier : null;
    }

    /**
     * Return the authenticated actor.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable
     */
    public function actor(): Authenticatable
    {
        return $this->actor;
    }

    /**
     * Return the authenticated actor identifier for audit and idempotency ownership.
     *
     * @return int|string|null
     */
    public function actorId(): int|string|null
    {
        return $this->actorId;
    }

    /**
     * Return the type of credential authenticated for this request.
     *
     * @return string
     */
    public function credentialType(): string
    {
        return $this->credentialType;
    }

    /**
     * Return the authenticated credential public identifier when available.
     *
     * @return string|null
     */
    public function credentialPublicId(): ?string
    {
        return $this->credentialPublicId;
    }

    /**
     * Return the interactive session public identifier when available.
     *
     * @return string|null
     */
    public function sessionPublicId(): ?string
    {
        return $this->sessionPublicId;
    }

    /**
     * Return the credential ability ceiling for this request.
     *
     * @return array<int, string>
     */
    public function abilities(): array
    {
        return $this->abilities;
    }

    /**
     * Return the stable website identifiers allowed by this credential.
     *
     * @return array<int, int|string>
     */
    public function websiteIds(): array
    {
        return $this->websiteIds;
    }

    /**
     * Determine whether the credential grants an exact ability.
     *
     * @param  string  $ability
     * @return bool
     */
    public function hasAbility(string $ability): bool
    {
        return in_array('*', $this->abilities(), true) || in_array($ability, $this->abilities(), true);
    }

    /**
     * Determine whether the credential grants access to an exact stable website identifier.
     *
     * @param  int|string  $websiteId
     * @return bool
     */
    public function hasWebsite(int|string $websiteId): bool
    {
        return in_array($websiteId, $this->websiteIds(), true);
    }
}
