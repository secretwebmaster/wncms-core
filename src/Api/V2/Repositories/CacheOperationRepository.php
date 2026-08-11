<?php

namespace Wncms\Api\V2\Repositories;

use Carbon\CarbonImmutable;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\FailoverStore;
use Illuminate\Cache\NullStore;
use Illuminate\Contracts\Cache\Factory;
use Illuminate\Contracts\Cache\Repository;
use Wncms\Api\V2\Contracts\OperationRepository;
use Wncms\Api\V2\Data\AsyncOperation;
use Wncms\Api\V2\Enums\AsyncOperationStatus;

class CacheOperationRepository implements OperationRepository
{
    protected const RECORD_PREFIX = 'wncms:api-v2:operation:';

    protected const RECORD_VERSION = 1;

    protected ?Repository $repository = null;

    /**
     * Create the cache-backed operation repository.
     *
     * @param  \Illuminate\Contracts\Cache\Factory  $cache
     * @param  mixed  $storeName
     * @param  bool  $requireSharedStore
     */
    public function __construct(
        protected Factory $cache,
        protected mixed $storeName = null,
        protected bool $requireSharedStore = false
    ) {
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

        if ($this->repository()->put(
            $this->recordKey($operation->id),
            $this->serializeOperation($operation),
            $ttlSeconds
        ) !== true) {
            throw new \RuntimeException('Unable to persist the API v2 operation record');
        }
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
     * @throws \UnexpectedValueException
     */
    public function find(string $id): ?AsyncOperation
    {
        $record = $this->repository()->get($this->recordKey($id));
        if ($record === null) {
            return null;
        }

        if (! is_array($record)) {
            throw new \UnexpectedValueException('Stored API v2 operation record is invalid');
        }

        $operation = $this->hydrateOperation($record);
        if (! hash_equals($id, $operation->id)) {
            throw new \UnexpectedValueException('Stored API v2 operation identifier is invalid');
        }

        if ($this->isExpired($operation)) {
            $this->forget($id);

            return null;
        }

        return $operation;
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

        if ($this->requireSharedStore && (
            $store instanceof ArrayStore
            || $store instanceof FailoverStore
            || $store instanceof NullStore
        )) {
            throw new \InvalidArgumentException(
                'The API v2 operation cache store must be shared across production processes'
            );
        }

        $this->repository = $repository;

        return $this->repository;
    }

    /**
     * Serialize an operation without coercing mixed result or error values.
     *
     * @param  \Wncms\Api\V2\Data\AsyncOperation  $operation
     *
     * @return array<string, mixed>
     */
    protected function serializeOperation(AsyncOperation $operation): array
    {
        return [
            'version' => self::RECORD_VERSION,
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
     * @return \Wncms\Api\V2\Data\AsyncOperation
     *
     * @throws \UnexpectedValueException
     */
    protected function hydrateOperation(array $record): AsyncOperation
    {
        $required = [
            'version',
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

        $this->parseUtcTimestamp($record['created_at']);
        $this->parseUtcTimestamp($record['updated_at']);
        $this->parseUtcTimestamp($record['expires_at']);

        return new AsyncOperation(
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
        return $this->parseUtcTimestamp($operation->expiresAt)
            ->lessThanOrEqualTo(CarbonImmutable::now('UTC'));
    }

    /**
     * Parse the canonical second-precision UTC timestamp format.
     *
     * @param  string  $timestamp
     *
     * @return \Carbon\CarbonImmutable
     *
     * @throws \UnexpectedValueException
     */
    protected function parseUtcTimestamp(string $timestamp): CarbonImmutable
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $timestamp)) {
            throw new \UnexpectedValueException('Stored API v2 operation timestamp is invalid');
        }

        try {
            $parsed = CarbonImmutable::createFromFormat('Y-m-d\TH:i:s\Z', $timestamp, 'UTC');
        } catch (\Throwable $exception) {
            throw new \UnexpectedValueException(
                'Stored API v2 operation timestamp is invalid',
                previous: $exception
            );
        }

        if (! $parsed instanceof CarbonImmutable || $parsed->format('Y-m-d\TH:i:s\Z') !== $timestamp) {
            throw new \UnexpectedValueException('Stored API v2 operation timestamp is invalid');
        }

        return $parsed;
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
}
