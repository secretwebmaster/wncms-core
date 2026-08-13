<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Builder;

class ApiRefreshToken extends BaseModel
{
    public static $modelKey = 'api_refresh_token';

    protected $guarded = [];

    protected $hidden = [
        'token_hash',
    ];

    protected $casts = [
        'consumed_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    /**
     * Scope refresh tokens that are unconsumed and usable for one rotation.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereNull('consumed_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now());
    }
}
