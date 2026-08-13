<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Builder;

class ApiSession extends BaseModel
{
    public static $modelKey = 'api_session';

    protected $guarded = [];

    protected $hidden = [
        'csrf_hash',
    ];

    protected $casts = [
        'remembered' => 'boolean',
        'last_activity_at' => 'datetime',
        'last_step_up_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    /**
     * Scope sessions that can still authenticate requests.
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
