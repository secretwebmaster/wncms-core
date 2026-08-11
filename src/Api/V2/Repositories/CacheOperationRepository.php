<?php

namespace Wncms\Api\V2\Repositories;

use Carbon\CarbonImmutable;
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
use Wncms\Api\V2\Contracts\AtomicOperationRepository;
use Wncms\Api\V2\Data\AsyncOperation;
use Wncms\Api\V2\Enums\AsyncOperationStatus;
use Wncms\Api\V2\OperationValidator;

class CacheOperationRepository implements AtomicOperationRepository
{
    protected const RECORD_PREFIX = 'wncms:api-v2:operation:';

    protected const LOCK_PREFIX = 'wncms:api-v2:operation-lock:';

    protected const RECORD_VERSION = 2;

    protected const DEFAULT_SHARED_STORE_CLASSES = [
        RedisStore::class,
        MemcachedStore::class,
        DatabaseStore::class,
    ];

    protected ?Repository $repository = null;

    /**
     * @var \WeakMap<\Wncms\Api\V2\Data\AsyncOperation, string>
     */
    protected \WeakMap $observedRevisions;

    protected OperationValidator $validator;

    /**
     * Create the cache-backed operation repository.
     *
     * @param  \Illuminate\Contracts\Cache\Factory  $cache
     * @param  mixed  $storeName
     * @param  bool  $requireSharedStore
     * @param  mixed  $allowedSharedStoreClasses
     * @param  int  $lockSeconds
     * @param  \Wncms\Api\V2\OperationValidator|null  $validator
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(
        protected Factory $cache,
        protected mixed $storeName = null,
        protected bool $requireSharedStore = false,
        protected mixed $allowedSharedStoreClasses = null,
        protected int $lockSeconds = 10,
        ?OperationValidator $validator = null
    ) {
        if ($this->lockSeconds <= 0) {
            throw new \InvalidArgumentException('Operation lock lifetime must be greater than zero');
        }

        $this->validator = $validator ?? new OperationValidator();
        $this->observedRevisions = new \WeakMap();
    }

    /**
     * Persist a versioned operation record for the requested lifetime.
     *
     * @param  \Wncms\Api\V2\Data\AsyncOperation  $operation
     * @param  int  $ttlSeconds
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function save(AsyncOperation $operation, int $ttlSeconds): void
    {
        if ($ttlSeconds <= 0) {
            throw new \InvalidArgumentException('Operation cache TTL must be greater than zero');
        }

        $this->validator->validate($operation);
        $revision = $this->newRevision();
        if ($this->repository()->put(
            $this->recordKey($operation->id),
            $this->serializeOperation($operation, $revision),
            $ttlSeconds
        ) !== true) {
            throw new \RuntimeException('Unable to persist the API v2 operation record');
        }

        $this->observedRevisions[$operation] = $revision;
    }

    /**
     * Atomically replace an operation when its observed persisted revision remains current.
     *
     * The comparison and replacement execute under a per-operation lock supplied by the same
     * resolved cache backend used for ordinary reads and writes.
     *
     * @param  \Wncms\Api\V2\Data\AsyncOperation  $expected
     * @param  \Wncms\Api\V2\Data\AsyncOperation  $replacement
     * @param  int  $ttlSeconds
     *
     * @return bool
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function compareAndSwap(
        AsyncOperation $expected,
        AsyncOperation $replacement,
        int $ttlSeconds
    ): bool {
        if ($ttlSeconds <= 0) {
            throw new \InvalidArgumentException('Operation cache TTL must be greater than zero');
        }

        $this->validator->validate($expected);
        $this->validator->validate($replacement);

        if (! hash_equals($expected->id, $replacement->id)) {
            throw new \InvalidArgumentException('Atomic operation replacement must preserve the operation ID');
        }

        $expectedRevision = $this->observedRevisions[$expected] ?? null;
        if (! is_string($expectedRevision)) {
            return false;
        }

        $repository = $this->repository();
        $store = $repository->getStore();
        if (! $store instanceof LockProvider) {
            throw new \InvalidArgumentException('The API v2 operation cache store must support atomic locks');
        }

        $lock = $store->lock($this->lockKey($expected->id), $this->lockSeconds);
        $swapped = $lock->get(function () use (
            $repository,
            $expected,
            $expectedRevision,
            $replacement,
            $ttlSeconds
        ): bool {
            $record = $repository->get($this->recordKey($expected->id));
            $stored = $this->decodeRecord($expected->id, $record);
            if ($stored === null || ! hash_equals($expectedRevision, $stored['revision'])) {
                return false;
            }

            $replacementRevision = $this->newRevision();
            if ($repository->put(
                $this->recordKey($replacement->id),
                $this->serializeOperation($replacement, $replacementRevision),
                $ttlSeconds
            ) !== true) {
                throw new \RuntimeException('Unable to persist the API v2 operation record');
            }

            $this->observedRevisions[$replacement] = $replacementRevision;

            return true;
        });

        return $swapped === true;
    }

    /**
     * Find and hydrate an unexpired operation record.
     *
     * Logically expired values are removed even when their physical cache TTL has not elapsed.
     *
     * @param  string  $id
     *
     * @return \Wncms\Api\V2\Data\AsyncOperation|null
     *
     */
    public function find(string $id): ?AsyncOperation
    {
        $repository = $this->repository();
        $record = $repository->get($this->recordKey($id));
        if ($record === null) {
            return null;
        }

        $stored = $this->decodeRecord($id, $record);
        if ($stored !== null && ! $this->isExpired($stored['operation'])) {
            return $this->trackStoredOperation($stored);
        }

        return $this->cleanupInvalidOrExpiredRecord($id, $repository);
    }

    /**
     * Remove an operation record from cache.
     *
     * @param  string  $id
     *
     * @return void
     */
    public function forget(string $id): void
    {
        $this->repository()->forget($this->recordKey($id));
    }

    /**
     * Resolve and validate the configured cache repository on first use.
     *
     * Lazy resolution keeps configuration failures inside API response normalization.
     *
     * @return \Illuminate\Contracts\Cache\Repository
     *
     * @throws \InvalidArgumentException
     */
    protected function repository(): Repository
    {
        if ($this->repository instanceof Repository) {
            return $this->repository;
        }

        if ($this->storeName !== null && (! is_string($this->storeName) || trim($this->storeName) === '')) {
            throw new \InvalidArgumentException(
                'wncms-api-v2.operations.store must be a cache store name or null'
            );
        }

        $storeName = $this->storeName === null ? null : trim($this->storeName);
        $repository = $this->cache->store($storeName);
        $store = $repository->getStore();

        if (! $store instanceof LockProvider) {
            throw new \InvalidArgumentException(
                'The API v2 operation cache store must support atomic locks'
            );
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
                    'The API v2 operation cache store must be an explicitly trusted shared store'
                );
            }
        }

        $this->repository = $repository;

        return $this->repository;
    }

    /**
     * Resolve the exact cache-store classes trusted for production operation state.
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
                'wncms-api-v2.operations.allowed_shared_store_classes must be a list of exact class names'
            );
        }

        foreach ($classes as $class) {
            if (! is_string($class) || trim($class) === '') {
                throw new \InvalidArgumentException(
                    'wncms-api-v2.operations.allowed_shared_store_classes must contain exact class names'
                );
            }
        }

        return array_values(array_unique($classes));
    }

    /**
     * Serialize an operation without coercing mixed result or error values.
     *
     * @param  \Wncms\Api\V2\Data\AsyncOperation  $operation
     * @param  string  $revision
     *
     * @return array<string, mixed>
     */
    protected function serializeOperation(AsyncOperation $operation, string $revision): array
    {
        return [
            'version' => self::RECORD_VERSION,
            'revision' => $revision,
            'id' => $operation->id,
            'type' => $operation->type,
            'status' => $operation->status->value,
            'progress' => $operation->progress,
            'cancellable' => $operation->cancellable,
            'actor_id' => $operation->actorId,
            'website_ids' => $operation->websiteIds,
            'result' => $operation->result,
            'error' => $operation->error,
            'created_at' => $operation->createdAt,
            'updated_at' => $operation->updatedAt,
            'expires_at' => $operation->expiresAt,
        ];
    }

    /**
     * Hydrate and validate a versioned operation record.
     *
     * @param  array<string, mixed>  $record
     *
     * @return array{operation: \Wncms\Api\V2\Data\AsyncOperation, revision: string}
     *
     * @throws \UnexpectedValueException
     */
    protected function hydrateOperation(array $record): array
    {
        $required = [
            'version',
            'revision',
            'id',
            'type',
            'status',
            'progress',
            'cancellable',
            'actor_id',
            'website_ids',
            'result',
            'error',
            'created_at',
            'updated_at',
            'expires_at',
        ];

        foreach ($required as $key) {
            if (! array_key_exists($key, $record)) {
                throw new \UnexpectedValueException('Stored API v2 operation record is incomplete');
            }
        }

        $status = is_string($record['status'])
            ? AsyncOperationStatus::tryFrom($record['status'])
            : null;
        if (
            $record['version'] !== self::RECORD_VERSION
            || ! is_string($record['revision'])
            || preg_match('/^[a-f0-9]{32}$/', $record['revision']) !== 1
            || ! is_string($record['id'])
            || ! is_string($record['type'])
            || ! $status instanceof AsyncOperationStatus
            || ! is_int($record['progress'])
            || ! is_bool($record['cancellable'])
            || ! is_int($record['actor_id'])
            || ! is_array($record['website_ids'])
            || (! is_array($record['error']) && $record['error'] !== null)
            || ! is_string($record['created_at'])
            || ! is_string($record['updated_at'])
            || ! is_string($record['expires_at'])
        ) {
            throw new \UnexpectedValueException('Stored API v2 operation record has invalid types');
        }

        $operation = new AsyncOperation(
            id: $record['id'],
            type: $record['type'],
            status: $status,
            progress: $record['progress'],
            cancellable: $record['cancellable'],
            actorId: $record['actor_id'],
            websiteIds: $record['website_ids'],
            result: $record['result'],
            error: $record['error'],
            createdAt: $record['created_at'],
            updatedAt: $record['updated_at'],
            expiresAt: $record['expires_at'],
        );

        $this->validator->validate($operation);

        return [
            'operation' => $operation,
            'revision' => $record['revision'],
        ];
    }

    /**
     * Decode a stored operation record without mutating cache state.
     *
     * @param  string  $id
     * @param  mixed  $record
     *
     * @return array{operation: \Wncms\Api\V2\Data\AsyncOperation, revision: string}|null
     */
    protected function decodeRecord(string $id, mixed $record): ?array
    {
        if ($record === null) {
            return null;
        }

        try {
            if (! is_array($record)) {
                throw new \UnexpectedValueException('Stored API v2 operation record is invalid');
            }

            $stored = $this->hydrateOperation($record);
            if (! hash_equals($id, $stored['operation']->id)) {
                throw new \UnexpectedValueException('Stored API v2 operation identifier is invalid');
            }

            return $stored;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Recheck a suspect record under its per-operation lock before deleting it.
     *
     * A valid replacement installed after the first read is returned and never removed. FileStore
     * may be configured explicitly only when every API and queue worker uses the same shared volume.
     *
     * @param  string  $id
     * @param  \Illuminate\Contracts\Cache\Repository  $repository
     *
     * @return \Wncms\Api\V2\Data\AsyncOperation|null
     */
    protected function cleanupInvalidOrExpiredRecord(string $id, Repository $repository): ?AsyncOperation
    {
        $store = $repository->getStore();
        if (! $store instanceof LockProvider) {
            throw new \InvalidArgumentException('The API v2 operation cache store must support atomic locks');
        }

        $resolved = $store->lock($this->lockKey($id), $this->lockSeconds)->get(
            function () use ($id, $repository): ?AsyncOperation {
                $record = $repository->get($this->recordKey($id));
                if ($record === null) {
                    return null;
                }

                $stored = $this->decodeRecord($id, $record);
                if ($stored !== null && ! $this->isExpired($stored['operation'])) {
                    return $this->trackStoredOperation($stored);
                }

                $repository->forget($this->recordKey($id));

                return null;
            }
        );

        return $resolved instanceof AsyncOperation ? $resolved : null;
    }

    /**
     * Associate a hydrated immutable operation with its observed persisted revision.
     *
     * @param  array{operation: \Wncms\Api\V2\Data\AsyncOperation, revision: string}  $stored
     *
     * @return \Wncms\Api\V2\Data\AsyncOperation
     */
    protected function trackStoredOperation(array $stored): AsyncOperation
    {
        $operation = $stored['operation'];
        $this->observedRevisions[$operation] = $stored['revision'];

        return $operation;
    }

    /**
     * Determine whether an operation has reached its immutable logical expiry.
     *
     * @param  \Wncms\Api\V2\Data\AsyncOperation  $operation
     *
     * @return bool
     */
    protected function isExpired(AsyncOperation $operation): bool
    {
        return $this->validator->parseUtcTimestamp($operation->expiresAt)
            ->lessThanOrEqualTo(CarbonImmutable::now('UTC'));
    }

    /**
     * Build an opaque cache key for an operation identifier.
     *
     * @param  string  $id
     *
     * @return string
     */
    protected function recordKey(string $id): string
    {
        return self::RECORD_PREFIX.hash('sha256', $id);
    }

    /**
     * Build the per-operation lock key without exposing the operation identifier.
     *
     * @param  string  $id
     *
     * @return string
     */
    protected function lockKey(string $id): string
    {
        return self::LOCK_PREFIX.hash('sha256', $id);
    }

    /**
     * Generate an opaque persisted revision that changes for every successful write.
     *
     * @return string
     *
     * @throws \Exception
     */
    protected function newRevision(): string
    {
        return bin2hex(random_bytes(16));
    }
}
