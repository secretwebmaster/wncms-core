<?php

namespace Wncms\Api\V2\Repositories;

use Illuminate\Contracts\Cache\Factory;
use Wncms\Api\V2\Contracts\IdempotencyStore;

class CacheIdempotencyStore implements IdempotencyStore
{
    protected const RECORD_PREFIX = 'wncms:api-v2:idempotency:record:';

    protected const LOCK_PREFIX = 'wncms:api-v2:idempotency:lock:';

    /**
     * Create the cache-backed idempotency store.
     *
     * @param  \Illuminate\Contracts\Cache\Factory  $cache
     */
    public function __construct(protected Factory $cache)
    {
    }

    /**
     * Retrieve a completed response record for an idempotency scope.
     *
     * @param  string  $scope
     *
     * @return array|null
     */
    public function get(string $scope): ?array
    {
        $record = $this->cache->store()->get($this->recordKey($scope));

        return is_array($record) ? $record : null;
    }

    /**
     * Store a completed response record for an idempotency scope.
     *
     * @param  string  $scope
     * @param  array  $record
     * @param  int  $ttlSeconds
     *
     * @return void
     */
    public function put(string $scope, array $record, int $ttlSeconds): void
    {
        $this->cache->store()->put($this->recordKey($scope), $record, $ttlSeconds);
    }

    /**
     * Create an atomic lock for an idempotency scope.
     *
     * @param  string  $scope
     * @param  int  $seconds
     *
     * @return \Illuminate\Contracts\Cache\Lock
     */
    public function lock(string $scope, int $seconds): \Illuminate\Contracts\Cache\Lock
    {
        return $this->cache->store()->lock($this->lockKey($scope), $seconds);
    }

    /**
     * Build the cache key for a completed response record.
     *
     * @param  string  $scope
     *
     * @return string
     */
    protected function recordKey(string $scope): string
    {
        return self::RECORD_PREFIX.$scope;
    }

    /**
     * Build the cache key for an in-progress mutation lock.
     *
     * @param  string  $scope
     *
     * @return string
     */
    protected function lockKey(string $scope): string
    {
        return self::LOCK_PREFIX.$scope;
    }
}
