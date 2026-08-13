<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Builder;

class ApiAccessToken extends BaseModel
{
    public static $modelKey = 'api_access_token';

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
     * Scope access tokens that can still authenticate requests.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at')->where('expires_at', '>', now());
    }
}
