<?php

namespace Wncms\Auth\Api\V2;

final readonly class RotatedCredentialPair
{
    /**
     * Create an immutable access/refresh rotation result.
     *
     * @param  array{token: string, expires_at: \Carbon\CarbonImmutable, model: \Wncms\Models\ApiAccessToken}  $access
     * @param  \Wncms\Auth\Api\V2\IssuedRefreshToken  $refresh
     */
    public function __construct(
        public array $access,
        public IssuedRefreshToken $refresh,
    ) {
    }
}
