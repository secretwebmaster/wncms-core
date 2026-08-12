<?php

namespace Wncms\Tests\Unit\Api\V2;

use Illuminate\Cache\DatabaseStore;
use Illuminate\Cache\DynamoDbStore;
use Illuminate\Cache\FileStore;
use Illuminate\Cache\MemcachedStore;
use Illuminate\Cache\RedisStore;
use Illuminate\Cache\Repository as IlluminateCacheRepository;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Filesystem\Filesystem;
use Mockery;
use Wncms\Api\V2\Repositories\CacheIdempotencyStore;
use Wncms\Tests\TestCase;

class IdempotencyStorePolicyTest extends TestCase
{
    /**
     * Verify the default idempotency allowlist contains only known shared Laravel stores.
     */
    public function test_default_idempotency_shared_store_allowlist_is_exact(): void
    {
        $this->assertSame([
            RedisStore::class,
            MemcachedStore::class,
            DatabaseStore::class,
        ], config('wncms-api-v2.idempotency.allowed_shared_store_classes'));
    }

    /**
     * Verify production rejects an unknown locking store without exact operator trust.
     */
    public function test_production_rejects_unknown_idempotency_store_by_default(): void
    {
        $store = new CustomSharedIdempotencyStore;
        $idempotency = new CacheIdempotencyStore(
            $this->cacheFactory(new IlluminateCacheRepository($store)),
            'custom',
            true,
        );

        $this->expectException(\InvalidArgumentException::class);
        $idempotency->get('scope');
    }

    /**
     * Verify exact custom-store opt-in uses one backend for records and locks.
     */
    public function test_production_accepts_exact_custom_idempotency_store_and_reuses_its_lock_provider(): void
    {
        $store = new CustomSharedIdempotencyStore;
        $idempotency = new CacheIdempotencyStore(
            $this->cacheFactory(new IlluminateCacheRepository($store), 'custom'),
            'custom',
            true,
            [CustomSharedIdempotencyStore::class],
        );

        $idempotency->put('scope', ['status' => 201], 60);
        $lock = $idempotency->lock('scope', 10);
        $this->assertTrue($lock->get());
        $lock->release();

        $this->assertSame(['status' => 201], $idempotency->get('scope'));
        $this->assertSame(1, $store->lockCount);
    }

    /**
     * Verify production FileStore requires exact shared-volume opt-in.
     */
    public function test_production_file_idempotency_store_requires_exact_opt_in(): void
    {
        $store = new FileStore(new Filesystem, sys_get_temp_dir().'/wncms-idempotency-store-policy');

        $rejected = new CacheIdempotencyStore(
            $this->cacheFactory(new IlluminateCacheRepository($store)),
            'file',
            true,
        );
        try {
            $rejected->get('scope');
            $this->fail('FileStore must be rejected without exact shared-volume opt-in.');
        } catch (\InvalidArgumentException) {
            $this->addToAssertionCount(1);
        }

        $allowed = new CacheIdempotencyStore(
            $this->cacheFactory(new IlluminateCacheRepository($store)),
            'file',
            true,
            [FileStore::class],
        );
        $this->assertNull($allowed->get('scope'));
    }

    /**
     * Verify DynamoDB and subclasses remain prohibited despite exact configuration.
     */
    public function test_production_permanently_rejects_dynamodb_idempotency_stores(): void
    {
        foreach ([DynamoDbStore::class, CustomDynamoDbIdempotencyStore::class] as $storeClass) {
            $store = Mockery::mock($storeClass);
            $store->shouldReceive('get')->never();
            $idempotency = new CacheIdempotencyStore(
                $this->cacheFactory(new IlluminateCacheRepository($store)),
                'dynamodb',
                true,
                [$storeClass],
            );

            try {
                $idempotency->get('scope');
                $this->fail("{$storeClass} must remain prohibited.");
            } catch (\InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    /**
     * Return a cache factory resolving one exact repository.
     */
    private function cacheFactory(
        IlluminateCacheRepository $repository,
        ?string $storeName = null
    ): CacheFactory {
        $cache = Mockery::mock(CacheFactory::class);
        $expectation = $cache->shouldReceive('store')->once();
        if ($storeName !== null) {
            $expectation->with($storeName);
        }
        $expectation->andReturn($repository);

        return $cache;
    }
}

class CustomDynamoDbIdempotencyStore extends DynamoDbStore {}

class CustomSharedIdempotencyStore extends FileStore
{
    public int $lockCount = 0;

    /**
     * Create an isolated lock-capable cache store.
     */
    public function __construct()
    {
        parent::__construct(
            new Filesystem,
            sys_get_temp_dir().'/wncms-idempotency-custom-'.bin2hex(random_bytes(8))
        );
    }

    /**
     * Create an atomic lock and record that this exact backend supplied it.
     *
     * @param  string  $name
     * @param  int  $seconds
     * @param  string|null  $owner
     * @return \Illuminate\Contracts\Cache\Lock
     */
    public function lock($name, $seconds = 0, $owner = null)
    {
        $this->lockCount++;

        return parent::lock($name, $seconds, $owner);
    }
}
