<?php

namespace Wncms\Auth\Api\V2;

use JsonSerializable;
use LogicException;
use Stringable;

final readonly class ApiCredential implements JsonSerializable, Stringable
{
    public const TYPE_INTERACTIVE_ACCESS = 'interactive_access';

    public const TYPE_REFRESH = 'refresh';

    public const TYPE_SERVICE_TOKEN = 'service_token';

    public const TYPE_LEGACY_PERSONAL_ACCESS_TOKEN = 'legacy_personal_access_token';

    /**
     * Create an immutable parsed API credential.
     *
     * @param  string  $type
     * @param  string|null  $publicId
     * @param  string  $plainText
     * @param  bool  $legacyCandidate
     */
    public function __construct(
        private string $type,
        private ?string $publicId,
        private string $plainText,
        private bool $legacyCandidate = false,
    ) {
    }

    /**
     * Return the resolved credential type.
     *
     * @return string
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * Return the opaque credential public identifier when the format provides one.
     *
     * @return string|null
     */
    public function publicId(): ?string
    {
        return $this->publicId;
    }

    /**
     * Return the credential plaintext for the hash verification boundary only.
     *
     * @return string
     */
    public function plainText(): string
    {
        return $this->plainText;
    }

    /**
     * Return whether the credential may be considered by the legacy adapter.
     *
     * @return bool
     */
    public function isLegacyCandidate(): bool
    {
        return $this->legacyCandidate;
    }

    /**
     * Return a safe credential representation without plaintext material.
     *
     * @return array{type: string, public_id: string|null, legacy_candidate: bool}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type(),
            'public_id' => $this->publicId(),
            'legacy_candidate' => $this->isLegacyCandidate(),
        ];
    }

    /**
     * Serialize a safe credential representation without plaintext material.
     *
     * @return array{type: string, public_id: string|null, legacy_candidate: bool}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Serialize only safe credential metadata.
     *
     * @return array{type: string, public_id: string|null, legacy_candidate: bool}
     */
    public function __serialize(): array
    {
        return $this->toArray();
    }

    /**
     * Reject credential restoration because serialized data cannot contain plaintext material.
     *
     * @param  array<string, mixed>  $data
     * @return void
     *
     * @throws \LogicException
     */
    public function __unserialize(array $data): void
    {
        throw new LogicException('Serialized API credentials cannot be restored.');
    }

    /**
     * Return a safe string representation without plaintext material.
     *
     * @return string
     */
    public function __toString(): string
    {
        return sprintf('ApiCredential(type=%s, public_id=%s)', $this->type(), $this->publicId() ?? 'none');
    }
}
