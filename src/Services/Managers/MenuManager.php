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

        // ✅ Changed default ordering from 'order' → 'id'
        $this->applyOrdering($q, $options['order'] ?? 'id', $options['sequence'] ?? 'asc');

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
    public function getMenuParentItems(Model|string|int $menuName, ?string $order = 'order', ?string $sequence = 'asc', Model|int|null $websiteId = null)
    {
        $menuModel = $this->getModelClass();
        $menuItemModel = wncms()->getModelClass('menu_item');
        $websiteModel = wncms()->getModelClass('website');

        $method = "getMenuParentItems";
        $cacheKeyDomain = empty($websiteId) ? wncms()->getDomain() : '';
        $cacheKey = wncms()->cache()->createKey($this->cacheKeyPrefix, $method, false, wncms()->getAllArgs(__METHOD__, func_get_args()), $cacheKeyDomain);
        $cacheTags = ['menus'];
        $cacheTime = gss('enable_cache') ? gss('data_cache_time') : 0;

        return wncms()->cache()->tags($cacheTags)->remember($cacheKey, $cacheTime, function () use ($menuName, $order, $sequence, $websiteId, $menuModel, $menuItemModel, $websiteModel) {
            $website = wncms()->website()->get($websiteId);
            if (!$website) return collect([]);

            $menu = $menuName instanceof $menuModel ? $menuName : $this->get(['name' => $menuName, 'website_id' => $websiteId]);
            if (!$menu) return collect([]);

            $order = (empty($order) || !in_array($order, $menuItemModel::ORDERS ?? [])) ? 'order' : $order;
            $sequence = (empty($sequence) || !in_array($sequence, ['asc', 'desc'])) ? 'asc' : $sequence;

            return $menu->menu_items()->whereNull('parent_id')->orderBy($order, $sequence)->get();
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

        // ==============================
        // 1️⃣ Handle tag-based menu items
        // ==============================
        if ($menuItem->model_type === "Tag") {
            $tag = wncms()->tag()->get(['id' => $menuItem->model_id]);
            if (!$tag) return "javascript:;";

            // Example: type = "product_category"
            $full = $menuItem->type;
            $segments = explode("_", $full);
            if (count($segments) < 2) return "javascript:;";

            $modelKey = $segments[0];   // e.g. product
            $key      = $segments[1];   // e.g. category

            // Get model class dynamically
            $modelClass = wncms()->getModelClass($modelKey);
            if (!class_exists($modelClass)) return "javascript:;";

            // Instantiate once (for tagFormats or extra logic)
            $instance = new $modelClass();

            // ✅ Get static package ID
            $packageId = method_exists($modelClass, 'getPackageId')
                ? $modelClass::getPackageId()
                : null;

            if ($packageId === 'wncms') {
                // Core model manager (e.g. wncms()->post()->getAllowedTagTypes())
                if (method_exists(wncms(), $modelKey)) {
                    $manager = wncms()->{$modelKey}();
                    $allowedTagTypes = method_exists($manager, 'getAllowedTagTypes')
                        ? $manager->getAllowedTagTypes()
                        : [];
                } else {
                    $allowedTagTypes = [];
                }
            } elseif (!empty($packageId)) {
                // Package model manager (e.g. wncms()->package('wncms-ecommerce')->product()->getAllowedTagTypes())
                $package = wncms()->package($packageId);

                $manager = $package->{$modelKey}();

                // ✅ check getAllowedTagTypes() only on the manager
                $allowedTagTypes = method_exists($manager, 'getAllowedTagTypes')
                    ? $manager->getAllowedTagTypes()
                    : [];

            } else {
                $allowedTagTypes = [];
            }


            // ✅ Find matching tag definition (e.g. full = "product_category")
            $matched = collect($allowedTagTypes)->firstWhere('full', $full);
  
            if (!$matched) return "javascript:;";

            // ✅ Try to find defined route pattern in tagFormats first
            $routeName = $instance->tagFormats[$full]
                ?? "frontend." . str($modelKey)->plural() . ".tag";

            // ✅ Generate URL if route exists
            if (wncms_route_exists($routeName)) {
                return route($routeName, ['type' => $key, 'slug' => $tag->slug]);
            }

            // ✅ Fallback (for debugging or incomplete routes)
            return "javascript:;";
        }


        // ==============================
        // 2️⃣ Handle pages
        // ==============================
        if ($menuItem->model_type === "page") {
            $page = wncms()->page()->get(['id' => $menuItem->model_id]);
            return $page ? route('frontend.pages.single', ['slug' => $page->slug]) : "javascript:;";
        }

        // ==============================
        // 3️⃣ Handle generic models
        // ==============================
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
