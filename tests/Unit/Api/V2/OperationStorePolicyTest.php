<?php

namespace Wncms\Tests\Unit\Api\V2;

use Illuminate\Cache\ApcStore;
use Illuminate\Cache\ApcWrapper;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\DatabaseStore;
use Illuminate\Cache\DynamoDbStore;
use Illuminate\Cache\FileStore;
use Illuminate\Cache\MemcachedStore;
use Illuminate\Cache\RedisStore;
use Illuminate\Cache\Repository as IlluminateCacheRepository;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Filesystem\Filesystem;
use Mockery;
use Wncms\Api\V2\Data\AsyncOperation;
use Wncms\Api\V2\Enums\AsyncOperationStatus;
use Wncms\Api\V2\Repositories\CacheOperationRepository;
use Wncms\Tests\TestCase;

class OperationStorePolicyTest extends TestCase
{
    protected const OPERATION_ID = '123e4567-e89b-42d3-a456-426614174080';

    /**
     * Verify production rejects APC because it cannot provide the shared atomic lock contract.
     *
     * @return void
     */
    public function test_production_rejects_apc_operation_store(): void
    {
        $apc = Mockery::mock(ApcWrapper::class);
        $apc->shouldReceive('get')->never();
        $cache = $this->cacheFactory(new IlluminateCacheRepository(new ApcStore($apc)));
        $repository = new CacheOperationRepository($cache, 'apc', true);

        $this->expectException(\InvalidArgumentException::class);
        $repository->find(self::OPERATION_ID);
    }

    /**
     * Verify production rejects an unknown lock-capable store until its exact class is trusted.
     *
     * @return void
     */
    public function test_production_rejects_an_unknown_lock_capable_operation_store(): void
    {
        $cache = $this->cacheFactory(new IlluminateCacheRepository(new CustomSharedOperationStore()));
        $repository = new CacheOperationRepository($cache, 'custom', true);

        $this->expectException(\InvalidArgumentException::class);
        $repository->find(self::OPERATION_ID);
    }

    /**
     * Verify a custom shared lock store is accepted only through exact-class configuration.
     *
     * @return void
     */
    public function test_production_accepts_an_explicitly_allowlisted_custom_shared_store(): void
    {
        $cache = $this->cacheFactory(new IlluminateCacheRepository(new CustomSharedOperationStore()));
        $repository = new CacheOperationRepository(
            $cache,
            'custom',
            true,
            [CustomSharedOperationStore::class]
        );

        $this->assertNull($repository->find(self::OPERATION_ID));
    }

    /**
     * Verify the default production allowlist names Laravel's known shared lock stores exactly.
     *
     * @return void
     */
    public function test_default_shared_store_allowlist_contains_only_known_laravel_store_classes(): void
    {
        $this->assertSame([
            RedisStore::class,
            MemcachedStore::class,
            DatabaseStore::class,
        ], config('wncms-api-v2.operations.allowed_shared_store_classes'));
    }

    /**
     * Verify DynamoDB remains prohibited even when its exact runtime class is explicitly configured.
     *
     * @return void
     */
    public function test_production_rejects_dynamodb_even_when_explicitly_allowlisted(): void
    {
        $store = Mockery::mock(DynamoDbStore::class);
        $store->shouldReceive('get')->andReturn(null);
        $cache = $this->cacheFactory(new IlluminateCacheRepository($store));
        $repository = new CacheOperationRepository($cache, 'dynamodb', true, [$store::class]);

        $this->expectException(\InvalidArgumentException::class);
        $repository->find(self::OPERATION_ID);
    }

    /**
     * Verify FileStore is rejected by default because shared-volume semantics are deployment-specific.
     *
     * @return void
     */
    public function test_production_rejects_file_store_without_exact_configuration(): void
    {
        $store = new FileStore(new Filesystem(), sys_get_temp_dir().'/wncms-operation-store-policy');
        $cache = $this->cacheFactory(new IlluminateCacheRepository($store));
        $repository = new CacheOperationRepository($cache, 'file', true);

        $this->expectException(\InvalidArgumentException::class);
        $repository->find(self::OPERATION_ID);
    }

    /**
     * Verify FileStore can be opted in exactly when operators guarantee one shared filesystem.
     *
     * @return void
     */
    public function test_production_accepts_exact_file_store_opt_in(): void
    {
        $store = new FileStore(new Filesystem(), sys_get_temp_dir().'/wncms-operation-store-policy');
        $cache = $this->cacheFactory(new IlluminateCacheRepository($store));
        $repository = new CacheOperationRepository($cache, 'file', true, [FileStore::class]);

        $this->assertNull($repository->find(self::OPERATION_ID));
    }

    /**
     * Verify save, find, and CAS retain one resolved backend and use that backend's lock provider.
     *
     * @return void
     */
    public function test_repository_uses_one_resolved_backend_for_records_and_atomic_locks(): void
    {
        $store = new CustomSharedOperationStore();
        $cacheRepository = new IlluminateCacheRepository($store);
        $cache = Mockery::mock(CacheFactory::class);
        $cache->shouldReceive('store')->once()->with('custom')->andReturn($cacheRepository);
        $repository = new CacheOperationRepository(
            $cache,
            'custom',
            true,
            [CustomSharedOperationStore::class]
        );
        $queued = $this->operation(AsyncOperationStatus::Queued);
        $repository->save($queued, 86400);
        $expected = $repository->find(self::OPERATION_ID);

        $this->assertTrue($repository->compareAndSwap(
            $expected,
            $this->operation(AsyncOperationStatus::Running),
            86400
        ));
        $this->assertSame(1, $store->lockCount);
        $this->assertSame(AsyncOperationStatus::Running, $repository->find(self::OPERATION_ID)?->status);
    }

    /**
     * Return a cache factory resolving the supplied real repository.
     *
     * @param  \Illuminate\Cache\Repository  $repository
     *
     * @return \Illuminate\Contracts\Cache\Factory
     */
    private function cacheFactory(IlluminateCacheRepository $repository): CacheFactory
    {
        $cache = Mockery::mock(CacheFactory::class);
        $cache->shouldReceive('store')->once()->andReturn($repository);

        return $cache;
    }

    /**
     * Build a valid operation in the requested state.
     *
     * @param  \Wncms\Api\V2\Enums\AsyncOperationStatus  $status
     *
     * @return \Wncms\Api\V2\Data\AsyncOperation
     */
    private function operation(AsyncOperationStatus $status): AsyncOperation
    {
        return new AsyncOperation(
            id: self::OPERATION_ID,
            type: 'plugins.upgrade',
            status: $status,
            progress: $status === AsyncOperationStatus::Running ? 10 : 0,
            cancellable: true,
            actorId: 42,
            websiteIds: [],
            result: null,
            error: null,
            createdAt: '2026-08-12T08:00:00Z',
            updatedAt: '2026-08-12T08:00:00Z',
            expiresAt: '2026-08-13T08:00:00Z',
        );
    }
}

final class CustomSharedOperationStore implements Store, LockProvider
{
    private ArrayStore $store;

    public int $lockCount = 0;

    /**
     * Create the custom lock-capable store used by policy tests.
     */
    public function __construct()
    {
        $this->store = new ArrayStore();
    }

    /**
     * Retrieve a custom-store value.
     *
     * @param  string  $key
     *
     * @return mixed
     */
    public function get($key)
    {
        return $this->store->get($key);
    }

    /**
     * Retrieve multiple custom-store values.
     *
     * @param  array<int, string>  $keys
     *
     * @return array<string, mixed>
     */
    public function many(array $keys)
    {
        return $this->store->many($keys);
    }

    /**
     * Store a custom-store value for a lifetime.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  int  $seconds
     *
     * @return bool
     */
    public function put($key, $value, $seconds)
    {
        return $this->store->put($key, $value, $seconds);
    }

    /**
     * Store multiple custom-store values for a lifetime.
     *
     * @param  array<string, mixed>  $values
     * @param  int  $seconds
     *
     * @return bool
     */
    public function putMany(array $values, $seconds)
    {
        return $this->store->putMany($values, $seconds);
    }

    /**
     * Increment a custom-store value.
     *
     * @param  string  $key
     * @param  int  $value
     *
     * @return int|false
     */
    public function increment($key, $value = 1)
    {
        return $this->store->increment($key, $value);
    }

    /**
     * Decrement a custom-store value.
     *
     * @param  string  $key
     * @param  int  $value
     *
     * @return int|false
     */
    public function decrement($key, $value = 1)
    {
        return $this->store->decrement($key, $value);
    }

    /**
     * Store a custom-store value indefinitely.
     *
     * @param  string  $key
     * @param  mixed  $value
     *
     * @return bool
     */
    public function forever($key, $value)
    {
        return $this->store->forever($key, $value);
    }

    /**
     * Change a custom-store value lifetime.
     *
     * @param  string  $key
     * @param  int  $seconds
     *
     * @return bool
     */
    public function touch($key, $seconds)
    {
        return $this->store->touch($key, $seconds);
    }

    /**
     * Remove a custom-store value.
     *
     * @param  string  $key
     *
     * @return bool
     */
    public function forget($key)
    {
        return $this->store->forget($key);
    }

    /**
     * Remove every custom-store value.
     *
     * @return bool
     */
    public function flush()
    {
        return $this->store->flush();
    }

    /**
     * Return the custom-store key prefix.
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->store->getPrefix();
    }

    /**
     * Create an atomic lock on this exact custom store.
     *
     * @param  string  $name
     * @param  int  $seconds
     * @param  string|null  $owner
     *
     * @return \Illuminate\Contracts\Cache\Lock
     */
    public function lock($name, $seconds = 0, $owner = null)
    {
        $this->lockCount++;

        return $this->store->lock($name, $seconds, $owner);
    }

    /**
     * Restore an atomic lock on this exact custom store.
     *
     * @param  string  $name
     * @param  string  $owner
     *
     * @return \Illuminate\Contracts\Cache\Lock
     */
    public function restoreLock($name, $owner)
    {
        return $this->store->restoreLock($name, $owner);
    }
}
