<?php

namespace Wncms\Auth\Api\V2;

use Carbon\CarbonImmutable;
use Wncms\Models\ApiRefreshToken;

final readonly class IssuedRefreshToken
{
    /**
     * Create an immutable refresh-token issuance result.
     *
     * @param  string  $token
     * @param  \Carbon\CarbonImmutable|null  $expiresAt
     * @param  \Wncms\Models\ApiRefreshToken  $model
     */
    public function __construct(
        public string $token,
        public ?CarbonImmutable $expiresAt,
        public ApiRefreshToken $model,
    ) {
    }
}
