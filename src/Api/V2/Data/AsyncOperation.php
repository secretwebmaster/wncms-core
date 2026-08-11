<?php

namespace Wncms\Api\V2\Data;

use JsonSerializable;
use Wncms\Api\V2\Enums\AsyncOperationStatus;

final class AsyncOperation implements JsonSerializable
{
    /**
     * Create an immutable asynchronous operation value.
     *
     * @param  array<int, int|string>  $websiteIds
     * @param  mixed  $result
     * @param  array<string, mixed>|null  $error
     */
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly AsyncOperationStatus $status,
        public readonly int $progress,
        public readonly bool $cancellable,
        public readonly int $actorId,
        public readonly array $websiteIds,
        public readonly mixed $result,
        public readonly ?array $error,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly string $expiresAt,
    ) {
    }

    /**
     * Export the operation with stable API field names.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status->value,
            'progress' => $this->progress,
            'cancellable' => $this->cancellable,
            'actor_id' => $this->actorId,
            'website_ids' => $this->websiteIds,
            'result' => $this->result,
            'error' => $this->error,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'expires_at' => $this->expiresAt,
        ];
    }
}
