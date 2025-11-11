<?php

/**
 * Menu helpers
 */

use Wncms\Models\Menu;
use Wncms\Models\Tag;
use Wncms\Models\Website;

// if (!function_exists('wncms_get_menu')) {
//     function wncms_get_menu($name, Website $website = null)
//     {
//         $website = $website ?? wn('website')->get();
//         $cacheKey = "wncms_get_menu_{$name}_{$website?->id}";
//         $cacheTags = ['menus', 'pages'];
//         $cacheTime = gss('enable_cache') ? gss('data_cache_time') : 0;
//         //wn('cache')->clear($cacheKey, $cacheTags);
//         $menu = wncms()->cache()->tags($cacheTags)->remember($cacheKey, gss('data_cache_time', 3600), function () use ($name, $website) {
//             return $website->menus()->where('name', $name)->orWhere('id', $name)->first();
//         });
//         return $menu;
//     }
// }

// if (!function_exists('wncms_get_menus')) {
//     function wncms_get_menus($ids = null, Website $website = null, $test = false)
//     {
//         if($test){
//             dd($ids, $website);
//         }
//         if (!is_array($ids)) $ids = array_filter(explode(',', $ids));
//         $website = $website ?? wn('website')->get();
//         $cacheKey = "wncms_get_menus_{$website->id}_" . implode(',', $ids);
//         $cacheTags = ['menus', 'pages'];
//         $cacheTime = gss('enable_cache') ? gss('data_cache_time') : 0;
//         //wn('cache')->clear($cacheKey, $cacheTags);
//         $menus = wncms()->cache()->tags($cacheTags)->remember($cacheKey, gss('data_cache_time', 3600), function () use ($website, $ids) {
//             if ($website) {
//                 if (count($ids) > 0) {
//                     return $website->menus()->whereIn('id', $ids)->get();
//                 } else {
//                     return $website->menus;
//                 }
//             } else {
//                 if (count($ids) > 0) {
//                     return Menu::whereIn('id', $ids)->get();
//                 } else {
//                     return Menu::all();
//                 }
//             }
//         });
//         return $menus;
//     }
// }

// if (!function_exists('wncms_get_menu_parent_items')) {
//     function wncms_get_menu_parent_items($name, Website $website = null)
//     {
//         $cacheKey = "wncms_get_menu_parent_items_{$name}_{$website?->id}";
//         $cacheTags = ['menus', 'pages'];
//         $cacheTime = gss('enable_cache') ? gss('data_cache_time') : 0;
//         //wn('cache')->clear($cacheKey, $cacheTags);
//         return  wncms()->cache()->tags($cacheTags)->remember($cacheKey, gss('data_cache_time', 3600), function () use ($name, $website) {
//             return wncms_get_menu($name, $website)?->menu_items()->whereNull('parent_id')->orderBy('order', 'asc')->get();
//         });
//     }
// }

// if (!function_exists('wncms_get_menu_item_url')) {
//     function wncms_get_menu_item_url($menu_item)
//     {
//         //如果是external_link
//         if ($menu_item->type == 'external_link') {
//             return $menu_item->url;
//         }

//         //如果是video
//         if(str()->startsWith($menu_item->type, 'video')){
//             if(str()->endsWith($menu_item->type, 'category')){
//                 return route('frontend.videos.video_taxonomy', ['video_taxonomy_type' => 'category', 'taxonomy_name' => $menu_item->name]);
//             }
//             if(str()->endsWith($menu_item->type, 'tag')){
//                 return route('frontend.videos.video_taxonomy', ['video_taxonomy_type' => 'tag', 'taxonomy_name' => $menu_item->name]);
//             }
//         }
        
//         if(str()->startsWith($menu_item->type, 'post')){
//             if(str()->endsWith($menu_item->type, 'category')){
//                 return route('frontend.posts.post_taxonomy_type', ['taxonomy_name' => $menu_item->name]);
//             }
//             if(str()->endsWith($menu_item->type, 'tag')){
//                 return route('frontend.posts.post_taxonomy_type', ['taxonomy_name' => $menu_item->name]);
//             }
//         }

//         if($menu_item->model_type == "Tag"){
//             $tag_data = explode("_", $menu_item->type);
//             if(!empty($tag_data[0] && !empty($tag_data[1]))){
//                 $table_name = str()->plural($tag_data[0]);
//                 $tag_type = $tag_data[1];
//                 $tag = Tag::where('type', $menu_item->type)->where('id',$menu_item->model_id)->first();

//                 $route = "frontend.{$table_name}.{$tag_type}";
//                 if(wncms_route_exists($route)){
//                     return route($route, [
//                         'slug' => $tag->slug
//                     ]);
//                 }

//                 $route = "frontend.{$table_name}.archive";
//                 if(wncms_route_exists($route)){
//                     return route($route, [
//                         'tag_type' => $tag_type,
//                         'slug' => $tag->slug
//                     ]);
//                 }
//             }
//         }

//         //其他model
//         $model_class_name = "Wncms\Models\\" .$menu_item->model_type;
//         if (class_exists($model_class_name)) {
//             $table_name = (new $model_class_name)->getTable();
//         } else {
//         }

//         return '#';
//     }
// }
