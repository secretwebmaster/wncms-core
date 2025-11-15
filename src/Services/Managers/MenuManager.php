<?php

namespace Wncms\Services\Managers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class MenuManager extends ModelManager
{
    protected string $cacheKeyPrefix = 'wncms_menu';
    protected string|array $cacheTags = ['menus', 'pages'];
    protected bool $shouldAuth = false;

    /**
     * Resolve model class dynamically via config.
     */
    public function getModelClass(): string
    {
        return wncms()->getModelClass('menu');
    }

    /**
     * Build the query for getList()
     */
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

        $this->applyOrdering($q, $options['sort'] ?? 'id', $options['direction'] ?? 'asc');

        return $q;
    }

    /**
     * Get a single menu by name or ID.
     */
    public function get(array $options = []): ?Model
    {
        $menuModel = $this->getModelClass();

        $options['withs'] = $options['withs'] ?? ['menu_items'];

        if (!isset($options['name']) && isset($options[0])) {
            $options['name'] = $options[0];
        }

        $options['wheres'][] = function ($q) use ($options) {
            if (!empty($options['name'])) {
                $q->where('name', $options['name'])->orWhere('id', $options['name']);
            }
        };

        return parent::get($options);
    }

    /**
     * Get list of menus (helper-style).
     */
    public function getList(array|string|int|null $names = [], ?int $websiteId = null): Collection|LengthAwarePaginator
    {
        $options = [
            'names' => $names,
            'website_id' => $websiteId,
            'cache' => true,
        ];

        return parent::getList($options);
    }

    /**
     * Get only parent items of a menu.
     */
    public function getMenuParentItems(Model|string|int $menuName, ?string $sort = 'sort', ?string $direction = 'asc', Model|int|null $websiteId = null)
    {
        $menuModel = $this->getModelClass();
        $menuItemModel = wncms()->getModelClass('menu_item');
        // $websiteModel = wncms()->getModelClass('website');

        $method = "getMenuParentItems";
        $cacheKeyDomain = empty($websiteId) ? wncms()->getDomain() : '';
        $cacheKey = wncms()->cache()->createKey($this->cacheKeyPrefix, $method, false, wncms()->getAllArgs(__METHOD__, func_get_args()), $cacheKeyDomain);
        $cacheTags = ['menus'];
        $cacheTime = gss('enable_cache') ? gss('data_cache_time') : 0;

        return wncms()->cache()->tags($cacheTags)->remember($cacheKey, $cacheTime, function () use ($menuName, $sort, $direction, $websiteId, $menuModel, $menuItemModel) {
            $website = wncms()->website()->get($websiteId);
            if (!$website) return collect([]);

            $menu = $menuName instanceof $menuModel ? $menuName : $this->get(['name' => $menuName, 'website_id' => $websiteId]);
            if (!$menu) return collect([]);

            $sort = (empty($sort) || !in_array($sort, $menuItemModel::SORTS ?? [])) ? 'sort' : $sort;
            $direction = (empty($direction) || !in_array($direction, ['asc', 'desc'])) ? 'asc' : $direction;

            return $menu->menu_items()->whereNull('parent_id')->orderBy($sort, $direction)->get();
        });
    }

    /**
     * Generate URL for menu item.
     */
    public function getMenuItemUrl($menuItem)
    {
        if (empty($menuItem)) return "javascript:;";

        // External link or anchor
        if (in_array($menuItem->type, ['external_link', 'theme_page'])) {
            return str($menuItem->url)->startsWith("#")
                ? url()->current() . $menuItem->url
                : $menuItem->url;
        }

        // Tag-based menu items (generic)
        if ($menuItem->model_type === "Tag") {
            $tag = wncms()->tag()->get(['id' => $menuItem->model_id]);
            if (! $tag) return "javascript:;";

            return wncms()->tag()->getUrl($tag);
        }

        // Pages (unchanged)
        if ($menuItem->model_type === "page") {
            $page = wncms()->page()->get(['id' => $menuItem->model_id]);
            return $page ? route('frontend.pages.single', ['slug' => $page->slug]) : "javascript:;";
        }

        // Generic models (unchanged)
        $modelClass = wncms()->getModelClass(strtolower($menuItem->model_type));
        if (class_exists($modelClass)) {
            $table     = (new $modelClass)->getTable();
            $routeName = "frontend.{$table}.show";

            if (wncms_route_exists($routeName)) {
                $model = wncms()->getModel(strtolower($menuItem->model_type))->get(['id' => $menuItem->model_id]);
                if ($model && $model->slug) {
                    return route($routeName, ['slug' => $model->slug]);
                }
            }
        }

        return "javascript:;";
    }
}
