<?php

namespace Wncms\Services\Managers;

use Wncms\Models\Menu;
use Wncms\Models\MenuItem;
use Wncms\Models\Website;
use LaravelLocalization;

class MenuManager
{
    //Cache key prefix that prepend all cache key in this page
    protected $cacheKeyPrefix = "wncms_menu";


    /**
     * ----------------------------------------------------------------------------------------------------
     * Get menu by id
     * ----------------------------------------------------------------------------------------------------
     * @since 3.0.0
     * @version 3.1.15
     * 
     * @param integer $contactFormId
     * @return mixed
     * TODO: 加入搜索多個欄位
     * ----------------------------------------------------------------------------------------------------
     */
    public function get(string $name, ?int $websiteId = null)
    {
        $method = "get";
        $shouldAuth = false;
        $cacheKeyDomain = empty($websiteId) ? wncms()->getDomain() : '';
        $cacheKey = wncms()->cache()->createKey($this->cacheKeyPrefix, $method, $shouldAuth, wncms()->getAllArgs(__METHOD__, func_get_args()), $cacheKeyDomain);
        $cacheTags = ['menus'];
        $cacheTime = gss('enable_cache') ? gss('data_cache_time') : 0;
        // wncms()->cache()->clear($cacheKey, $cacheTags);

        return wncms()->cache()->tags($cacheTags)->remember($cacheKey, $cacheTime, function () use ($name, $websiteId) {
            // info('no cache from MenuManager get()');
            
            $website = wncms()->website()->get($websiteId);

            $menu = $website->menus()
            ->where(function($q) use($name){
                foreach(LaravelLocalization::getSupportedLanguagesKeys() as $localeKey){
                    $q->orwhere("name->{$localeKey}", $name);
                }
                $q->orWhere('id', $name);
            })
            ->with('menu_items')
            ->first();

            return $menu;
        });

    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Get a list of menus
     * ----------------------------------------------------------------------------------------------------
     * @since 3.0.0
     * @version 3.1.15
     * 
     * @param array|string|int|null $names
     * @param int|null $websiteId
     * ----------------------------------------------------------------------------------------------------
     */
    public function getList(array|string|int|null $names = [], ?int $websiteId = null)
    {
        $method = "getList";
        $shouldAuth = false;
        $cacheKeyDomain = empty($websiteId) ? wncms()->getDomain() : '';
        $cacheKey = wncms()->cache()->createKey($this->cacheKeyPrefix, $method, $shouldAuth, wncms()->getAllArgs(__METHOD__, func_get_args()), $cacheKeyDomain);
        $cacheTags = ['menus'];
        $cacheTime = gss('enable_cache') ? gss('data_cache_time') : 0;
        // wncms()->cache()->clear($cacheKey, $cacheTags);

        return wncms()->cache()->tags($cacheTags)->remember($cacheKey, $cacheTime, function () use ($names, $websiteId) {
            // info('no cache from MenuManager getList()');

            $website = wncms()->website()->get($websiteId);

            $q = $website->menus();

            if(!empty($names)){
                if(!is_array($names)){
                    $names = array_filter(explode(",",$names));
                }
        
                $q->where(function($q) use($names){
                    $q->whereIn('name', $names)->orWhereIn('id', $names);
                });
            }

            $menus = $q->get();
            return $menus;
        });

    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Get only parent items of a menu
     * ----------------------------------------------------------------------------------------------------
     * @since 3.0.0
     * @version 3.1.15
     * 
     * @param Menu|string|int $menuName
     * @param ?string $order = 'order'
     * @param ?string $sequence = 'asc'
     * @param Website|int|null $websiteId = null
     * ----------------------------------------------------------------------------------------------------
     */
    public function getMenuParentItems(Menu|string|int $menuName, ?string $order = 'order', ?string $sequence = 'asc', Website|int|null $websiteId = null)
    {
        $method = "getMenuParentItems";
        $shouldAuth = false;
        $cacheKeyDomain = empty($websiteId) ? wncms()->getDomain() : '';
        $cacheKey = wncms()->cache()->createKey($this->cacheKeyPrefix, $method, $shouldAuth, wncms()->getAllArgs(__METHOD__, func_get_args()), $cacheKeyDomain);
        $cacheTags = ['menus'];
        $cacheTime = gss('enable_cache') ? gss('data_cache_time') : 0;
        // wncms()->cache()->clear($cacheKey, $cacheTags);
        // dd($cacheKey);

        return wncms()->cache()->tags($cacheTags)->remember($cacheKey, $cacheTime, function () use ($menuName, $order, $sequence, $websiteId) {
            // info('no cache from MenuManager getMenuParentItems()');

            $website = wncms()->website()->get($websiteId);

            if(!$website) return collect([]);

            if($menuName instanceof Menu){
                $menu = $menuName;
            }else{
                $menu = $this->get($menuName, $websiteId);
            }

            if(!$menu) return collect([]);

            if(empty($order) || !in_array($order, MenuItem::ORDERS)){
                $order = 'order';
            }

            if(empty($sequence) || !in_array($sequence, ['asc', 'desc'])){
                $sequence = 'desc';
            }

            return $menu->menu_items()->whereNull('parent_id')->orderBy($order, $sequence)->get();
        });
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Get a validated url of a single menu item
     * ----------------------------------------------------------------------------------------------------
     * @since 3.0.0
     * @version 3.1.15
     * 
     * @param MenuItem|int|null $menuItem

     * ----------------------------------------------------------------------------------------------------
     */
    public function getMenuItemUrl(MenuItem|int|null $menuItem)
    {
        if(empty($menuItem)){
            return "javascript:;";
        }

        //如果是external_link
        if ($menuItem->type == 'external_link' || $menuItem->type == 'theme_page') {
            if(str($menuItem->url)->startsWith("#")){
               return url()->current() . $menuItem->url;
            }
            return $menuItem->url;
        }

        // TODO: 統一大小寫
        if ($menuItem->model_type == "Tag") {
            $tagData = explode("_", $menuItem->type);

            if (!empty($tagData[0] && !empty($tagData[1]))) {
                $tableName = str()->plural($tagData[0]);
                $tagType = $tagData[1];

                //get Tag model
                $tag = wncms()->tag()->get($menuItem->model_id);
 
                if(!$tag){
                    return "javascript:;";
                }

                //get route
                $route = "frontend.{$tableName}.{$tagType}";
                if (wncms_route_exists($route)) {
                    return route($route, [
                        'tagName' => $tag->name,
                    ]);
                }

                $route = "frontend.{$tableName}.archive";
                if (wncms_route_exists($route)) {
                    return route($route, [
                        'tagType' => $menuItem->model_type,
                        'tagName' => $tag->name,
                    ]);
                }
            }
        }

        if ($menuItem->model_type == "page") {
            $page = wncms()->page()->get(pageId:$menuItem->model_id);
            if($page){
                return wncms()->getRoute('frontend.pages', ['slug' => $page->slug], false, $page->website->domain);
            }
        }

        //其他model
        $model_class_name = "Wncms\Models\\" . $menuItem->model_type;

        if (class_exists($model_class_name)) {
            $tableName = (new $model_class_name)->getTable();
        } else {
            
        }

        return 'javascript:;';
    }
}