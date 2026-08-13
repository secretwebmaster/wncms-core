<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Builder;
use Wncms\Services\Security\SecurityEventMutationScope;

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

    protected ?SecurityEventMutationScope $mutationScope = null;

    /**
     * Register append-only guards for ordinary event mutations.
     *
     * Aggregate counters and retention pruning use instance-scoped capabilities that are
     * cleared immediately after their one permitted operation.
     *
     * @return void
     */
    protected static function booted(): void
    {
        parent::booted();

        static::updating(function (self $event): void {
            $event->assertMutationAllowed('aggregate');
        });
        static::deleting(function (self $event): void {
            $event->assertMutationAllowed('retention');
        });
    }

    /**
     * Update an aggregate counter through a one-operation service capability.
     *
     * @param  array  $attributes
     * @param  \Wncms\Services\Security\SecurityEventMutationScope  $scope
     *
     * @return bool
     */
    public function updateAggregate(array $attributes, SecurityEventMutationScope $scope): bool
    {
        return $this->withMutationScope($scope, 'aggregate', fn(): bool => $this->update($attributes));
    }

    /**
     * Delete an expired event through a one-operation retention capability.
     *
     * @param  \Wncms\Services\Security\SecurityEventMutationScope  $scope
     *
     * @return bool|null
     */
    public function deleteForRetention(SecurityEventMutationScope $scope): ?bool
    {
        return $this->withMutationScope($scope, 'retention', fn(): ?bool => $this->delete());
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

    /**
     * Execute one explicitly scoped append-only exception.
     *
     * @param  \Wncms\Services\Security\SecurityEventMutationScope  $scope
     * @param  string  $purpose
     * @param  callable  $callback
     *
     * @return mixed
     */
    protected function withMutationScope(SecurityEventMutationScope $scope, string $purpose, callable $callback): mixed
    {
        if (!$scope->permits($purpose)) {
            throw new \LogicException('Security event mutation scope is invalid.');
        }

        $this->mutationScope = $scope;

        try {
            return $callback();
        } finally {
            $this->mutationScope = null;
        }
    }

    /**
     * Reject an ordinary mutation outside its narrow service scope.
     *
     * @param  string  $purpose
     *
     * @return void
     */
    protected function assertMutationAllowed(string $purpose): void
    {
        if ($this->mutationScope === null || !$this->mutationScope->permits($purpose)) {
            throw new \LogicException('Security events are append-only.');
        }
    }
}
