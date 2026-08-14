<?php

namespace Wncms\Auth\Api\V2;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Request;
use Wncms\Api\V2\Risk\RiskContext;
use Wncms\Api\V2\Risk\RiskContextException;

final class WebsiteScopeGuard
{
    public const WEBSITE_ATTRIBUTE = 'wncms_api_v2_website';

    public const WEBSITE_IDENTITY_ATTRIBUTE = 'wncms_api_v2_website_identity';

    /**
     * Create the website scope guard.
     *
     * @param  \Wncms\Auth\Api\V2\ActorWebsiteAccess  $actorWebsites
     */
    public function __construct(private ActorWebsiteAccess $actorWebsites) {}

    /**
     * Resolve and authorize one explicit stable website selection.
     *
     * Website keys use the canonical `website:{primary-key}` form. Domains are never identities.
     */
    public function resolve(Request $request, AuthenticationContext $context): WebsiteScopeResolution
    {
        $websiteId = $this->requestedWebsiteId($request);
        if ($websiteId === null || $this->hasInvalidWebsiteIds($request)) {
            return WebsiteScopeResolution::rejected('website.scope_missing');
        }

        if (! $context->hasWebsite($websiteId)) {
            return WebsiteScopeResolution::rejected('website.scope_denied');
        }

        foreach ($this->requestedWebsiteIds($request) as $requestedId) {
            if (! $context->hasWebsite($requestedId)) {
                return WebsiteScopeResolution::rejected('website.scope_denied');
            }
        }

        $websiteModel = wncms()->getModelClass('website');
        $website = $websiteModel::query()->find($websiteId);
        if (! $website instanceof $websiteModel || ! $this->actorCanAccess($context, $website)) {
            return WebsiteScopeResolution::rejected('website.scope_denied');
        }
        $requestedWebsites = $websiteModel::query()->whereIn('id', $this->requestedWebsiteIds($request))->get();
        if ($requestedWebsites->count() !== count($this->requestedWebsiteIds($request))) {
            return WebsiteScopeResolution::rejected('website.scope_denied');
        }
        foreach ($requestedWebsites as $requestedWebsite) {
            if (! $requestedWebsite instanceof $websiteModel || ! $this->actorCanAccess($context, $requestedWebsite)) {
                return WebsiteScopeResolution::rejected('website.scope_denied');
            }
        }

        $request->attributes->set(self::WEBSITE_ATTRIBUTE, $website);
        $request->attributes->set(self::WEBSITE_IDENTITY_ATTRIBUTE, self::identity($website));

        return WebsiteScopeResolution::allowed($website);
    }

    /**
     * Enforce server-resolved target and requested website scope.
     *
     *
     * @throws \Wncms\Api\V2\Risk\RiskContextException
     */
    public function assertResolvedScope(AuthenticationContext $context, RiskContext $riskContext, bool $lock = false, bool $allowMissingForStalePlan = false): void
    {
        $scope = (array) ($riskContext->targetState['website_scope'] ?? []);
        $websiteIds = array_values(array_unique(array_map('intval', array_merge(
            (array) ($scope['requested_ids'] ?? []),
            (array) ($scope['target_ids'] ?? []),
        ))));
        foreach ($websiteIds as $websiteId) {
            if ($websiteId > 0 && ! $context->hasWebsite($websiteId)) {
                throw new RiskContextException('website.scope_denied', 403);
            }
        }
        $requestedRows = (array) ($scope['requested_rows'] ?? []);
        $missingRequestedRows = count($requestedRows) !== count((array) ($scope['requested_ids'] ?? []));
        if ($missingRequestedRows && ! $allowMissingForStalePlan) {
            throw new RiskContextException('website.scope_denied', 403);
        }
        if ($missingRequestedRows) {
            $existingIds = array_map(static fn (array $row): int => (int) ($row['id'] ?? 0), $requestedRows);
            $websiteIds = array_values(array_intersect($websiteIds, $existingIds));
        }
        $this->assertCurrentActorAccess($context, $websiteIds, $lock);
        if (
            (bool) ($scope['scoped_model'] ?? false)
            && (int) ($scope['target_count'] ?? 0) > 0
            && (array) ($scope['target_ids'] ?? []) === []
        ) {
            throw new RiskContextException('website.scope_denied', 403);
        }
    }

    /**
     * Re-read current actor membership and ownership for every authoritative website.
     *
     * @param  array<int, int>  $websiteIds
     */
    private function assertCurrentActorAccess(AuthenticationContext $context, array $websiteIds, bool $lock): void
    {
        if ($websiteIds === [] || $context->actorId() === null) {
            return;
        }
        $actor = $context->actor();
        $actorQuery = $actor->newQuery()->whereKey($context->actorId());
        $freshActor = ($lock ? $actorQuery->lockForUpdate() : $actorQuery)->first();
        if ($freshActor === null || ! method_exists($freshActor, 'websites')) {
            throw new RiskContextException('website.scope_denied', 403);
        }
        $websiteClass = wncms()->getModelClass('website');
        $websiteQuery = $websiteClass::query()->whereIn((new $websiteClass)->getKeyName(), $websiteIds);
        $websites = ($lock ? $websiteQuery->lockForUpdate() : $websiteQuery)->get();
        if ($websites->count() !== count($websiteIds)) {
            throw new RiskContextException('website.scope_denied', 403);
        }
        if ($freshActor instanceof \Wncms\Models\User && $this->actorWebsites->isAdministrator($freshActor)) {
            return;
        }
        $relation = $freshActor->websites();
        $pivotQuery = $relation->newPivotStatement()
            ->where($relation->getForeignPivotKeyName(), $freshActor->getKey())
            ->whereIn($relation->getRelatedPivotKeyName(), $websiteIds);
        if (method_exists($relation, 'getMorphType') && method_exists($relation, 'getMorphClass')) {
            $pivotQuery->where($relation->getMorphType(), $relation->getMorphClass());
        }
        $memberIds = ($lock ? $pivotQuery->lockForUpdate() : $pivotQuery)
            ->pluck($relation->getRelatedPivotKeyName())
            ->map(static fn ($id): int => (int) $id)
            ->all();
        foreach ($websites as $website) {
            if ((string) $website->user_id !== (string) $context->actorId() && ! in_array((int) $website->getKey(), $memberIds, true)) {
                throw new RiskContextException('website.scope_denied', 403);
            }
        }
    }

    /**
     * Return the canonical immutable identity for a resolved website.
     */
    public static function identity(Model $website): string
    {
        return 'website:'.(string) $website->getKey();
    }

    /**
     * Return every named connection used by current actor website authorization.
     *
     * @return array<int, string>
     */
    public function authorizationConnectionNames(AuthenticationContext $context): array
    {
        $actor = $context->actor();
        if (! method_exists($actor, 'getConnection') || ! method_exists($actor, 'websites')) {
            throw new \RuntimeException('Actor website authorization cannot be resolved.');
        }
        $relation = $actor->websites();
        if (! $relation instanceof BelongsToMany) {
            throw new \RuntimeException('Actor websites relation is unsupported.');
        }
        $websiteClass = wncms()->getModelClass('website');

        return array_values(array_unique([
            $actor->getConnection()->getName(),
            (new $websiteClass)->getConnection()->getName(),
            $relation->getRelated()->getConnection()->getName(),
            $relation->newPivotStatement()->getConnection()->getName(),
        ]));
    }

    /**
     * Resolve an explicit numeric ID or canonical website key.
     */
    private function requestedWebsiteId(Request $request): ?int
    {
        $id = $request->input('website_id');
        $key = trim((string) $request->input('website_key', ''));
        $keyId = null;

        if ($key !== '' && preg_match('/^website:([1-9][0-9]*)$/D', $key, $matches) === 1) {
            $keyId = (int) $matches[1];
        } elseif ($key !== '') {
            return null;
        }

        if ($id !== null && filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            return null;
        }

        $numericId = $id === null ? null : (int) $id;
        if ($numericId !== null && $keyId !== null && $numericId !== $keyId) {
            return null;
        }

        return $numericId ?? $keyId;
    }

    /**
     * Return every canonical requested website identifier.
     *
     * @return array<int, int>
     */
    public function requestedWebsiteIds(Request $request): array
    {
        $ids = [];
        $selected = $this->requestedWebsiteId($request);
        if ($selected !== null) {
            $ids[] = $selected;
        }
        $values = $request->input('website_ids', []);
        $values = is_array($values) ? $values : explode(',', (string) $values);
        foreach ($values as $value) {
            if (filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false) {
                $ids[] = (int) $value;
            }
        }
        $ids = array_values(array_unique($ids));
        sort($ids);

        return $ids;
    }

    /**
     * Determine whether any requested website-list value is not a positive integer.
     */
    private function hasInvalidWebsiteIds(Request $request): bool
    {
        $values = $request->input('website_ids', []);
        $values = is_array($values) ? $values : explode(',', (string) $values);

        return collect($values)->contains(
            static fn ($value): bool => filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false,
        );
    }

    /**
     * Determine whether the current actor owns or is related to the selected website.
     */
    private function actorCanAccess(AuthenticationContext $context, Model $website): bool
    {
        $actor = $context->actor();

        return $actor instanceof \Wncms\Models\User && $this->actorWebsites->canAccess($actor, $website);
    }
}
