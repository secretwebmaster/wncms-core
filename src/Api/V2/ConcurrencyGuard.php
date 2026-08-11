<?php

namespace Wncms\Api\V2;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Wncms\Api\V2\Exceptions\ApiConflictException;

class ConcurrencyGuard
{
    /**
     * Create a stable optimistic-concurrency revision for an Eloquent model.
     */
    public function revision(Model $model): string
    {
        return hash('sha256', json_encode([
            'class' => $model::class,
            'route_key' => (string) $model->getRouteKey(),
            'updated_at' => $this->updatedAt($model),
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * Require an If-Match value that matches the model's current revision.
     *
     * Missing or non-matching revisions prevent stale mutations.
     *
     * @throws \Wncms\Api\V2\Exceptions\ApiConflictException
     */
    public function assertMatches(Model $model, ?string $ifMatch): void
    {
        $revision = $this->normalizeEtag($ifMatch);

        if ($revision === null || ! hash_equals($this->revision($model), $revision)) {
            throw new ApiConflictException;
        }
    }

    /**
     * Build the ETag response header value for a model revision.
     */
    public function responseEtag(Model $model): string
    {
        return '"'.$this->revision($model).'"';
    }

    /**
     * Serialize the model's update time as a UTC ISO-8601 instant.
     */
    private function updatedAt(Model $model): ?string
    {
        $updatedAt = $model->getAttribute('updated_at');
        if ($updatedAt === null) {
            return null;
        }

        if ($updatedAt instanceof DateTimeInterface) {
            return Carbon::instance($updatedAt)->utc()->format('Y-m-d\\TH:i:s.u\\Z');
        }

        return Carbon::parse($updatedAt)->utc()->format('Y-m-d\\TH:i:s.u\\Z');
    }

    /**
     * Strip HTTP weak and quoted ETag syntax from an If-Match value.
     */
    private function normalizeEtag(?string $ifMatch): ?string
    {
        if ($ifMatch === null) {
            return null;
        }

        $etag = trim($ifMatch);
        if ($etag === '') {
            return null;
        }

        if (str_starts_with(strtoupper($etag), 'W/')) {
            $etag = trim(substr($etag, 2));
        }

        if (str_starts_with($etag, '"') && str_ends_with($etag, '"')) {
            $etag = substr($etag, 1, -1);
        }

        return $etag === '' ? null : $etag;
    }
}
