<?php

namespace Wncms\Api\V2\Contracts;

interface IdempotencyStore
{
    /**
     * Retrieve a completed response record for an idempotency scope.
     *
     * @param  string  $scope
     *
     * @return array|null
     */
    public function get(string $scope): ?array;

    /**
     * Store a completed response record for an idempotency scope.
     *
     * @param  string  $scope
     * @param  array  $record
     * @param  int  $ttlSeconds
     *
     * @return void
     */
    public function put(string $scope, array $record, int $ttlSeconds): void;

    /**
     * Create an atomic lock for an idempotency scope.
     *
     * @param  string  $scope
     * @param  int  $seconds
     *
     * @return \Illuminate\Contracts\Cache\Lock
     */
    public function lock(string $scope, int $seconds): \Illuminate\Contracts\Cache\Lock;
}
