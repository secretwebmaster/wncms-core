<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Builder;

class ApiServiceToken extends BaseModel
{
    public static $modelKey = 'api_service_token';

    protected $guarded = [];

    protected $hidden = [
        'token_hash',
    ];

    protected $casts = [
        'abilities' => 'array',
        'website_ids' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    /**
     * Scope service tokens that are not revoked or expired.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereNull('revoked_at')
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }
}
