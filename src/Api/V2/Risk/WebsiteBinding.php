<?php

namespace Wncms\Api\V2\Risk;

final readonly class WebsiteBinding
{
    /**
     * Create one canonical website binding decision.
     *
     * @param  array<int, int>  $websiteIds
     */
    public function __construct(
        public array $websiteIds,
        public bool $supplied,
        public bool $shouldSync,
    ) {}
}
