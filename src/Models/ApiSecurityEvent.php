<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Builder;

class ApiSecurityEvent extends BaseModel
{
    public static $modelKey = 'api_security_event';

    protected $guarded = [];

    protected $hidden = [
        'ip_hash',
        'login_identifier_hash',
        'user_agent_hash',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'website_ids' => 'array',
        'context' => 'array',
        'http_status' => 'integer',
    ];

    /**
     * Scope security events from a supplied UTC timestamp onwards.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \DateTimeInterface  $occurredAt
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOccurredSince(Builder $query, \DateTimeInterface $occurredAt): Builder
    {
        return $query->where('occurred_at', '>=', $occurredAt);
    }
}
