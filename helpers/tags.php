<?php

//% Depracated soon. This helper will be moved to TagHelper and called as wncms()->tag()->{method name}()

use Wncms\Models\Tag;

if (!function_exists('wncms_get_hot_taxonomies')) {
    function wncms_get_hot_taxonomies($type = 'post_category', $count = 50)
    {
        $cacheKey = "wncms_get_hot_taxonomies_{$type}_{$count}_" . wncms()->getDomain();
        wncms()->cache()->tags(['tags'])->forget($cacheKey);
        $taxonomies = cache()->remember($cacheKey, gss('data_cache_time'), function () use ($type, $count) {
            return Tag::query()
                ->withCount('posts')
                ->where('type', $type)
                ->orderBy('posts_count', 'desc')
                ->limit($count)
                ->get();
        });
        return $taxonomies;
    }
}

if (!function_exists('wncms_get_all_tag_types')) {
    function wncms_get_all_tag_types()
    {
        $cacheKey = "wncms_get_all_tag_types" . wncms()->getDomain();
        $tags = ['tags'];
        wncms()->cache()->tags($tags)->forget($cacheKey);
        $taxonomies = wncms()->cache()->tags($tags)->remember($cacheKey, gss('data_cache_time'), function () {
            // info(array_unique(array_filter(Tag::distinct('type')->pluck('type')->toArray())));
            return array_unique(array_filter(Tag::distinct('type')->pluck('type')->toArray()));
        });
        return $taxonomies;
    }
}

//單一標籤
if (!function_exists('wncms_get_tag')) {
    function wncms_get_tag($name, $type = 'post_category')
    {
        $cacheKey = "wncms_get_tag_{$name}_{$type}";
        $cacheTags = ['tags'];
        //wncms_clear_cache($cacheKey, $cacheTags);
        return wncms()->cache()->tags($cacheTags)->remember($cacheKey, gss('data_cache_time', 3600), function () use ($name, $type) {
            return Tag::query()
                ->where('type', $type)
                ->where(function($q) use($name){
                    foreach(LaravelLocalization::getSupportedLocales() as $locale_key => $locale){
                        $q->orWhere('name->' . $locale_key,  $name);
                    }
                    $q->orWhere('slug', $name);
                })->first();
        });
    }
}

/**
 * 根據Tag類別獲取所有Tag
 * @since 1.0.0
 * @version 3.0.0
 * @param string $type Tag類別，例如 post_category
 * @param int $count 獲取數量
 * @param boolean $parent_only 是否只獲取父分類
 * @return Collection|null
 * @example wncms_get_tags_by_type('post_category')
 */
if (!function_exists('wncms_get_tags_by_type')) {
    function wncms_get_tags_by_type(string $type = 'post_category', int $count = 0, $parent_only = false)
    {
        $cacheKey = "wncms_get_tags_by_type_{$type}_{$count}_{$parent_only}";
        $cacheTags = ['tags'];
        // wncms_clear_cache($cacheKey, $cacheTags);
        return wncms()->cache()->tags($cacheTags)->remember($cacheKey, gss('data_cache_time', 3600), function () use ($type, $count, $parent_only) {
            $q = Tag::query();
            $q->where('type', $type);
            
            if($count){
                $q->linit(0);
            }

            if($parent_only){
                $q->whereNull('parent_id');
            }

            $tags = $q->get();
            return $tags;
        });
    }
}

//model的第一個標籤
// if (!function_exists('wncms_get_model_first_tag')) {
//     function wncms_get_model_first_tag($model, $type = 'post_category')
//     {
//         $cacheKey = "wncms_get_model_first_tag_" . class_basename($model) . "_{$model?->id}_{$type}_" . wncms()->getDomain();
//         $cacheTags = ['tags'];
//         // wncms()->cache()->clear($cacheKey, $cacheTags);
//         return wncms()->cache()->tags($cacheTags)->remember($cacheKey, gss('data_cache_time', 3600), function () use ($model, $type) {
//             if (method_exists($model, 'tags')) {
//                 return $model->tags()->where('type', $type)->first() ?? false;
//             }
            
//             return false;
//         });
//     }
// }

//model的第一個子標籤
if (!function_exists('wncms_get_model_lowerest_level_tag')) {
    function wncms_get_model_lowerest_level_tag($model, $type = 'post_category')
    {
        $cacheKey = "wncms_get_model_lowerest_level_tag_" . class_basename($model) . "_{$model?->id}_{$type}_" . wncms()->getDomain();
        $cacheTags = ['tags'];
        //wncms_clear_cache($cacheKey, $cacheTags);
        return wncms()->cache()->tags($cacheTags)->remember($cacheKey, gss('data_cache_time', 3600), function () use ($model, $type    ) {
            return _get_model_lowerest_level_tag($model, $type);
        });
    }

    function _get_model_lowerest_level_tag($model, $type, $current_tag = null)
    {
        if (!$current_tag) {
            // Start from the model's tags
            $tags = $model->tags()->where('type', $type)->get();
        } else {
            // Get the children of the current tag
            $tags = $current_tag->children;
        }

        if ($tags->isEmpty()) {
            // If there are no more children, return the current tag
            return $current_tag;
        }

        // Initialize a variable to store the lowest level tag
        $lowest_level_tag = null;

        foreach ($tags as $tag) {
            // Recursively call the function for each child
            $child_lowest_tag = _get_model_lowerest_level_tag($model, $type, $tag);

            if (!$lowest_level_tag || $child_lowest_tag->level > $lowest_level_tag->level) {
                $lowest_level_tag = $child_lowest_tag;
            }
        }

        return $lowest_level_tag;
    }
}

//獲取多個標籤 id/name
if (!function_exists('wncms_get_tags')) {
    function wncms_get_tags(string $category_str)
    {
        $cacheKey = "wncms_get_tags_{$category_str}";
        $cacheTags = ['tags'];
        //wncms_clear_cache($cacheKey, $cacheTags);
        return wncms()->cache()->tags($cacheTags)->remember($cacheKey, gss('data_cache_time', 3600), function () use ($category_str) {
            return Tag::where('type', 'link_category')
                ->whereIn('name', explode(',', $category_str))
                ->orWhereIn('id', explode(',', $category_str))
                ->orderBy('order_column', 'desc')
                ->get() ?? [];
        });
    }
}

//所有標籤
if (!function_exists('wncms_get_all_tags')) {
    function wncms_get_all_tags()
    {
        wncms()->cache()->tags(['tags'])->forget('wncms_get_all_tags');
        return wncms()->cache()->tags(['tags'])->remember('wncms_get_all_tags', gss('data_cache_time', 3600), function () {
            return Tag::getWithType('link_category')->pluck('name')->toArray();
        });
    }
}

//獲取標籤連結
if (!function_exists('wncms_get_tag_url')) {
    function wncms_get_tag_url($tag)
    {
        if(empty($tag)) return;
        $cacheKey = "wncms_get_tag_url_{$tag->name}_" . wncms()->getDomain();
        $cacheTags = ['tags'];
        wncms_clear_cache($cacheKey, $cacheKey);
        return wncms()->cache()->tags($cacheKey)->remember($cacheKey, gss('data_cache_time', 3600), function () use ($tag) {
            if ($tag->type == 'post_category') {
                return route('frontend.posts.post_taxonomy', ['post_taxonomy_type' => 'category', 'taxonomy_name' => $tag->name]);
            } elseif ($tag->type == 'post_tag') {
                return route('frontend.posts.post_taxonomy', ['post_taxonomy_type' => 'tag', 'taxonomy_name' => $tag->name]);
            } else {
                return route('frontend.posts.archive', ['taxonomy_type' => $tag->type, 'taxonomy_name' => $tag->name]);
            }
        });
    }
}

if (!function_exists('generate_breadcrumbs')) {
    function generate_breadcrumbs($tag)
    {
        $breadcrumbs = [];

        while ($tag) {
            $breadcrumbs[] = $tag;
            $tag = $tag->parent;
        }

        return array_reverse($breadcrumbs);
    }
}