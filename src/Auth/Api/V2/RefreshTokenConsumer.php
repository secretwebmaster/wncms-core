<?php

namespace Wncms\Auth\Api\V2;

use Carbon\CarbonImmutable;

final class RefreshTokenConsumer
{
    /**
     * Atomically consume one unrevoked refresh row through its resolved model class.
     *
     * @param  class-string<\Wncms\Models\ApiRefreshToken>  $modelClass
     * @param  int|string  $primaryKey
     * @param  string  $replacementTokenId
     * @param  \Carbon\CarbonImmutable  $now
     * @return void
     *
     * @throws \Wncms\Auth\Api\V2\RefreshTokenReuseException
     */
    public function consume(
        string $modelClass,
        int|string $primaryKey,
        string $replacementTokenId,
        CarbonImmutable $now,
    ): void {
        $consumed = $modelClass::query()
            ->whereKey($primaryKey)
            ->whereNull('consumed_at')
            ->whereNull('revoked_at')
            ->update([
                'consumed_at' => $now,
                'replaced_by_token_id' => $replacementTokenId,
                'updated_at' => $now,
            ]);

        if ($consumed !== 1) {
            throw new RefreshTokenReuseException();
        }
    }
}
