<?php

namespace Wncms\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasMultisite
{
    public static function getWebsiteMode(): string
    {
        $class = static::class;
        foreach (config('wncms.models', []) as $key => $data) {
            if (($data['class'] ?? null) === $class) {
                return $data['website_mode'] ?? 'global';
            }
        }
        return 'global';
    }

    // Fake "belongsTo-like" relation that works in both modes
    public function getWebsiteAttribute()
    {
        if (! $this->relationLoaded('websites')) {
            $this->load('websites');
        }

        return $this->websites->first();
    }

    public function websites(): BelongsToMany
    {
        $websiteClass = wncms()->getModelClass('website');
        return $this->morphToMany(
            $websiteClass,
            'model',
            'model_has_websites',
            'model_id',
            'website_id'
        )->withPivot('model_type');
    }

    /**
     * Apply website filter to a query.
     */
    public function scopeForWebsite(Builder $q, ?int $websiteId = null): Builder
    {
        return static::applyWebsiteScope($q, $websiteId);
    }

    /**
     * Reusable helper to apply website scoping manually.
     */
    public static function applyWebsiteScope(Builder $q, ?int $websiteId = null): Builder
    {
        $mode = static::getWebsiteMode();
        $websiteId = $websiteId ?? wncms()->website()->get()?->id;

        if (in_array($mode, ['single', 'multi']) && $websiteId) {
            $q->whereHas('websites', function ($sub) use ($websiteId) {
                $sub->where('website_id', $websiteId);
            });
        }

        return $q;
    }

    public function bindWebsites(array|int $websiteIds): array
    {
        $websiteIds = (array)$websiteIds;
        $mode = static::getWebsiteMode();

        if ($mode === 'single') {
            // force only one website: take the first
            return $this->websites()->sync([reset($websiteIds)]);
        } elseif ($mode === 'multi') {
            return $this->websites()->syncWithoutDetaching($websiteIds);
        }

        return [];
    }

    public function unbindWebsites(array|int $websiteIds): void
    {
        $websiteIds = (array)$websiteIds;
        $mode = static::getWebsiteMode();

        if ($mode === 'single') {
            // if unbinding the current one, just detach all
            $this->websites()->detach($websiteIds);
        } elseif ($mode === 'multi') {
            $this->websites()->detach($websiteIds);
        }
    }

    public function bindAllWebsites(): void
    {
        $websiteClass = wncms()->getModelClass('website');
        $allWebsiteIds = $websiteClass::pluck('id')->toArray();

        if (static::getWebsiteMode() === 'single') {
            // pick the first one if "single"
            $this->websites()->sync([reset($allWebsiteIds)]);
        } else {
            $this->websites()->syncWithoutDetaching($allWebsiteIds);
        }
    }

    public function unbindAllWebsites(): void
    {
        $this->websites()->detach();
    }
}
