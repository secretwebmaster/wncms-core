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

        $func = function () use ($id, $name, $slug, $withs, $wheres) {
            $modelClass = $this->getModelClass();
            $q = $modelClass::query();

            if (!empty($withs)) {
                $q->with($withs);
            }

            if (!empty($wheres)) {
                $this->applyWhereConditions($q, $wheres);
            }

            if (!empty($id)) {
                $q->where('id', $id);
            }

            if (!empty($slug)) {
                $q->where('slug', $slug);
            }

            if (!empty($name)) {
                $q->where('name', $name);
            }

            return $q->first();
        };

        if (gss('enable_cache') && $useCache) {
            $cacheKey = $this->getCacheKey(__METHOD__, [
                'id' => $id,
                'slug' => $slug,
                'withs' => $withs,
                'wheres' => $wheres,
            ]);
            $cacheTime = $this->getCacheTime();

            // wncms()->cache()->tags($this->getCacheTag())->forget($cacheKey);

            return wncms()->cache()->remember($cacheKey, $cacheTime, $func, $this->getCacheTag());
        }

        return $func();
    }

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
        $useCache = $options['cache'] ?? true;

        $cacheKey = wncms()->cache()->createKey(
            $this->cacheKeyPrefix,
            'getList',
            $this->shouldAuth ? (auth()->id() ?? false) : false,
            $options,
            wncms()->website()->get()?->url
        );

        $isRandom = $options['is_random'] ?? false;
        $cacheTime = $isRandom ? 0 : (gss('enable_cache') ? gss('data_cache_time') : 0);

        $callback = function () use ($options) {
            $q = $this->buildListQuery($options);
            return $this->finalizeResult($q, $options);
        };

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
     * Apply tag filters to a query.
     * 
     * @param Builder $q
     * @param array|string|null $tags
     * @param string $tagType
     */
    protected function applyTagFilter(Builder $q, array|string|null $tags, ?string $tagType = null)
    {
        if (empty($tags)) return;

        if (is_string($tags)) {
            $tags = explode(',', $tags);
        }

        $tagType = $tagType ?? $this->defaultTagType;

        $tagModelClass = config('wncms.models.tag', \Wncms\Models\Tag::class);

        $ids = [];
        $names = [];

        foreach ($tags as $tag) {
            if ($tag instanceof $tagModelClass) {
                $names[] = $tag->name;
            } elseif (is_numeric($tag)) {
                $ids[] = $tag;
            } elseif (is_string($tag)) {
                $names[] = $tag;
            }
        }

        $query = $tagModelClass::query()->where('type', $tagType);
        $query->where(function ($subq) use ($ids, $names) {
            if (!empty($ids)) {
                $subq->orWhereIn('id', $ids);
            }
            if (!empty($names)) {
                $subq->orWhereIn('name', $names);
            }
        });

        $tagNames = $query->pluck('name')->toArray();

        if (empty($tagNames)) {
            $q->whereNull('id');
            return;
        }

        if (!empty($tagNames)) {
            $q->where(function ($subq) use ($tagNames, $tagType) {
                $subq->withAnyTags($tagNames, $tagType);
            });
        }
    }

    /**
     * Apply filter to exclude models with specified tag IDs.
     *
     * @param Builder $q
     * @param array|string|int|null $excludedTagIds
     */
    protected function applyExcludedTags(Builder $q, array|string|int|null $excludedTagIds): void
    {
        if (empty($excludedTagIds)) return;

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
        if (empty($keywords)) return;
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
        if (empty($ids)) return;
        if (is_string($ids)) $ids = explode(',', $ids);
        $q->whereIn($column, (array)$ids);
    }

    /**
     * Apply a whereNotIn filter.
     */
    protected function applyExcludeIds(Builder $q, string $column, array|string|int|null $excluded)
    {
        if (empty($excluded)) return;
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
    protected function applyStatus(Builder $q, string $column, string $status)
    {
        $q->where($column, $status);
    }

    /**
     * Apply eager-loading to the query.
     */
    protected function applyWiths(Builder $q, array $withs)
    {
        if (!empty($withs)) {
            $q->with($withs);
        }
    }

    /**
     * Apply ordering rules to the query.
     */
    protected function applyOrdering(Builder $q, string $order, string $sequence = 'desc', bool $isRandom = false)
    {
        if ($isRandom) {
            $q->inRandomOrder();
        } else {
            $q->orderBy($order, in_array($sequence, ['asc', 'desc']) ? $sequence : 'desc');
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
     * Return a scoped builder from the Website model relation.
     * 
     * @param string $relation
     * @param int|null $websiteId
     * @return Builder
     */
    protected function getWebsiteQuery(string $relation, ?int $websiteId = null): Builder
    {
        $website = wncms()->website()->get($websiteId);

        if (method_exists($website, $relation)) {
            return $website->{$relation}();
        }

        throw new \Exception("Website does not support relation: {$relation}");
    }

    public function getCacheKeyPrefix(): string
    {
        return $this->cacheKeyPrefix;
    }

    public function getCacheTag(): array|string
    {
        return $this->cacheTags;
    }

    public function getDefaultTagType(): string
    {
        return $this->defaultTagType;
    }

    protected function getCacheKey(string $method, array $args = []): string
    {
        return wncms()->cache()->createKey(
            prefix: $this->getCacheKeyPrefix(),
            method: $method,
            auth: $this->shouldAuth,
            args: $args
        );
    }

    public function getCacheTime(int $fallback = 0): int
    {
        return gss('enable_cache') ? gss('data_cache_time', $fallback) : 0;
    }

    protected function autoAddOrderByColumnsToSelect(Builder $q, array $select): array
    {
        $orderColumns = collect($q->getQuery()->orders ?? [])
            ->pluck('column')
            ->filter() // remove raw expressions
            ->unique()
            ->values();

        foreach ($orderColumns as $column) {
            // Skip if already selected (consider both 'col' and 'table.col')
            if (!in_array($column, $select) && !in_array('links.' . $column, $select)) {
                $select[] = $column;
            }
        }

        return $select;
    }
}
