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

    public function get($options = []): ?Model
    {
        if (!empty($options['type'])) {
            $options['wheres'][] = ['type', $options['type']];
            unset($options['type']);
        }

        return parent::get($options);
    }

    //% Deprecated: use get()
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

    protected function applyExtraFilters($q, array $options): void
    {
        $type = $options['type'] ?? null;

        if (!empty($type)) {
            $q->where('type', $type);
        }
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

        $meta = collect($this->getAllTagTypes())->where('key', $tag->type)->first();

        // if($tag->type == 'post_category') {
        //     dd($meta['route'], wncms_route_exists($meta['route']));
        // }

        if (!empty($meta['route']) && wncms_route_exists($meta['route'])) {
            return route($meta['route'], [
                'type' => $meta['short'],
                'slug' => $tag->slug,
            ]);
        }

        return 'javascript:;';
    }

    public function getAllTagTypes()
    {
        $models = wncms()->getModels();

        $metas = [];
        foreach ($models as $model) {
            if (!method_exists($model, 'getTagMeta')) {
                continue;
            }
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
        if (empty($meta) || !is_array($meta)) {
            return [];
        }

        // Extract the correct field
        if ($key === 'short') {
            $values = array_map(fn($i) => $i['short'] ?? null, $meta);
        } elseif ($key === 'route') {
            $values = array_map(fn($i) => $i['route'] ?? null, $meta);
        } elseif ($key === 'key') {
            $values = array_map(fn($i) => $i['key'] ?? null, $meta);
        } else {
            // Unknown key → return raw meta
            return $meta;
        }

        // Clean null values
        $values = array_values(array_filter($values));

        // If no implode → return array
        if ($implode === false) {
            return $values;
        }

        // If implode is string → always return string
        if (is_string($implode)) {
            return count($values) ? implode($implode, $values) : '';
        }

        // If implode something else (rare use-case)
        return $values;
    }

    public function getTagTypeLabel(string $modelClass, string $tagType): string
    {
        // modelClass must exist
        if (!class_exists($modelClass)) {
            return ucfirst(str_replace('_', ' ', $tagType));
        }

        // retrieve package and metas from model
        $packageId = $modelClass::getPackageId();
        $metas = $modelClass::getTagMeta();
        $meta = collect($metas)->firstWhere('key', $tagType);

        // if no meta found → fallback
        if (!$meta) {
            return ucfirst(str_replace('_', ' ', $tagType));
        }

        // 1. Primary translation: <package>::word.<product_tag>
        if ($packageId) {
            $translated = __("{$packageId}::word.{$tagType}");
            if ($translated !== "{$packageId}::word.{$tagType}") {
                return $translated;
            }
        }

        // 2. Secondary translation: <package>::word.<tag>
        if ($packageId && !empty($meta['short'])) {
            $translated = __("{$packageId}::word.{$meta['short']}");
            if ($translated !== "{$packageId}::word.{$meta['short']}") {
                return $translated;
            }
        }

        // 3. Final fallback: human readable text
        return ucfirst(str_replace('_', ' ', $tagType));
    }

    public function getTagTypesForRoute($modelClass)
    {
        // Get types as a pipe-separated string
        $types = $this->getTagTypes($modelClass, 'short', '|');

        // If empty, return a safe dummy string so the route still compiles
        // This ensures Symfony gets a valid regex
        return $types ?: 'NO_AVAILABLE_TAG_TYPES';
    }
}
