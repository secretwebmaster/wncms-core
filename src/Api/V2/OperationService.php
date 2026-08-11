<?php

namespace Wncms\Api\V2;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Wncms\Api\V2\Contracts\AtomicOperationRepository;
use Wncms\Api\V2\Data\AsyncOperation;
use Wncms\Api\V2\Enums\AsyncOperationStatus;
use Wncms\Api\V2\Exceptions\ApiConflictException;

final class OperationService
{
    protected const TRANSITION_ATTEMPTS = 3;

    private readonly OperationValidator $validator;

    /**
     * Create the asynchronous operation state service.
     *
     * @param  \Wncms\Api\V2\Contracts\AtomicOperationRepository  $operations
     * @param  int  $ttlSeconds
     * @param  \Wncms\Api\V2\OperationValidator|null  $validator
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(
        private readonly AtomicOperationRepository $operations,
        private readonly int $ttlSeconds = 86400,
        ?OperationValidator $validator = null
    ) {
        if ($this->ttlSeconds <= 0) {
            throw new \InvalidArgumentException('Operation TTL must be greater than zero');
        }

        $this->validator = $validator ?? new OperationValidator();
    }

    /**
     * Queue a new operation for an authenticated actor.
     *
     * @param  string  $type
     * @param  int  $actorId
     * @param  array<int, int|string>  $websiteIds
     * @param  bool  $cancellable
     *
     * @return \Wncms\Api\V2\Data\AsyncOperation
     *
     * @throws \RuntimeException
     */
    public function queue(
        string $type,
        int $actorId,
        array $websiteIds = [],
        bool $cancellable = false
    ): AsyncOperation {
        $now = CarbonImmutable::now('UTC');
        $timestamp = $this->formatTimestamp($now);
        $operation = new AsyncOperation(
            id: (string) Str::uuid(),
            type: $type,
            status: AsyncOperationStatus::Queued,
            progress: 0,
            cancellable: $cancellable,
            actorId: $actorId,
            websiteIds: $websiteIds,
            result: null,
            error: null,
            createdAt: $timestamp,
            updatedAt: $timestamp,
            expiresAt: $this->formatTimestamp($now->addSeconds($this->ttlSeconds)),
        );

        $this->validator->validate($operation);
        $this->operations->save($operation, $this->ttlSeconds);

        return $operation;
    }

    /**
     * Start a queued operation.
     *
     * @param  string  $id
     *
     * @return \Wncms\Api\V2\Data\AsyncOperation
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Wncms\Api\V2\Exceptions\ApiConflictException
     */
    public function start(string $id): AsyncOperation
    {
        return $this->transition($id, function (AsyncOperation $operation): AsyncOperation {
            $this->assertStatus($operation, [AsyncOperationStatus::Queued], 'Operation cannot be started');

            return new AsyncOperation(
                id: $operation->id,
                type: $operation->type,
                status: AsyncOperationStatus::Running,
                progress: $operation->progress,
                cancellable: $operation->cancellable,
                actorId: $operation->actorId,
                websiteIds: $operation->websiteIds,
                result: $operation->result,
                error: $operation->error,
                createdAt: $operation->createdAt,
                updatedAt: $this->nowTimestamp(),
                expiresAt: $operation->expiresAt,
            );
        });
    }

    /**
     * Update progress for a running operation.
     *
     * @param  string  $id
     * @param  int  $progress
     *
     * @return \Wncms\Api\V2\Data\AsyncOperation
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Wncms\Api\V2\Exceptions\ApiConflictException
     */
    public function progress(string $id, int $progress): AsyncOperation
    {
        if ($progress < 0 || $progress > 100) {
            throw new ApiConflictException('Operation progress must be between 0 and 100');
        }

        return $this->transition($id, function (AsyncOperation $operation) use ($progress): AsyncOperation {
            $this->assertStatus(
                $operation,
                [AsyncOperationStatus::Running],
                'Operation progress cannot be updated'
            );

            return new AsyncOperation(
                id: $operation->id,
                type: $operation->type,
                status: $operation->status,
                progress: $progress,
                cancellable: $operation->cancellable,
                actorId: $operation->actorId,
                websiteIds: $operation->websiteIds,
                result: $operation->result,
                error: $operation->error,
                createdAt: $operation->createdAt,
                updatedAt: $this->nowTimestamp(),
                expiresAt: $operation->expiresAt,
            );
        });
    }

    /**
     * Complete a running operation successfully.
     *
     * @param  string  $id
     * @param  mixed  $result
     *
     * @return \Wncms\Api\V2\Data\AsyncOperation
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Wncms\Api\V2\Exceptions\ApiConflictException
     */
    public function succeed(string $id, mixed $result = null): AsyncOperation
    {
        return $this->transition($id, function (AsyncOperation $operation) use ($result): AsyncOperation {
            $this->assertStatus($operation, [AsyncOperationStatus::Running], 'Operation cannot succeed');

            return new AsyncOperation(
                id: $operation->id,
                type: $operation->type,
                status: AsyncOperationStatus::Succeeded,
                progress: 100,
                cancellable: false,
                actorId: $operation->actorId,
                websiteIds: $operation->websiteIds,
                result: $result,
                error: null,
                createdAt: $operation->createdAt,
                updatedAt: $this->nowTimestamp(),
                expiresAt: $operation->expiresAt,
            );
        });
    }

    /**
     * Complete a running operation with structured error details.
     *
     * @param  string  $id
     * @param  array<string, mixed>  $error
     *
     * @return \Wncms\Api\V2\Data\AsyncOperation
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Wncms\Api\V2\Exceptions\ApiConflictException
     */
    public function fail(string $id, array $error): AsyncOperation
    {
        return $this->transition($id, function (AsyncOperation $operation) use ($error): AsyncOperation {
            $this->assertStatus($operation, [AsyncOperationStatus::Running], 'Operation cannot fail');

            return new AsyncOperation(
                id: $operation->id,
                type: $operation->type,
                status: AsyncOperationStatus::Failed,
                progress: $operation->progress,
                cancellable: false,
                actorId: $operation->actorId,
                websiteIds: $operation->websiteIds,
                result: null,
                error: $error,
                createdAt: $operation->createdAt,
                updatedAt: $this->nowTimestamp(),
                expiresAt: $operation->expiresAt,
            );
        });
    }

    /**
     * Cancel a queued or running operation when cancellation is supported.
     *
     * Repeated cancellation returns the existing terminal value without extending its lifetime.
     *
     * @param  string  $id
     *
     * @return \Wncms\Api\V2\Data\AsyncOperation
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Wncms\Api\V2\Exceptions\ApiConflictException
     */
    public function cancel(string $id): AsyncOperation
    {
        return $this->transition($id, function (AsyncOperation $operation): AsyncOperation {
            if ($operation->status === AsyncOperationStatus::Cancelled) {
                return $operation;
            }

            $this->assertStatus(
                $operation,
                [AsyncOperationStatus::Queued, AsyncOperationStatus::Running],
                'Operation cannot be cancelled'
            );
            if (! $operation->cancellable) {
                throw new ApiConflictException('Operation does not support cancellation');
            }

            return new AsyncOperation(
                id: $operation->id,
                type: $operation->type,
                status: AsyncOperationStatus::Cancelled,
                progress: $operation->progress,
                cancellable: false,
                actorId: $operation->actorId,
                websiteIds: $operation->websiteIds,
                result: null,
                error: null,
                createdAt: $operation->createdAt,
                updatedAt: $this->nowTimestamp(),
                expiresAt: $operation->expiresAt,
            );
        });
    }

    /**
     * Find an operation only when it belongs to the requesting actor.
     *
     * Missing, expired, and cross-actor identifiers all return null to prevent disclosure.
     *
     * @param  string  $id
     * @param  int  $actorId
     *
     * @return \Wncms\Api\V2\Data\AsyncOperation|null
     */
    public function findForActor(string $id, int $actorId): ?AsyncOperation
    {
        $operation = $this->operations->find($id);
        if (! $operation instanceof AsyncOperation) {
            return null;
        }

        $this->validator->validate($operation);
        if ($this->isExpired($operation)) {
            $this->operations->forget($id);

            return null;
        }

        return $operation->actorId === $actorId ? $operation : null;
    }

    /**
     * Resolve a live operation or raise a resource-not-found response.
     *
     * @param  string  $id
     *
     * @return \Wncms\Api\V2\Data\AsyncOperation
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    private function findOrFail(string $id): AsyncOperation
    {
        $operation = $this->operations->find($id);
        if (! $operation instanceof AsyncOperation) {
            $this->operations->forget($id);

            throw new NotFoundHttpException('Operation not found');
        }

        $this->validator->validate($operation);
        if ($this->isExpired($operation)) {
            $this->operations->forget($id);

            throw new NotFoundHttpException('Operation not found');
        }

        return $operation;
    }

    /**
     * Apply a state transition with bounded compare-and-swap retries.
     *
     * Every retry re-reads and revalidates current state so a competing terminal transition
     * cannot be revived by a stale worker value.
     *
     * @param  string  $id
     * @param  callable(\Wncms\Api\V2\Data\AsyncOperation): \Wncms\Api\V2\Data\AsyncOperation  $transition
     *
     * @return \Wncms\Api\V2\Data\AsyncOperation
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Wncms\Api\V2\Exceptions\ApiConflictException
     */
    private function transition(string $id, callable $transition): AsyncOperation
    {
        for ($attempt = 0; $attempt < self::TRANSITION_ATTEMPTS; $attempt++) {
            $current = $this->findOrFail($id);
            $replacement = $transition($current);
            if ($replacement === $current) {
                return $current;
            }

            $this->validator->validate($replacement);
            $ttlSeconds = $this->remainingTtl($replacement);
            if ($this->operations->compareAndSwap($current, $replacement, $ttlSeconds)) {
                return $replacement;
            }
        }

        throw new ApiConflictException('Operation was modified concurrently');
    }

    /**
     * Calculate remaining cache lifetime without extending immutable operation expiry.
     *
     * @param  \Wncms\Api\V2\Data\AsyncOperation  $operation
     *
     * @return int
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    private function remainingTtl(AsyncOperation $operation): int
    {
        $ttlSeconds = $this->validator->parseUtcTimestamp($operation->expiresAt)->getTimestamp()
            - CarbonImmutable::now('UTC')->getTimestamp();
        if ($ttlSeconds <= 0) {
            $this->operations->forget($operation->id);

            throw new NotFoundHttpException('Operation not found');
        }

        return $ttlSeconds;
    }

    /**
     * Assert an operation is currently in one of the legal source states.
     *
     * @param  \Wncms\Api\V2\Data\AsyncOperation  $operation
     * @param  array<int, \Wncms\Api\V2\Enums\AsyncOperationStatus>  $statuses
     * @param  string  $message
     *
     * @return void
     */
    private function assertStatus(AsyncOperation $operation, array $statuses, string $message): void
    {
        if (! in_array($operation->status, $statuses, true)) {
            throw new ApiConflictException($message);
        }
    }

    /**
     * Determine whether an operation reached its immutable expiration time.
     *
     * @param  \Wncms\Api\V2\Data\AsyncOperation  $operation
     *
     * @return bool
     */
    private function isExpired(AsyncOperation $operation): bool
    {
        return $this->validator->parseUtcTimestamp($operation->expiresAt)
            ->lessThanOrEqualTo(CarbonImmutable::now('UTC'));
    }

    /**
     * Return the current time in canonical second-precision UTC format.
     *
     * @return string
     */
    private function nowTimestamp(): string
    {
        return $this->formatTimestamp(CarbonImmutable::now('UTC'));
    }

    /**
     * Format a timestamp in canonical second-precision UTC form.
     *
     * @param  \Carbon\CarbonImmutable  $timestamp
     *
     * @return string
     */
    private function formatTimestamp(CarbonImmutable $timestamp): string
    {
        return $timestamp->toIso8601ZuluString();
    }
}
