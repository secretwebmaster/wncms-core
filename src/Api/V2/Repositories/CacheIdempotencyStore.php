<?php

namespace Wncms\Api\V2\Repositories;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\DatabaseStore;
use Illuminate\Cache\DynamoDbStore;
use Illuminate\Cache\FailoverStore;
use Illuminate\Cache\MemcachedStore;
use Illuminate\Cache\NullStore;
use Illuminate\Cache\RedisStore;
use Illuminate\Contracts\Cache\Factory;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository;
use Wncms\Api\V2\Contracts\IdempotencyStore;

class CacheIdempotencyStore implements IdempotencyStore
{
    protected const RECORD_PREFIX = 'wncms:api-v2:idempotency:record:';

    protected const LOCK_PREFIX = 'wncms:api-v2:idempotency:lock:';

    protected const DEFAULT_SHARED_STORE_CLASSES = [
        RedisStore::class,
        MemcachedStore::class,
        DatabaseStore::class,
    ];

    protected ?Repository $repository = null;

    protected ?LockProvider $lockProvider = null;

    /**
     * Create the cache-backed idempotency store.
     *
     * @param  \Illuminate\Contracts\Cache\Factory  $cache
     * @param  mixed  $storeName
     * @param  bool  $requireSharedStore
     * @param  mixed  $allowedSharedStoreClasses
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(
        protected Factory $cache,
        protected mixed $storeName = null,
        protected bool $requireSharedStore = false,
        protected mixed $allowedSharedStoreClasses = null
    ) {
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
        $record = $this->repository()->get($this->recordKey($scope));

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
        if ($this->repository()->put($this->recordKey($scope), $record, $ttlSeconds) !== true) {
            throw new \RuntimeException('Unable to persist the API v2 idempotency record');
        }
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
        $this->repository();
        if (! $this->lockProvider instanceof LockProvider) {
            throw new \RuntimeException('The API v2 idempotency lock provider is unavailable');
        }

        return $this->lockProvider->lock($this->lockKey($scope), $seconds);
    }

    /**
     * Resolve and validate the configured cache repository on first use.
     *
     * Lazy resolution keeps configuration failures inside request middleware error normalization.
     *
     * @return \Illuminate\Contracts\Cache\Repository
     */
    protected function repository(): Repository
    {
        if ($this->repository instanceof Repository) {
            return $this->repository;
        }

        if ($this->storeName !== null && (! is_string($this->storeName) || trim($this->storeName) === '')) {
            throw new \InvalidArgumentException(
                'wncms-api-v2.idempotency.store must be a cache store name or null'
            );
        }

        $storeName = $this->storeName === null ? null : trim($this->storeName);
        $repository = $this->cache->store($storeName);
        $store = $repository->getStore();

        if (! $store instanceof LockProvider) {
            throw new \InvalidArgumentException('The API v2 idempotency cache store must support atomic locks');
        }

        if ($this->requireSharedStore) {
            $storeClass = $store::class;
            if (
                $store instanceof ArrayStore
                || $store instanceof NullStore
                || $store instanceof FailoverStore
                || $store instanceof DynamoDbStore
                || ! in_array($storeClass, $this->sharedStoreClasses(), true)
            ) {
                throw new \InvalidArgumentException(
                    'The API v2 idempotency cache store must be an explicitly trusted shared store'
                );
            }
        }

        $this->repository = $repository;
        $this->lockProvider = $store;

        return $this->repository;
    }

    /**
     * Resolve the exact cache-store classes trusted for production idempotency state.
     *
     * @return array<int, class-string>
     *
     * @throws \InvalidArgumentException
     */
    protected function sharedStoreClasses(): array
    {
        $classes = $this->allowedSharedStoreClasses;
        if ($classes === null) {
            $classes = array_values(array_filter(
                self::DEFAULT_SHARED_STORE_CLASSES,
                static fn (string $class): bool => class_exists($class)
            ));
        }

        if (! is_array($classes) || ! array_is_list($classes)) {
            throw new \InvalidArgumentException(
                'wncms-api-v2.idempotency.allowed_shared_store_classes must be a list of exact class names'
            );
        }

        foreach ($classes as $class) {
            if (! is_string($class) || trim($class) === '') {
                throw new \InvalidArgumentException(
                    'wncms-api-v2.idempotency.allowed_shared_store_classes must contain exact class names'
                );
            }
        }

        return array_values(array_unique($classes));
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
