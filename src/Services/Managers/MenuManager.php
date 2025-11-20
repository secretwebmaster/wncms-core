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

    public function getList(array|string|int|null $names = [], ?int $websiteId = null): Collection|LengthAwarePaginator
    {
        $options = [
            'names' => $names,
            'website_id' => $websiteId,
            'cache' => true,
        ];

        return parent::getList($options);
    }

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

        $modelClass = wncms()->getModelClass(strtolower($menuItem->model_type));
        if (class_exists($modelClass)) {
            $table = (new $modelClass)->getTable();
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
