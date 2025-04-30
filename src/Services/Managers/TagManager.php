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
        return config('wncms.models.tag', \Wncms\Models\Tag::class);
    }

    public function getByName(string $tagName, ?string $tagType = null, array $withs = [], ?string $locale = null, bool $cache = true)
    {
        $locale ??= LaravelLocalization::getCurrentLocale();
    
        $wheres = [
            ['type', '=', $tagType],
            [
                function ($q) use ($tagName, $locale) {
                    $q->where("name", $tagName)
                      ->orWhere("slug", $tagName)
                      ->orWhereHas('translations', function ($subq) use ($tagName, $locale) {
                          $subq->where('field', 'name')->where('value', $tagName)->where('locale', $locale);
                      });
                }
            ]
        ];
    
        return $this->get([
            'withs'   => $withs,
            'wheres' => $wheres,
            'cache'  => $cache,
        ]);
    }
    
    
    /**
     * Retrieve a list of tags using unified options array.
     *
     * Supported $options:
     * - tag_type (string): Tag type(s), e.g. 'post_category', or comma-separated types.
     * - tag_ids (array|string|null): Tag IDs, names, or slugs to match.
     * - with (array): Eager-loaded relationships.
     * - has_models (bool): Whether to require tags having related models.
     * - model_type (string): Related model name, e.g. 'posts'.
     * - only_current_website (bool): Restrict to current website only.
     * - locale (string|null): Fallback to current locale if null.
     * - parent_only (bool): Only top-level tags if true.
     * - order (string): Field to order by.
     * - sequence (string): 'asc' or 'desc'.
     * - count (int): Limit number of results.
     * - page_size (int): Results per page.
     * - page (int): Page number.
     * - is_random (bool): Randomize results, disables cache.
     * - cache (bool): Whether to use cache (default true).
     *
     * @param array $options
     * @return Collection|LengthAwarePaginator
     */
    public function getList(array $options = []): mixed
    {
        return parent::getList($options);
    }

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
        $order = $options['order'] ?? 'order_column';
        $sequence = $options['sequence'] ?? 'desc';
    
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
                        $q->where('field', 'name')->where('locale', $locale)->whereIn('value', $tagIds);
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
        $this->applyOrdering($q, $order, $sequence, $isRandom);
    
        return $this->finalizeResult($q, $options);
    }
    

    public function getArray(string $tagType = 'post_category', int $count = 0, string $columnName = 'name', string $keyName = null, array|string|null $tagIds = null): array
    {
        $tags = $this->getList([
            'tag_type' => $tagType,
            'count' => $count,
            'tag_ids' => $tagIds,
            'cache' => true,
        ]);
    
        $array = [];
    
        foreach ($tags as $tag) {
            if ($keyName) {
                $array[$tag->{$keyName}] = $tag->{$columnName};
            } else {
                $array[] = $tag->{$columnName};
            }
        }
    
        return $array;
    }
    

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
    

    public function getModelsWithHasTagsTraits()
    {
        // dd($request->all());
        $appModelsWithHasTagsTraits = collect(File::allFiles(app_path('Models')))
            ->map(function ($file) {
                $relativePath = Str::replaceFirst(app_path('Models') . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $class = 'App\\Models\\' . Str::replace('.php', '', str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath));
                return class_exists($class) ? app($class) : null;
            })
            ->filter(function ($model) {
                $reflection = new \ReflectionClass($model);
                return in_array("Wncms\Tags\HasTags", $reflection->getTraitNames());
            });

        $packageModelsWithHasTagsTraits = collect(File::allFiles(wncms()->getPackagePath('Models')))
            ->map(function ($file) {

                $relativePath = Str::replaceFirst(wncms()->getPackagePath('Models') . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $class = 'Wncms\\Models\\' . Str::replace('.php', '', str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath));
                return class_exists($class) ? app($class) : null;
            })
            ->filter(function ($model) {
                $reflection = new \ReflectionClass($model);
                return in_array("Wncms\Tags\HasTags", $reflection->getTraitNames());
            });

        $modelsWithHasTagsTraits = $appModelsWithHasTagsTraits->merge($packageModelsWithHasTagsTraits);

        $tagTypes = [];
        $index = 0;
        foreach ($modelsWithHasTagsTraits as $modelsWithHasTagsTrait) {
            foreach (\Wncms\Models\Tag::SUBTYPES as $subType) {
                $tagTypes[$index]['slug'] = str()->singular($modelsWithHasTagsTrait->getTable()) . "_" . $subType;
                $tagTypes[$index]['name'] = $this->getWord(str()->singular($modelsWithHasTagsTrait->getTable()), $subType);
                $index++;
            }
        }
        return $tagTypes;
    }

    public function getWord(string $modelName = 'post', $subType = 'category')
    {
        if (strpos($subType, $modelName . "_") !== false) {
            $subType = str_replace($modelName . "_", "", $subType);
        }
        return __('wncms::word.' . $modelName) . __('wncms::word.word_separator') . __('wncms::word.' . $subType);
    }

    public function getTagifyDropdownItems($type, $nameColumn = 'name', $valueColumn = null): array
    {
        $tags = $this->getList([
            'tag_type' => $type,
            'select' => [$nameColumn, $valueColumn ?? $nameColumn],
            'cache' => true,
        ]);
    
        return collect($tags)->map(function ($tag) use ($nameColumn, $valueColumn) {
            return [
                'name' => $tag->{$nameColumn},
                'value' => $tag->{$valueColumn ?? $nameColumn}
            ];
        })->toArray();
    }
    
}
