<?php

namespace Wncms\Auth\Api\V2;

use Illuminate\Database\Eloquent\Model;

final class WebsiteScopeResolution
{
    /**
     * Create one typed website-scope result.
     */
    private function __construct(
        private ?Model $website,
        private ?string $errorCode,
    ) {}

    /**
     * Create an allowed website-scope result.
     */
    public static function allowed(Model $website): self
    {
        return new self($website, null);
    }

    /**
     * Create a rejected website-scope result.
     */
    public static function rejected(string $errorCode): self
    {
        return new self(null, $errorCode);
    }

    /**
     * Return the authorized website, when present.
     */
    public function website(): ?Model
    {
        return $this->website;
    }

    /**
     * Return the stable rejection reason, when rejected.
     */
    public function errorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * Determine whether website scope was authorized.
     */
    public function isAllowed(): bool
    {
        return $this->website !== null;
    }
}
