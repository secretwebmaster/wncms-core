<?php

namespace Wncms\Auth\Api\V2;

use Illuminate\Http\Request;
use Wncms\Models\Website;

final class WebsiteScopeGuard
{
    public const WEBSITE_ATTRIBUTE = 'wncms_api_v2_website';

    public const WEBSITE_IDENTITY_ATTRIBUTE = 'wncms_api_v2_website_identity';

    /**
     * Resolve and authorize one explicit stable website selection.
     *
     * Website keys use the canonical `website:{primary-key}` form. Domains are never identities.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Wncms\Auth\Api\V2\AuthenticationContext  $context
     * @return \Wncms\Models\Website|null
     */
    public function resolve(Request $request, AuthenticationContext $context): ?Website
    {
        $websiteId = $this->requestedWebsiteId($request);
        if ($websiteId === null || ! $context->hasWebsite($websiteId)) {
            return null;
        }

        $websiteModel = wncms()->getModelClass('website');
        $website = $websiteModel::query()->find($websiteId);
        if (! $website instanceof Website || ! $this->actorCanAccess($context, $website)) {
            return null;
        }

        $request->attributes->set(self::WEBSITE_ATTRIBUTE, $website);
        $request->attributes->set(self::WEBSITE_IDENTITY_ATTRIBUTE, self::identity($website));

        return $website;
    }

    /**
     * Return the canonical immutable identity for a resolved website.
     *
     * @param  \Wncms\Models\Website  $website
     * @return string
     */
    public static function identity(Website $website): string
    {
        return 'website:'.(string) $website->getKey();
    }

    /**
     * Resolve an explicit numeric ID or canonical website key.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return int|null
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
     * Determine whether the current actor owns or is related to the selected website.
     *
     * @param  \Wncms\Auth\Api\V2\AuthenticationContext  $context
     * @param  \Wncms\Models\Website  $website
     * @return bool
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
