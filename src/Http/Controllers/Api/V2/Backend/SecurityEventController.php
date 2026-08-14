<?php

namespace Wncms\Http\Controllers\Api\V2\Backend;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Wncms\Auth\Api\V2\AuthenticationContext;
use Wncms\Http\Middleware\ApiV2TokenAuth;
use Wncms\Http\Resources\Api\V2\SecurityEventResource;
use Wncms\Models\ApiSecurityEvent;

final class SecurityEventController extends ApiV2Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['sometimes', 'string'], 'severity' => ['sometimes', 'string'],
            'outcome' => ['sometimes', 'string'], 'surface' => ['sometimes', 'string'],
            'actor_type' => ['sometimes', 'string'], 'actor_id' => ['sometimes'],
            'target_type' => ['sometimes', 'string'], 'target_id' => ['sometimes'],
            'credential_type' => ['sometimes', 'string'], 'credential_id' => ['sometimes', 'string'], 'website_id' => ['sometimes'],
            'request_id' => ['sometimes', 'string'], 'run_id' => ['sometimes', 'string'],
            'from' => ['sometimes', 'date'], 'to' => ['sometimes', 'date'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);
        $query = $this->scopedQuery($request);
        foreach (['type' => 'event_type', 'severity' => 'severity', 'outcome' => 'outcome', 'surface' => 'surface',
            'actor_type' => 'actor_type', 'actor_id' => 'actor_id', 'target_type' => 'target_type', 'target_id' => 'target_id',
            'credential_type' => 'credential_type', 'credential_id' => 'credential_id',
            'request_id' => 'request_id', 'run_id' => 'run_id'] as $input => $column) {
            if (array_key_exists($input, $validated)) {
                $query->where($column, $validated[$input]);
            }
        }
        if (array_key_exists('website_id', $validated)) {
            $query->whereJsonContains('website_ids', $validated['website_id']);
        }
        if (isset($validated['from'])) {
            $query->where('occurred_at', '>=', CarbonImmutable::parse($validated['from'])->utc());
        }
        if (isset($validated['to'])) {
            $query->where('occurred_at', '<=', CarbonImmutable::parse($validated['to'])->utc());
        }

        $page = $query->orderByDesc('occurred_at')->orderByDesc('id')->paginate($this->normalizePerPage($request));
        $page->setCollection($page->getCollection()->map(fn (ApiSecurityEvent $event): array => (new SecurityEventResource($event))->toArray($request)));

        return $this->ok($page);
    }

    public function show(Request $request, string $eventId): JsonResponse
    {
        $event = $this->scopedQuery($request)->where('event_id', $eventId)->first();
        if (! $event instanceof ApiSecurityEvent) {
            return $this->error('Resource not found', 404);
        }

        return $this->ok((new SecurityEventResource($event))->toArray($request));
    }

    private function scopedQuery(Request $request): Builder
    {
        $context = $request->attributes->get(ApiV2TokenAuth::AUTH_CONTEXT_ATTRIBUTE);
        $websiteIds = $context instanceof AuthenticationContext ? $context->websiteIds() : [];
        $actorId = $context instanceof AuthenticationContext ? $context->actorId() : null;
        $actorTypes = [];
        if ($context instanceof AuthenticationContext) {
            $actor = $context->actor();
            $actorTypes[] = $actor::class;
            if (method_exists($actor, 'getMorphClass')) {
                $actorTypes[] = $actor->getMorphClass();
            }
            $actorTypes = array_values(array_unique($actorTypes));
        }

        return ApiSecurityEvent::query()->where(function (Builder $query) use ($websiteIds, $actorId, $actorTypes): void {
            $query->where(function (Builder $global) use ($actorId, $actorTypes): void {
                $global->where(function (Builder $scope): void {
                    $scope->whereNull('website_ids')->orWhereJsonLength('website_ids', 0);
                })->where('actor_id', $actorId)->whereIn('actor_type', $actorTypes);
            });
            foreach ($websiteIds as $websiteId) {
                $query->orWhereJsonContains('website_ids', $websiteId);
            }
        });
    }
}
