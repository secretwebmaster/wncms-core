<?php

namespace Wncms\Api\V2;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Wncms\Api\V2\Contracts\OperationRepository;
use Wncms\Api\V2\Data\AsyncOperation;
use Wncms\Api\V2\Enums\AsyncOperationStatus;
use Wncms\Api\V2\Exceptions\ApiConflictException;

final class OperationService
{
    /**
     * Create the asynchronous operation state service.
     *
     * @param  \Wncms\Api\V2\Contracts\OperationRepository  $operations
     * @param  int  $ttlSeconds
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(
        private readonly OperationRepository $operations,
        private readonly int $ttlSeconds = 86400
    ) {
        if ($this->ttlSeconds <= 0) {
            throw new \InvalidArgumentException('Operation TTL must be greater than zero');
        }
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
        $operation = $this->findOrFail($id);
        $this->assertStatus($operation, [AsyncOperationStatus::Queued], 'Operation cannot be started');

        return $this->persist(new AsyncOperation(
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
        ));
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
        $operation = $this->findOrFail($id);
        $this->assertStatus($operation, [AsyncOperationStatus::Running], 'Operation progress cannot be updated');
        if ($progress < 0 || $progress > 100) {
            throw new ApiConflictException('Operation progress must be between 0 and 100');
        }

        return $this->persist(new AsyncOperation(
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
        ));
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
        $operation = $this->findOrFail($id);
        $this->assertStatus($operation, [AsyncOperationStatus::Running], 'Operation cannot succeed');

        return $this->persist(new AsyncOperation(
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
        ));
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
        $operation = $this->findOrFail($id);
        $this->assertStatus($operation, [AsyncOperationStatus::Running], 'Operation cannot fail');

        return $this->persist(new AsyncOperation(
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
        ));
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
        $operation = $this->findOrFail($id);
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

        return $this->persist(new AsyncOperation(
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
        ));
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
        if (! $operation instanceof AsyncOperation || $this->isExpired($operation)) {
            $this->operations->forget($id);

            throw new NotFoundHttpException('Operation not found');
        }

        return $operation;
    }

    /**
     * Persist a transitioned operation without extending its immutable expiry.
     *
     * @param  \Wncms\Api\V2\Data\AsyncOperation  $operation
     *
     * @return \Wncms\Api\V2\Data\AsyncOperation
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    private function persist(AsyncOperation $operation): AsyncOperation
    {
        $ttlSeconds = $this->parseTimestamp($operation->expiresAt)->getTimestamp()
            - CarbonImmutable::now('UTC')->getTimestamp();
        if ($ttlSeconds <= 0) {
            $this->operations->forget($operation->id);

            throw new NotFoundHttpException('Operation not found');
        }

        $this->operations->save($operation, $ttlSeconds);

        return $operation;
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
        return $this->parseTimestamp($operation->expiresAt)
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

    /**
     * Parse a canonical operation timestamp.
     *
     * @param  string  $timestamp
     *
     * @return \Carbon\CarbonImmutable
     *
     * @throws \UnexpectedValueException
     */
    private function parseTimestamp(string $timestamp): CarbonImmutable
    {
        try {
            return CarbonImmutable::parse($timestamp)->utc();
        } catch (\Throwable $exception) {
            throw new \UnexpectedValueException(
                'Operation expiration timestamp is invalid',
                previous: $exception
            );
        }
    }
}
