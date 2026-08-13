<?php

namespace Wncms\Auth\Api\V2;

use Wncms\Models\Website;

final class WebsiteScopeResolution
{
    /**
     * Create one typed website-scope result.
     *
     * @param  \Wncms\Models\Website|null  $website
     * @param  string|null  $errorCode
     */
    private function __construct(
        private ?Website $website,
        private ?string $errorCode,
    ) {
    }

    /**
     * Create an allowed website-scope result.
     *
     * @param  \Wncms\Models\Website  $website
     * @return self
     */
    public static function allowed(Website $website): self
    {
        return new self($website, null);
    }

    /**
     * Create a rejected website-scope result.
     *
     * @param  string  $errorCode
     * @return self
     */
    public static function rejected(string $errorCode): self
    {
        return new self(null, $errorCode);
    }

    /**
     * Return the authorized website, when present.
     *
     * @return \Wncms\Models\Website|null
     */
    public function website(): ?Website
    {
        return $this->website;
    }

    /**
     * Return the stable rejection reason, when rejected.
     *
     * @return string|null
     */
    public function errorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * Determine whether website scope was authorized.
     *
     * @return bool
     */
    public function isAllowed(): bool
    {
        return $this->website !== null;
    }
}
