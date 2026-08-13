<?php

namespace Wncms\Auth\Api\V2;

use Illuminate\Http\Request;
use Wncms\Api\V2\Risk\RiskContext;
use Wncms\Api\V2\Risk\RiskContextException;
use Wncms\Models\Website;

final class WebsiteScopeGuard
{
    public const WEBSITE_ATTRIBUTE = 'wncms_api_v2_website';

    public const WEBSITE_IDENTITY_ATTRIBUTE = 'wncms_api_v2_website_identity';

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
        if (! $website instanceof Website || ! $this->actorCanAccess($context, $website)) {
            return WebsiteScopeResolution::rejected('website.scope_denied');
        }
        $requestedWebsites = $websiteModel::query()->whereIn('id', $this->requestedWebsiteIds($request))->get();
        if ($requestedWebsites->count() !== count($this->requestedWebsiteIds($request))) {
            return WebsiteScopeResolution::rejected('website.scope_denied');
        }
        foreach ($requestedWebsites as $requestedWebsite) {
            if (! $requestedWebsite instanceof Website || ! $this->actorCanAccess($context, $requestedWebsite)) {
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
    public function assertResolvedScope(AuthenticationContext $context, RiskContext $riskContext): void
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
        if (
            (bool) ($scope['scoped_model'] ?? false)
            && (int) ($scope['target_count'] ?? 0) > 0
            && (array) ($scope['target_ids'] ?? []) === []
        ) {
            throw new RiskContextException('website.scope_denied', 403);
        }
    }

    /**
     * Return the canonical immutable identity for a resolved website.
     */
    public static function identity(Website $website): string
    {
        return 'website:'.(string) $website->getKey();
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
    private function actorCanAccess(AuthenticationContext $context, Website $website): bool
    {
        if ((string) $website->user_id === (string) $context->actorId()) {
            return true;
        }

        $actor = $context->actor();

        return method_exists($actor, 'websites')
            && $actor->websites()->whereKey($website->getKey())->exists();
    }
}
