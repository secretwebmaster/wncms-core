<?php

namespace Wncms\Services\Managers;

use Illuminate\Support\Facades\File;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Str;

class TagManager extends ModelManager
{
    protected string $cacheKeyPrefix = 'wncms_tag';
    protected bool $shouldAuth = false;
    protected string|array $cacheTags = ['tags'];

    public function getModelClass(): string
    {
        return wncms()->getModelClass('tag');
    }

    public function getByName(string $tagName, ?string $tagType = null, array $withs = [], ?string $locale = null, bool $cache = true)
    {
        $locale ??= LaravelLocalization::getCurrentLocale();
        $modelClass = $this->getModelClass();

        $query = $modelClass::query();

        if (!empty($withs)) {
            $query->with($withs);
        }

        $query->where('type', $tagType);
        $query->where(function ($q) use ($tagName, $locale) {
            $q->where('name', $tagName)
                ->orWhere('slug', $tagName)
                ->orWhereHas('translations', function ($subq) use ($tagName, $locale) {
                    $subq->where('field', 'name')
                        ->where('value', $tagName)
                        ->where('locale', $locale);
                });
        });

        if ($cache && gss('enable_cache')) {
            $cacheKey = $this->getCacheKey(__METHOD__, [
                'tagType' => $tagType,
                'tagName' => $tagName,
                'locale' => $locale,
            ]);
            return wncms()->cache()->remember($cacheKey, $this->getCacheTime(), fn() => $query->first(), $this->getCacheTag());
        }

        return $query->first();
    }

    /**
     * Build the base query for retrieving a list of tags.
     *
     * Supported $options keys:
     * - tag_type: string - Filter by tag type(s). Can be a single type or comma-separated list (e.g., 'post_category,post_tag').
     * - tag_ids: array|string - Filter tags by ID, slug, name, or translated name.
     * - with: array - Eloquent relationships to eager load (e.g., ['translations']).
     * - has_models: bool - If true, only include tags that have associated models.
     * - model_type: string - The model relationship name to check (e.g., 'posts', 'videos').
     * - only_current_website: bool - If true, restrict tags to those used by models on the current website.
     * - locale: string - Language to use for translated name filtering (default: current app locale).
     * - parent_only: bool - If true, only include top-level tags (i.e., where parent_id is null).
     * - is_random: bool - If true, results will be returned in random order.
     * - sort: string - Column to sort by (default: 'sort').
     * - direction: string - Sort direction ('asc' or 'desc', default: 'desc').
     *
     * @param array $options
     * @return mixed
     */
    protected function buildListQuery(array $options): mixed
    {
        $q = $this->query();

        $tagType = $options['tag_type'] ?? 'post_category';
        $tagIds = $options['tag_ids'] ?? null;
        $withs = $options['with'] ?? [];
        $hasModels = $options['has_models'] ?? false;
        $modelType = $options['model_type'] ?? 'posts';
        $onlyCurrentWebsite = $options['only_current_website'] ?? false;
        $locale = $options['locale'] ?? app()->getLocale();
        $parentOnly = $options['parent_only'] ?? false;
        $isRandom = $options['is_random'] ?? false;

        // updated naming
        $sort = $options['sort'] ?? 'sort';
        $direction = $options['direction'] ?? 'desc';

        // Filter by tag type(s)
        if (str_contains($tagType, ',')) {
            $q->whereIn('type', explode(',', $tagType));
        } else {
            $q->where('type', $tagType);
        }

        // Filter by tag ID, slug, or translated name
        if (!empty($tagIds)) {
            $tagIds = is_array($tagIds) ? $tagIds : explode(',', $tagIds);
            $q->where(function ($subq) use ($tagIds, $locale) {
                $subq->orWhereIn('tags.id', $tagIds)
                    ->orWhereIn('tags.name', $tagIds)
                    ->orWhereIn('tags.slug', $tagIds)
                    ->orWhereHas('translations', function ($q) use ($tagIds, $locale) {
                        $q->where('field', 'name')
                            ->where('locale', $locale)
                            ->whereIn('value', $tagIds);
                    });
            });
        }

        // Ensure tags are related to given model (e.g., posts)
        if ($hasModels && $modelType) {
            if ($onlyCurrentWebsite) {
                $websiteId = wncms()->website()->getCurrent()?->id;
                $q->whereHas($modelType, fn($q) => $q->whereRelation('websites', 'websites.id', $websiteId));
            } else {
                $q->whereHas($modelType);
            }
        }

        // Eager load relationships
        $this->applyWiths($q, $withs);

        // Only top-level tags
        if ($parentOnly) {
            $q->whereNull('parent_id');
        }

        // Apply sorting (random disables ordering)
        $this->applyOrdering($q, $sort, $direction, $isRandom);

        return $q;
    }

    public function getArray(string $tagType = 'post_category', int $count = 0, string $columnName = 'name', ?string $keyName = null, array|string|null $tagIds = null): array
    {
        $tags = $this->getList([
            'tag_type' => $tagType,
            'count' => $count,
            'tag_ids' => $tagIds,
            'cache' => true,
        ]);

        $array = [];

        foreach ($tags as $tag) {
            /** @var \Wncms\Models\Tag|\Illuminate\Database\Eloquent\Model $tag */

            if ($keyName) {
                $array[$tag->{$keyName}] = $tag->{$columnName};
            } else {
                $array[] = $tag->{$columnName};
            }
        }

        return $array;
    }

    //TODO: Add cache support and allow to disable
    public function getTypes(array|string|null $tagIds = null): array
    {
        $q = $this->query()->select('type');

        if (!empty($tagIds)) {
            $tagIds = is_array($tagIds) ? $tagIds : explode(',', $tagIds);
            $q->whereIn('id', $tagIds);
        }

        return $q->distinct()->pluck('type')->toArray();
    }

    public function getTagKeywordList(string $tagType = 'post_category', $column = 'id'): array
    {
        $tags = $this->getList([
            'tag_type' => $tagType,
            'withs' => ['keywords'],
            'cache' => true,
        ]);

        $availableTagKeywords = [];

        foreach ($tags as $tag) {
            /** @var \Wncms\Models\Tag|\Illuminate\Database\Eloquent\Model $tag */

            foreach ($tag->keywords as $keyword) {
                $availableTagKeywords[$tag->{$column}][] = $keyword->name;
            }
        }

        return $availableTagKeywords;
    }

    public function getTagsToBind(string $tagType = 'post_category', array $contents = [], $column = 'id'): array
    {
        $availableTagKeywords = $this->getTagKeywordList($tagType, $column);
        $tagKeysToBind = [];

        foreach ($availableTagKeywords as $tagKey => $keywords) {
            foreach ($contents as $content) {
                foreach ($keywords as $keyword) {
                    if (stripos($content, $keyword) !== false) {
                        $tagKeysToBind[] = $tagKey;
                    }
                }
            }
        }

        return array_unique($tagKeysToBind);
    }

    public function getWord(string $modelName = 'post', $subType = 'category')
    {
        if (strpos($subType, $modelName . "_") !== false) {
            $subType = str_replace($modelName . "_", "", $subType);
        }
        return __('wncms::word.' . $modelName) . __('wncms::word.word_separator') . __('wncms::word.' . $subType);
    }

    public function getTagifyDropdownItems($type, $nameColumn = 'name', $valueColumn = null, $cache = true): array
    {
        $tags = $this->getList([
            'tag_type' => $type,
            'select' => [$nameColumn, $valueColumn ?? $nameColumn],
            'cache' => $cache,
        ]);

        return collect($tags)->map(function ($tag) use ($nameColumn, $valueColumn) {
            /** @var \Wncms\Models\Tag $tag */
            return [
                'name' => $tag->{$nameColumn},
                'value' => $tag->{$valueColumn ?? $nameColumn}
            ];
        })->toArray();
    }

    public function getUrl($tag): string
    {
        if (empty($tag) || empty($tag->type)) {
            return 'javascript:;';
        }

        // Split type: e.g. product_category
        $segments = explode('_', $tag->type);
        if (count($segments) < 2) {
            return 'javascript:;';
        }

        [$modelKey, $key] = $segments;

        // Get model class dynamically
        $modelClass = wncms()->getModelClass($modelKey);
        if (!class_exists($modelClass)) {
            return 'javascript:;';
        }

        $instance = new $modelClass();
        $packageId = method_exists($modelClass, 'getPackageId')
            ? $modelClass::getPackageId()
            : null;

        // Determine route from model tagFormats
        $routeName = $instance->tagFormats[$tag->type]
            ?? "frontend." . str($modelKey)->plural() . ".tag";

        // Generate URL if route exists
        if (wncms_route_exists($routeName)) {
            return route($routeName, ['type' => $key, 'slug' => $tag->slug]);
        }

        return 'javascript:;';
    }

    public function getAllTagTypes()
    {
        $models = wncms()->getModels();

        $metas = [];
        foreach ($models as $model) {
            $meta = $model::getTagMeta();
            if (!empty($meta)) {
                $metas = array_merge($metas, $meta);
            }
        }

        return $metas;
    }

    public function getTagTypes($modelClass, $key = 'key', $implode = false)
    {
        if (!class_exists($modelClass)) {
            return [];
        }

        $meta = $modelClass::getTagMeta();

        // Extract values based on $key
        if ($key === 'short') {
            $values = array_map(fn($item) => $item['short'] ?? null, $meta);
        } elseif ($key === 'route') {
            $values = array_map(fn($item) => $item['route'] ?? null, $meta);
        } elseif ($key === 'key') {
            $values = array_map(fn($item) => $item['key'] ?? null, $meta);
        } else {
            // return the raw meta array
            return $meta;
        }

        // Handle implode behavior
        if ($implode === false || $implode === '') {
            return $values;
        }

        // Implode using the supplied string
        return implode($implode, $values);
    }


    /**
     * Get a human-readable label for a tag type key, e.g. 'product_category'.
     *
     * Rule:
     * 1. Try "{$packageId}::word.{$modelKey}_{$short}"
     * 2. Try "wncms::word.{$modelKey}_{$short}"
     * 3. Try "wncms::word.{$short}"
     * 4. Fallback ucfirst($short)
     */
    // public function getTypeLabel(string $typeKey, ?string $locale = null): string
    // {
    //     try {
    //         $entry = $this->getTagMetaByKey($typeKey);
    //         if (! $entry) {
    //             return ucfirst($typeKey);
    //         }

    //         $modelClass = $entry['model'];
    //         $meta       = $entry['meta'];
    //         $short      = $meta['short'] ?? $typeKey;

    //         $modelKey  = method_exists($modelClass, 'getModelKey') ? $modelClass::getModelKey() : class_basename($modelClass);
    //         $packageId = method_exists($modelClass, 'getPackageId') ? $modelClass::getPackageId() : null;

    //         // 1. Package-specific translation
    //         if ($packageId) {
    //             $key        = "{$packageId}::word.{$modelKey}_{$short}";
    //             $translated = __($key, locale: $locale);
    //             if ($translated !== $key) {
    //                 return $translated;
    //             }
    //         }

    //         // 2. Core translation: wncms::word.product_category
    //         $coreKey      = "wncms::word.{$modelKey}_{$short}";
    //         $coreLabel    = __($coreKey, locale: $locale);
    //         if ($coreLabel !== $coreKey) {
    //             return $coreLabel;
    //         }

    //         // 3. Fallback using generic subtype word
    //         $subKey       = "wncms::word.{$short}";
    //         $subLabel     = __($subKey, locale: $locale);
    //         if ($subLabel !== $subKey) {
    //             return $subLabel;
    //         }

    //         // Final fallback
    //         return ucfirst($short);
    //     } catch (\Throwable $e) {
    //         report($e);
    //         return ucfirst($typeKey);
    //     }
    // }
}
