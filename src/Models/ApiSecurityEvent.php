<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Builder;
use Wncms\Models\Builders\AppendOnlySecurityEventBuilder;
use Wncms\Models\Builders\AppendOnlySecurityEventQueryBuilder;

class ApiSecurityEvent extends BaseModel
{
    public static $modelKey = 'api_security_event';

    protected $guarded = [];

    protected $hidden = [
        'ip_hash',
        'login_identifier_hash',
        'user_agent_hash',
        'aggregate_key',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'website_ids' => 'array',
        'context' => 'array',
        'http_status' => 'integer',
    ];

    /**
     * Create the query builder that rejects ordinary write operations.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     *
     * @return \Wncms\Models\Builders\AppendOnlySecurityEventBuilder
     */
    public function newEloquentBuilder($query): AppendOnlySecurityEventBuilder
    {
        return new AppendOnlySecurityEventBuilder($query);
    }

    /**
     * Create the base query builder that rejects ordinary event mutations.
     *
     * @return \Wncms\Models\Builders\AppendOnlySecurityEventQueryBuilder
     */
    protected function newBaseQueryBuilder(): AppendOnlySecurityEventQueryBuilder
    {
        return new AppendOnlySecurityEventQueryBuilder($this->getConnection());
    }

    /**
     * Reject timestamp-only model mutations before Laravel can treat them as clean saves.
     *
     * @param  array|string|null  $attribute
     *
     * @return never
     */
    public function touch($attribute = null): never
    {
        throw new \LogicException('Security events are append-only.');
    }

    /**
     * Reject quiet timestamp-only model mutations.
     *
     * @param  array|string|null  $attribute
     *
     * @return never
     */
    public function touchQuietly($attribute = null): never
    {
        throw new \LogicException('Security events are append-only.');
    }

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
