<?php

namespace Wncms\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    /**
     * Fetch view
     */
    public function view(string $name, array $params = [], ?string $fallbackView = null, ?string $fallbackRoute = null)
    {
        return wncms()->view($name, $params, $fallbackView, $fallbackRoute);
    }

    /**
     * Check whether model supports WNCMS multisite relation binding.
     */
    protected function supportsWncmsMultisite(string $modelClass): bool
    {
        if (!method_exists($modelClass, 'getWebsiteMode') || !method_exists($modelClass, 'bindWebsites')) {
            return false;
        }

        return in_array($modelClass::getWebsiteMode(), ['single', 'multi'], true);
    }

    /**
     * Resolve requested website IDs for a model according to website mode.
     */
    protected function resolveModelWebsiteIds(string $modelClass, array|string|int|null $websiteIds = null): array
    {
        if (!$this->supportsWncmsMultisite($modelClass)) {
            return [];
        }

        $websiteMode = $modelClass::getWebsiteMode();

        if (!gss('multi_website')) {
            $fallbackWebsiteId = wncms()->website()->get()?->id;
            return $fallbackWebsiteId ? [$fallbackWebsiteId] : [];
        }

        if ($websiteIds === null) {
            $websiteIds = $websiteMode === 'multi'
                ? request()->input('website_ids', [])
                : request()->input('website_id');
        }

        if (is_int($websiteIds)) {
            $websiteIds = [$websiteIds];
        }

        if (is_string($websiteIds)) {
            $websiteIds = explode(',', $websiteIds);
        }

        $websiteIds = array_values(array_unique(array_filter(array_map('intval', (array) $websiteIds))));

        if ($websiteMode === 'single') {
            return !empty($websiteIds) ? [reset($websiteIds)] : [];
        }

        if ($websiteMode === 'multi') {
            return $this->normalizeWebsiteIds($websiteIds);
        }

        return [];
    }

    /**
     * Sync model website bindings based on model website mode.
     */
    protected function syncModelWebsites($model, array $websiteIds): void
    {
        $modelClass = get_class($model);
        if (!$this->supportsWncmsMultisite($modelClass)) {
            return;
        }

        $websiteMode = $modelClass::getWebsiteMode();
        $websiteIds = $this->normalizeWebsiteIds($websiteIds);

        if ($websiteMode === 'single') {
            if (!empty($websiteIds)) {
                $model->bindWebsites(reset($websiteIds));
            }
            return;
        }

        if ($websiteMode === 'multi') {
            if (method_exists($model, 'unbindAllWebsites')) {
                $model->unbindAllWebsites();
            }

            if (!empty($websiteIds)) {
                $model->bindWebsites($websiteIds);
            }
        }
    }

    /**
     * Keep only existing website IDs.
     */
    protected function normalizeWebsiteIds(array $websiteIds): array
    {
        $websiteIds = array_values(array_unique(array_filter(array_map('intval', $websiteIds))));
        if (empty($websiteIds)) {
            return [];
        }

        return wncms()->getModelClass('website')::whereIn('id', $websiteIds)->pluck('id')->map(fn($id) => (int) $id)->values()->all();
    }
}
