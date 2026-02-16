<?php

namespace Wncms\Services\Managers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class ModelManager
{
    protected string $cacheKeyPrefix = 'wncms_model';
    protected bool $shouldAuth = false;
    protected string|array $cacheTags = ['models'];

    /**
     * Check whether a filter value should be treated as present.
     */
    protected function hasFilterValue(mixed $value, bool $allowFalse = false): bool
    {
        if ($value === null || $value === '' || $value === []) {
            return false;
        }

        if (!$allowFalse && $value === false) {
            return false;
        }

        return true;
    }

    /**
     * Returns the model class name that this manager handles.
     * 
     * @return string
     */
    abstract public function getModelClass(): string;

    /**
     * Get a single model instance by ID or slug, with optional relations and where conditions.
     *
     * @param array $options ['id' => int|string, 'slug' => string, 'withs' => array, 'wheres' => array, 'cache' => bool]
     * @return Model|null
     */
    public function get(array $options = []): ?Model
    {
        $id = $options['id'] ?? null;
        $name = $options['name'] ?? null;
        $slug = $options['slug'] ?? null;
        $withs = $options['withs'] ?? [];
        $wheres = $options['wheres'] ?? [];
        $useCache = $options['cache'] ?? true;

        // if (empty($id) && empty($slug)) return null;

        $func = function () use ($id, $name, $slug, $withs, $wheres, $options) {
            $modelClass = $this->getModelClass();
            $q = $modelClass::query();

            if ($this->hasFilterValue($withs)) {
                $q->with($withs);
            }

            if ($this->hasFilterValue($wheres)) {
                $this->applyWhereConditions($q, $wheres);
            }

            if ($this->hasFilterValue($id, true)) {
                $q->where('id', $id);
            }

            if ($this->hasFilterValue($slug, true)) {
                $q->where('slug', $slug);
            }

            if ($this->hasFilterValue($name, true)) {
                $q->where('name', $name);
            }

            $this->applyExtraFilters($q, $options);

            return $q->first();
        };

        if (gss('enable_cache') && $useCache) {
            $cacheKey = $this->getCacheKey(__METHOD__, $options);
            $cacheTime = $this->getCacheTime();
            return wncms()->cache()->remember($cacheKey, $cacheTime, $func, $this->getCacheTag());
        }

        return $func();
    }

    /**
     * Run a function with optional caching.
     *
     * @param callable $func
     * @param bool $useCache
     * @param string|int $cacheKey
     * @param int $cacheTime
     * @param string|array|null $cacheTag
     * @return mixed
     */
    public function run($func, $useCache = true, $cacheKey = 3600, $cacheTime = 3600, $cacheTag = null)
    {
        if (gss('enable_cache') && $useCache) {
            return wncms()->cache()->remember($cacheKey, $cacheTime, $func, $cacheTag);
        } else {
            return $func();
        }
    }

    /**
     * Get a list of models using provided filter options with optional caching.
     * 
     * @param array $options
     * @return mixed Laravel Collection or LengthAwarePaginator
     */
    public function getList(array $options = []): mixed
    {
        // User preference: cache or not
        $useCache = $options['cache'] ?? true;

        // Detect random mode
        $isRandom = $options['is_random'] ?? false;

        // Determine cache time based on random rules
        // Rule:
        // - is_random && cache=false  → no cache
        // - is_random && cache=true   → cached normally
        // - is_random && cache not set → cached normally (default)
        // - non-random uses existing global cache logic
        if ($isRandom) {
            if ($useCache === false) {
                // Completely disable cache for this random query
                $cacheTime = 0;
            } else {
                // Random but cached (default)
                $cacheTime = gss('enable_cache') ? gss('data_cache_time') : 0;
            }
        } else {
            // Normal ordering — same as before
            $cacheTime = gss('enable_cache') ? gss('data_cache_time') : 0;
        }

        // Create cache key
        $cacheKey = wncms()->cache()->createKey(
            $this->cacheKeyPrefix,
            'getList',
            $this->shouldAuth ? (auth()->id() ?? false) : false,
            $options,
            wncms()->website()->get()?->url
        );

        // Query callback
        $callback = function () use ($options) {
            $q = $this->buildListQuery($options);
            return $this->finalizeResult($q, $options);
        };

        // Run with or without cache
        return $useCache
            ? wncms()->cache()->remember($cacheKey, $cacheTime, $callback, $this->getCacheTag())
            : $callback();
    }


    /**
     * Get the count of models based on provided options.
     * 
     * @param array $options
     * @return int
     */
    public function getCount(array $options = []): int
    {
        $useCache = $options['cache'] ?? true;

        $cacheKey = wncms()->cache()->createKey(
            $this->cacheKeyPrefix,
            'getCount',
            $this->shouldAuth ? (auth()->id() ?? false) : false,
            $options,
            wncms()->website()->get()?->url
        );

        $cacheTime = gss('enable_cache') ? gss('data_cache_time') : 0;

        $callback = function () use ($options) {
            $q = $this->buildListQuery($options);
            return $q->count();
        };

        return $useCache
            ? wncms()->cache()->remember($cacheKey, $cacheTime, $callback, $this->getCacheTag())
            : $callback();
    }

    /**
     * Build the base query for getList. Should be implemented by each manager.
     * 
     * @param array $options
     * @return mixed Laravel Collection or LengthAwarePaginator
     */
    abstract protected function buildListQuery(array $options): mixed;

    /**
     * Return a query builder for the current model class.
     * 
     * @return Builder
     */
    protected function query(): Builder
    {
        return $this->getModelClass()::query();
    }

    /**
     * apply tag filters to query
     * @param Builder $q
     * @param mixed $tags
     * @param string|null $tagType
     */
    protected function applyTagFilter(Builder $q, mixed $tags, ?string $tagType = null)
    {
        // return if empty
        if ($tags === null || $tags === '' || $tags === []) return;

        // load tag model class from config and fallback through model resolver
        $tagConfig = config('wncms.models.tag');
        $tagModelClass = is_array($tagConfig) ? ($tagConfig['class'] ?? null) : $tagConfig;
        if (!is_string($tagModelClass) || !class_exists($tagModelClass)) {
            $tagModelClass = wncms()->getModelClass('tag');
        }

        // normalize single tag model
        if (is_object($tags) && is_a($tags, $tagModelClass)) {
            $tags = [$tags];
        }

        // normalize string "a,b,c" to ['a','b','c']
        if (is_string($tags)) {
            $tags = array_filter(array_map('trim', explode(',', $tags)));
        }

        // ensure array
        if (!is_array($tags)) {
            $tags = [$tags];
        }

        // fallback type
        $tagType = $tagType ?? $this->defaultTagType;

        $ids = [];
        $names = [];

        foreach ($tags as $tag) {
            // tag model instance or subclass
            if (is_object($tag) && is_a($tag, $tagModelClass)) {
                $ids[] = $tag->id;
            } elseif (is_numeric($tag)) {
                $ids[] = (int) $tag;
            } elseif (is_string($tag) && $tag !== '') {
                $names[] = $tag;
            }
        }

        // if still nothing valid, force no result instead of everything
        if (!$ids && !$names) {
            $q->whereNull('id');
            return;
        }

        // fetch tag names for given ids/names and type
        $query = $tagModelClass::query()->where('type', $tagType);
        $query->where(function ($sub) use ($ids, $names) {
            // match ids
            if ($ids) $sub->orWhereIn('id', $ids);
            // match names
            if ($names) $sub->orWhereIn('name', $names);
        });

        $tagNames = $query->pluck('name')->toArray();

        // no tag names found → force no result
        if (!$tagNames) {
            $q->whereNull('id');
            return;
        }

        // apply tag filter to main query
        $q->withAnyTags($tagNames, $tagType);
    }

    /**
     * Apply filter to exclude models with specified tag IDs.
     *
     * @param Builder $q
     * @param array|string|int|null $excludedTagIds
     */
    protected function applyExcludedTags(Builder $q, array|string|int|null $excludedTagIds): void
    {
        if (!$this->hasFilterValue($excludedTagIds)) return;

        if (is_string($excludedTagIds)) {
            $excludedTagIds = explode(',', $excludedTagIds);
        }

        $q->whereDoesntHave('tags', function ($query) use ($excludedTagIds) {
            $query->whereIn('tags.id', (array) $excludedTagIds);
        });
    }

    /**
     * Apply keyword search across specified columns.
     * 
     * @param Builder $q
     * @param array|string|null $keywords
     * @param array $columns
     */
    protected function applyKeywordFilter(Builder $q, array|string|null $keywords, array $columns = ['title'])
    {
        if (!$this->hasFilterValue($keywords)) return;
        if (is_string($keywords)) $keywords = explode(',', $keywords);

        $q->where(function ($subq) use ($keywords, $columns) {
            foreach ($keywords as $keyword) {
                foreach ($columns as $col) {
                    $subq->orWhere($col, 'like', "%$keyword%");
                }
            }
        });
    }

    /**
     * Apply a whereIn filter.
     */
    protected function applyIds(Builder $q, string $column, array|string|int|null $ids)
    {
        if (!$this->hasFilterValue($ids)) return;
        if (is_string($ids)) $ids = explode(',', $ids);
        $q->whereIn($column, (array)$ids);
    }

    /**
     * Apply a whereNotIn filter.
     */
    protected function applyExcludeIds(Builder $q, string $column, array|string|int|null $excluded)
    {
        if (!$this->hasFilterValue($excluded)) return;
        if (is_string($excluded)) $excluded = explode(',', $excluded);
        $q->whereNotIn($column, (array)$excluded);
    }

    /**
     * Apply multiple where conditions.
     */
    protected function applyWhereConditions(Builder $q, array $wheres = [])
    {
        foreach ($wheres as $where) {
            if ($where instanceof \Closure) {
                $q->where($where);
            } elseif (is_array($where)) {
                if (isset($where[0]) && $where[0] instanceof \Closure) {
                    $q->where($where[0]);
                } elseif (count($where) === 3) {
                    $q->where($where[0], $where[1], $where[2]);
                } elseif (count($where) === 2) {
                    $q->where($where[0], $where[1]);
                } else {
                    info('Invalid where condition in manager: ' . json_encode($where));
                }
            }
        }
    }

    /**
     * Apply a status filter.
     */
    protected function applyStatus(Builder $q, string $column, string|array|int|bool|null $status): void
    {
        if (!$this->hasFilterValue($status, true)) {
            return;
        }

        if (is_string($status)) {
            $status = explode(',', $status);
        }

        $q->whereIn($column, (array) $status);
    }

    /**
     * Apply eager-loading to the query.
     */
    protected function applyWiths(Builder $q, array $withs)
    {
        if ($this->hasFilterValue($withs)) {
            $q->with($withs);
        }
    }

    /**
     * Apply ordering rules to the query.
     *
     * @param Builder $q
     * @param string $sort       // column to sort by (default: 'sort')
     * @param string $direction  // asc|desc
     * @param bool $isRandom     // random ordering disables explicit sorting
     */
    protected function applyOrdering(Builder $q, string $sort, string $direction = 'desc', bool $isRandom = false)
    {
        if ($isRandom) {
            $q->inRandomOrder();
        } else {
            $q->orderBy($sort, in_array($direction, ['asc', 'desc']) ? $direction : 'desc');
            $q->orderBy('id', 'desc');
        }
    }

    /**
     * Apply select columns to query.
     */
    protected function applySelect(Builder $q, array|string|null $select = ['*'])
    {
        if (is_string($select)) $select = explode(',', $select);
        $q->select($select);
    }

    /**
     * Apply offset to query.
     */
    protected function applyOffset(Builder $q, int $offset = 0)
    {
        if ($offset > 0) {
            $q->offset($offset);
        }
    }

    /**
     * Apply a limit to query results.
     */
    protected function applyLimit(Builder $q, int $count = 0)
    {
        if ($count > 0) {
            $q->limit($count);
        }
    }

    /**
     * Apply website scoping to the query.
     */
    protected function applyWebsiteId(Builder $q, ?int $websiteId = null): void
    {
        $modelClass = $this->getModelClass();
        if (!$this->isModelWebsiteScoped() || !method_exists($modelClass, 'applyWebsiteScope')) {
            return;
        }

        $modelClass::applyWebsiteScope($q, $websiteId);
    }

    /**
     * Apply user scoping to the query.
     */
    protected function applyUserId(Builder $q, ?int $userId = null): void
    {
        if (!$this->hasFilterValue($userId, true)) {
            return;
        }

        $q->where('user_id', $userId);
    }

    /**
     * Apply extra filters based on options.
     * 
     * @param Builder $q
     * @param array $options
     */
    protected function applyExtraFilters($q, array $options): void
    {
        // default: nothing
    }

    /**
     * Finalize the result: apply pagination, limits, or fetch all.
     * 
     * @param Builder $q
     * @param array $options
     * @return Collection|LengthAwarePaginator
     */
    protected function finalizeResult(Builder $q, array $options): Collection|LengthAwarePaginator
    {
        $pageSize = $options['page_size'] ?? 0;
        $pageName = $options['page_name'] ?? 'page';
        $count = $options['count'] ?? 0;

        if ($pageSize > 0) {
            $result = $q->paginate($pageSize, ['*'], $pageName, $options['page'] ?? null);

            if ($count > 0) {
                return wncms()->paginateWithLimit(
                    $result,
                    $pageSize,
                    $count,
                    $options['page'] ?? null,
                    $pageName
                );
            }

            return $result;
        }

        if ($count > 0) {
            $q->limit($count);
        }

        return $q->get();
    }

    /**
     * Get a query builder for the website relation.
     * 
     * @param string $relation
     * @param int|null $websiteId
     */
    protected function getWebsiteQuery(string $relation, ?int $websiteId = null): mixed
    {
        $website = wncms()->website()->get($websiteId);

        if (method_exists($website, $relation)) {
            return $website->{$relation}();
        }

        throw new \Exception("Website does not support relation: {$relation}");
    }

    /**
     * Get the cache key prefix for this manager.
     */
    public function getCacheKeyPrefix(): string
    {
        return $this->cacheKeyPrefix;
    }

    /**
     * Get the model key used in configuration.
     */
    public function getModelKey(): string
    {
        // Return custom model key if set
        if (property_exists($this, 'modelKey')) {
            return $this->modelKey;
        }

        // Derive model key from model class
        $modelClass = $this->getModelClass();
        $modelKey = array_search($modelClass, array_column(config('wncms.models'), 'class'));
        if ($modelKey === false) {
            $modelKey = str()->snake(class_basename($modelClass));
        }

        return $modelKey;
    }

    /**
     * Get website mode for current model with model-level fallback.
     */
    public function getModelMultiWebsiteMode(): string
    {
        $modelClass = $this->getModelClass();

        if (method_exists($modelClass, 'getMultiWebsiteMode')) {
            return $modelClass::getMultiWebsiteMode();
        }

        if (method_exists($modelClass, 'getWebsiteMode')) {
            return $modelClass::getWebsiteMode();
        }

        $mode = config('wncms.models.' . $this->getModelKey() . '.website_mode', 'global');
        return in_array($mode, ['global', 'single', 'multi'], true) ? $mode : 'global';
    }

    /**
     * Check whether current model needs website scoping.
     */
    public function isModelWebsiteScoped(): bool
    {
        return in_array($this->getModelMultiWebsiteMode(), ['single', 'multi'], true);
    }

    /**
     * Get the package key used in configuration.
     */
    public function getPackageKey(): string
    {
        return $this->packageKey ?? 'wncms';
    }

    /**
     * Get the cache tag(s) for this manager.
     */
    public function getCacheTag(): array|string
    {
        return $this->cacheTags;
    }

    /**
     * Get the default tag type for this model.
     */
    public function getDefaultTagType(): string
    {
        return $this->defaultTagType;
    }

    /**
     * Generate a cache key for a method with arguments.
     */
    public function getCacheKey(string $method, array $args = []): string
    {
        return wncms()->cache()->createKey(
            prefix: $this->getCacheKeyPrefix(),
            method: $method,
            auth: $this->shouldAuth,
            args: $args
        );
    }

    /**
     * Get the cache time in seconds.
     */
    public function getCacheTime(int $fallback = 0): int
    {
        return gss('enable_cache') ? gss('data_cache_time', $fallback) : 0;
    }

    /**
     * Automatically add order by columns to select clause to avoid SQL errors.
     */
    protected function autoAddOrderByColumnsToSelect(Builder $q, array $select): array
    {
        $columns = collect($q->getQuery()->orders ?? [])
            ->pluck('column')
            ->filter() // remove raw expressions
            ->unique()
            ->values();

        foreach ($columns as $column) {
            if (!in_array($column, $select)) {
                $select[] = $column;
            }
        }

        return $select;
    }
}
