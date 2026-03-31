<?php

namespace Wncms\Services\Managers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Wncms\Models\BaseModel;

class MenuManager extends ModelManager
{
    protected string $cacheKeyPrefix = 'wncms_menu';
    protected string|array $cacheTags = ['menus', 'pages'];
    protected bool $shouldAuth = false;

    public function getModelClass(): string
    {
        return wncms()->getModelClass('menu');
    }

    public function get(array $options = []): ?Model
    {
        $menuModel = $this->getModelClass();
        $options['withs'] = $options['withs'] ?? ['menu_items'];

        if (isset($options['id'])) {
            $id = $options['id'];
            $options['wheres'][] = fn($q) => $q->where('id', $id);
        }

        if (isset($options['name'])) {
            $name = $options['name'];
            $options['wheres'][] = fn($q) => $q->where('name', $name);
        }

        if (!isset($options['id']) && !isset($options['name']) && isset($options[0])) {
            $value = $options[0];

            if (is_numeric($value)) {
                $options['wheres'][] = fn($q) => $q->where('id', $value);
            } else {
                $options['wheres'][] = fn($q) => $q->where('name', $value);
            }
        }

        return parent::get($options);
    }

    // public function getList(array|string|int|null $options = []): Collection|LengthAwarePaginator
    // {
    //     $options = [
    //         'names' => $names,
    //         'website_id' => $websiteId,
    //         'cache' => true,
    //     ];

    //     return parent::getList($options);
    // }

    protected function buildListQuery(array $options): mixed
    {
        $menuModel = $this->getModelClass();
        $q = $menuModel::query()->with('menu_items');

        $this->applyWebsiteId($q, $options['website_id'] ?? null);

        if (!empty($options['names'])) {
            $names = is_array($options['names']) ? $options['names'] : explode(',', $options['names']);
            $q->where(function ($q) use ($names) {
                $q->whereIn('name', $names)->orWhereIn('id', $names);
            });
        }

        $sort = (string) ($options['sort'] ?? 'id');
        $direction = strtolower((string) ($options['direction'] ?? 'asc'));
        $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : 'asc';
        $isRandom = $options['is_random'] ?? ($sort === 'random');
        $sort = $this->normalizeSortColumn($sort);

        $this->applyOrdering($q, $sort, $direction, $isRandom);

        return $q;
    }

    public function resolveMenuSources(?Request $request = null): array
    {
        $sources = $this->resolveDefaultMenuSources();

        Event::dispatch('wncms.backend.menus.sources.resolve', [&$sources, $request]);

        return collect($sources)
            ->map(fn ($source) => is_array($source) ? $this->normalizeMenuSource($source) : null)
            ->filter()
            ->keyBy('key')
            ->all();
    }

    protected function resolveDefaultMenuSources(): array
    {
        $sources = [];
        $configuredModelClasses = collect((array) config('wncms.models', []))
            ->map(function ($configData) {
                if (is_array($configData)) {
                    return $configData['class'] ?? null;
                }

                return is_string($configData) ? $configData : null;
            })
            ->filter()
            ->values()
            ->all();
        $coreOptInClasses = collect(['page', 'post'])
            ->map(function ($modelKey) {
                try {
                    return wncms()->getModelClass($modelKey);
                } catch (\Throwable $e) {
                    return null;
                }
            })
            ->filter()
            ->values()
            ->all();

        foreach (array_unique(array_merge($configuredModelClasses, $coreOptInClasses, wncms()->getModels())) as $modelClass) {
            if (!is_string($modelClass) || !class_exists($modelClass) || !is_subclass_of($modelClass, BaseModel::class)) {
                continue;
            }

            if (!$this->shouldShowModelInMenuEditor($modelClass)) {
                continue;
            }

            $source = $this->buildDefaultMenuSourceFromModel($modelClass);
            if ($source) {
                $sources[] = $source;
            }
        }

        return $sources;
    }

    public function getMenuSource(string $key, ?Request $request = null): ?array
    {
        return $this->resolveMenuSources($request)[$key] ?? null;
    }

    public function searchMenuSourceItems(string $key, string $keyword, ?Request $request = null): array
    {
        $source = $this->getMenuSource($key, $request);
        $keyword = trim($keyword);

        if (!$source || $keyword === '') {
            return [];
        }

        /** @var Builder $query */
        $query = $this->buildMenuSourceQuery($source, $request);
        $searchFields = $source['search_fields'] ?? ['title'];

        $query->where(function (Builder $subQuery) use ($searchFields, $keyword) {
            foreach ($searchFields as $field) {
                if (!is_string($field) || !preg_match('/^[A-Za-z0-9_.>-]+$/', $field)) {
                    continue;
                }

                $subQuery->orWhere($field, 'like', "%{$keyword}%");
            }
        });

        return $query
            ->limit($source['result_limit'] ?? 20)
            ->get()
            ->map(function (Model $model) use ($source) {
                return [
                    'id' => $model->getKey(),
                    'model_id' => $model->getKey(),
                    'model_type' => $source['model_key'],
                    'type' => $source['key'],
                    'label' => $this->resolveMenuSourceItemLabel($source, $model),
                    'description' => $this->resolveMenuSourceItemDescription($model),
                ];
            })
            ->values()
            ->all();
    }

    public function getMenuParentItems(Model|string|int $menuName, ?string $sort = 'sort', ?string $direction = 'asc', Model|int|null $websiteId = null)
    {
        $menuModel = $this->getModelClass();
        $menuItemModel = wncms()->getModelClass('menu_item');

        $method = "getMenuParentItems";
        $cacheKeyDomain = empty($websiteId) ? wncms()->getDomain() : '';
        $cacheKey = wncms()->cache()->createKey($this->cacheKeyPrefix, $method, false, wncms()->getAllArgs(__METHOD__, func_get_args()), $cacheKeyDomain);
        $cacheTags = ['menus'];
        $cacheTime = gss('enable_cache') ? gss('data_cache_time') : 0;

        return wncms()->cache()->tags($cacheTags)->remember($cacheKey, $cacheTime, function () use ($menuName, $sort, $direction, $websiteId, $menuModel, $menuItemModel) {

            $website = wncms()->website()->get($websiteId);
            if (!$website) {
                return collect([]);
            }

            if ($menuName instanceof $menuModel) {
                $menu = $menuName;
            } else {
                $options = [];
                if (is_numeric($menuName)) {
                    $options['id'] = $menuName;
                } else {
                    $options['name'] = $menuName;
                }

                if ($websiteId) {
                    $options['website_id'] = $websiteId;
                }

                $menu = $this->get($options);
            }

            if (!$menu) {
                return collect([]);
            }

            $sort = (empty($sort) || !in_array($sort, $menuItemModel::SORTS ?? [])) ? 'sort' : $sort;
            $direction = (empty($direction) || !in_array($direction, ['asc', 'desc'])) ? 'asc' : $direction;

            // dd(
            //     $menu->menu_items()
            //     ->whereNull('parent_id')
            //     ->orderBy($sort, $direction)
            //     ->get()
            // );
            return $menu->menu_items()
                ->whereNull('parent_id')
                ->orderBy($sort, $direction)
                ->get();
        });
    }

    public function getMenuItemUrl($menuItem)
    {
        if (empty($menuItem)) return "javascript:;";

        if (in_array($menuItem->type, ['external_link', 'theme_page'])) {
            return str($menuItem->url)->startsWith("#")
                ? url()->current() . $menuItem->url
                : $menuItem->url;
        }

        if ($menuItem->model_type === "Tag") {
            $tag = wncms()->tag()->get(['id' => $menuItem->model_id]);
            if (!$tag) return "javascript:;";

            return wncms()->tag()->getUrl($tag);
        }

        if ($menuItem->model_type === "page") {
            $page = wncms()->page()->get(['id' => $menuItem->model_id]);
            return $page ? route('frontend.pages.show', ['slug' => $page->slug]) : "javascript:;";
        }

        $source = $this->findMenuSourceForMenuItem($menuItem);
        if ($source) {
            $model = $this->findLinkedModelForMenuItem($menuItem, $source);
            if ($model) {
                $resolvedUrl = $this->resolveMenuSourceItemUrl($source, $model);
                if (!empty($resolvedUrl)) {
                    return $resolvedUrl;
                }
            }
        }

        $modelClass = wncms()->getModelClass(strtolower($menuItem->model_type));
        if (class_exists($modelClass)) {
            $table = (new $modelClass)->getTable();
            $routeName = "frontend.{$table}.show";

            if (wncms()->hasRoute($routeName)) {
                $model = wncms()->getModel(strtolower($menuItem->model_type))->get(['id' => $menuItem->model_id]);
                if ($model && $model->slug) {
                    return route($routeName, ['slug' => $model->slug]);
                }
            }
        }

        return "javascript:;";
    }

    public function getMenuItemResolvedName($menuItem): string
    {
        $overrideName = $this->resolveMenuItemOverrideName($menuItem);
        if ($overrideName !== '') {
            return $overrideName;
        }

        if ($menuItem->model_type === 'Tag') {
            $tag = wncms()->tag()->get(['id' => $menuItem->model_id]);
            if ($tag && !empty($tag->name)) {
                return (string) $tag->name;
            }
        }

        if ($menuItem->model_type === 'page') {
            $page = wncms()->page()->get(['id' => $menuItem->model_id]);
            if ($page) {
                $resolved = $this->resolveModelLabelFallback($page);
                if ($resolved !== '') {
                    return $resolved;
                }
            }
        }

        $source = $this->findMenuSourceForMenuItem($menuItem);
        if ($source) {
            $model = $this->findLinkedModelForMenuItem($menuItem, $source);
            if ($model) {
                $resolved = $this->resolveMenuSourceItemLabel($source, $model);
                if ($resolved !== '') {
                    return $resolved;
                }
            }
        }

        $modelClass = wncms()->getModelClass(strtolower((string) $menuItem->model_type));
        if (class_exists($modelClass)) {
            $model = $modelClass::query()->find($menuItem->model_id);
            if ($model) {
                $resolved = $this->resolveModelLabelFallback($model);
                if ($resolved !== '') {
                    return $resolved;
                }
            }
        }

        if (!empty($menuItem->model_id)) {
            return '#' . $menuItem->model_id;
        }

        return __('wncms::word.untitled');
    }

    protected function normalizeSortColumn(string $sort): string
    {
        $sort = trim(str_replace('menus.', '', $sort));
        if ($sort === '') {
            return 'id';
        }

        if ($sort === 'random') {
            return $sort;
        }

        if (!preg_match('/^[A-Za-z0-9_]+$/', $sort)) {
            return 'id';
        }

        return $sort;
    }

    protected function normalizeMenuSource(array $source): ?array
    {
        $key = Str::snake((string) ($source['key'] ?? ''));
        $type = (string) ($source['type'] ?? '');
        $modelClass = $source['model_class'] ?? null;

        if ($key === '' || $type !== 'model_search' || !is_string($modelClass) || !class_exists($modelClass)) {
            return null;
        }

        $modelKey = (string) ($source['model_key'] ?? '');
        if ($modelKey === '') {
            $modelKey = property_exists($modelClass, 'modelKey')
                ? (string) ($modelClass::$modelKey ?? '')
                : '';
        }
        if ($modelKey === '') {
            $modelKey = Str::snake(Str::singular(class_basename($modelClass)));
        }

        $searchFields = collect((array) ($source['search_fields'] ?? ['title']))
            ->filter(fn ($field) => is_string($field) && trim($field) !== '')
            ->values()
            ->all();

        return array_merge($source, [
            'key' => $key,
            'type' => 'model_search',
            'label' => (string) ($source['label'] ?? Str::headline($key)),
            'model_class' => $modelClass,
            'model_key' => Str::snake($modelKey),
            'search_fields' => !empty($searchFields) ? $searchFields : ['title'],
            'result_limit' => max(1, (int) ($source['result_limit'] ?? 20)),
        ]);
    }

    protected function shouldShowModelInMenuEditor(string $modelClass): bool
    {
        return (bool) ($modelClass::$showInMenuEditor ?? false);
    }

    protected function buildDefaultMenuSourceFromModel(string $modelClass): ?array
    {
        $model = new $modelClass;
        $modelKey = trim((string) ($modelClass::$modelKey ?? ''));

        if ($modelKey === '') {
            $modelKey = Str::snake(Str::singular(class_basename($modelClass)));
        }

        $searchFields = $this->resolveDefaultMenuSourceSearchFields($modelClass);
        if (empty($searchFields)) {
            return null;
        }

        return [
            'key' => $modelKey,
            'label' => $modelClass::getModelName(),
            'type' => 'model_search',
            'model_class' => $modelClass,
            'model_key' => $modelKey,
            'search_fields' => $searchFields,
            'result_limit' => 20,
            'query' => fn ($query) => $query->orderByDesc($model->getQualifiedKeyName()),
        ];
    }

    protected function resolveDefaultMenuSourceSearchFields(string $modelClass): array
    {
        $model = new $modelClass;
        $table = $model->getTable();
        $schema = Schema::connection($model->getConnectionName());

        try {
            return collect(['title', 'name', 'slug'])
                ->filter(fn ($field) => $schema->hasColumn($table, $field))
                ->values()
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function buildMenuSourceQuery(array $source, ?Request $request = null): Builder
    {
        $modelClass = $source['model_class'];
        $query = $modelClass::query();

        if (!empty($source['query']) && is_callable($source['query'])) {
            $customQuery = call_user_func($source['query'], $query, $request, $source);
            if ($customQuery instanceof Builder) {
                $query = $customQuery;
            }
        }

        return $query;
    }

    protected function resolveMenuSourceItemLabel(array $source, Model $model): string
    {
        if (!empty($source['label_resolver']) && is_callable($source['label_resolver'])) {
            $resolved = call_user_func($source['label_resolver'], $model, $source);
            if (is_string($resolved) && trim($resolved) !== '') {
                return trim($resolved);
            }
        }

        $fallback = $this->resolveModelLabelFallback($model);
        if ($fallback !== '') {
            return $fallback;
        }

        return '#' . $model->getKey();
    }

    protected function resolveMenuSourceItemDescription(Model $model): ?string
    {
        foreach (['slug', 'url', 'remark', 'description'] as $attribute) {
            $value = data_get($model, $attribute);
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    protected function resolveMenuSourceItemUrl(array $source, Model $model): ?string
    {
        if (!empty($source['url_resolver']) && is_callable($source['url_resolver'])) {
            $resolved = call_user_func($source['url_resolver'], $model, $source);
            return is_string($resolved) && trim($resolved) !== '' ? trim($resolved) : null;
        }

        return null;
    }

    protected function findMenuSourceForMenuItem($menuItem): ?array
    {
        $type = Str::snake((string) ($menuItem->type ?? ''));
        $modelType = Str::snake((string) ($menuItem->model_type ?? ''));

        foreach ([null, request() instanceof Request ? request() : null] as $request) {
            if ($type !== '') {
                $source = $this->getMenuSource($type, $request);
                if ($source) {
                    return $source;
                }
            }

            $matchedSource = $this->matchMenuSource($this->resolveMenuSources($request), $type, $modelType);
            if ($matchedSource) {
                return $matchedSource;
            }
        }

        return null;
    }

    protected function matchMenuSource(array $sources, string $type, string $modelType): ?array
    {
        foreach ($sources as $source) {
            if (($source['key'] ?? null) === $type || ($source['model_key'] ?? null) === $modelType) {
                return $source;
            }
        }

        return null;
    }

    protected function findLinkedModelForMenuItem($menuItem, ?array $source = null): ?Model
    {
        $source = $source ?: $this->findMenuSourceForMenuItem($menuItem);
        if (!$source) {
            return null;
        }

        $query = $this->buildMenuSourceQuery($source, request() instanceof Request ? request() : null);
        return $query->find($menuItem->model_id);
    }

    protected function resolveMenuItemOverrideName($menuItem): string
    {
        if ($menuItem instanceof Model && method_exists($menuItem, 'getRawOriginal')) {
            $raw = $menuItem->getRawOriginal('name');
        } else {
            $raw = $menuItem->name ?? '';
        }

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $locale = app()->getLocale();
                $defaultLocale = config('app.locale', 'en');

                foreach ([$locale, $defaultLocale] as $localeKey) {
                    $value = trim((string) ($decoded[$localeKey] ?? ''));
                    if ($value !== '') {
                        return $value;
                    }
                }

                foreach ($decoded as $value) {
                    $value = trim((string) $value);
                    if ($value !== '') {
                        return $value;
                    }
                }

                return '';
            }

            return trim($raw);
        }

        return trim((string) $raw);
    }

    protected function resolveModelLabelFallback(Model $model): string
    {
        foreach (['title', 'name', 'label'] as $attribute) {
            $value = $this->resolveTranslatedLabelValue(data_get($model, $attribute));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    protected function resolveTranslatedLabelValue($value): string
    {
        if (is_array($value)) {
            $locale = app()->getLocale();
            $fallbackLocale = config('app.locale', 'en');

            foreach ([$locale, $fallbackLocale] as $localeKey) {
                $localizedValue = $value[$localeKey] ?? '';
                if (is_array($localizedValue)) {
                    $resolved = $this->resolveTranslatedLabelValue($localizedValue);
                    if ($resolved !== '') {
                        return $resolved;
                    }
                    continue;
                }

                $localizedValue = trim((string) $localizedValue);
                if ($localizedValue !== '') {
                    return $localizedValue;
                }
            }

            foreach ($value as $candidate) {
                if (is_array($candidate)) {
                    $resolved = $this->resolveTranslatedLabelValue($candidate);
                    if ($resolved !== '') {
                        return $resolved;
                    }
                    continue;
                }

                $candidate = trim((string) $candidate);
                if ($candidate !== '') {
                    return $candidate;
                }
            }

            return '';
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $this->resolveTranslatedLabelValue($decoded);
            }

            return trim($value);
        }

        return trim((string) $value);
    }
}
