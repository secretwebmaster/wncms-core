<?php

//Post
//% 將參數改為array

use Wncms\Models\Post;
use Wncms\Models\Tag;
use Wncms\Models\Website;

if (!function_exists('wncms_get_post')) {
    function wncms_get_post($slug)
    {
        $cacheKey = "wncms_get_post_" . $slug;
        wncms()->cache()->tags(['posts'])->forget($cacheKey);
        $post = wncms()->cache()->tags(['post'])->remember($cacheKey, gss('data_cache_time'), function () use ($slug) {
            return Post::query()
                ->where('slug', $slug)
                ->with('media', function ($q) {
                    $q->where('collection_name', 'post_thumbnail');
                })
                ->with('tags')
                ->with(['comments'])
                ->first();
        });
        return $post;
    }
}

if (!function_exists('wncms_get_posts')) {
    function wncms_get_posts(Website $website = null, array|string|null $category = [], $taxonomy_type = 'post_category', $count = 0, $page_size = 0, $order = 'id', $sequence = 'desc', $status = 'published')
    {
        if (empty($category)) $category = [];
        if (is_string($category)) $category = explode(',', $category);
        //% To DO

        $cacheKey = "wncms_get_posts_" . implode(",", $category) . "_taxonomy_type_{$taxonomy_type}_{$count}_{$page_size}_{$order}_{$sequence}_{$status}";
        wncms()->cache()->tags(['posts'])->forget($cacheKey);
        $posts = wncms()->cache()->tags(['posts'])->remember($cacheKey, gss('data_cache_time'), function () use ($category, $taxonomy_type, $count, $page_size, $order, $sequence, $status) {
            return Post::query()
                ->with('media', function ($q) {
                    $q->where('collection_name', 'post_thumbnail');
                })
                ->with('tags')
                ->with(['comments'])
                ->withCount('comments')
                ->when($category, function ($q) use ($category, $taxonomy_type) {
                    $q->withAnyTags($category, $taxonomy_type);
                })
                ->when($count, fn ($q) => $q->limit($count))
                ->orderBy($order, in_array($sequence, ['asc', 'desc']) ? $sequence : 'desc')
                ->orderBy('id', 'desc')
                ->where('status', $status)
                ->get();
        });

        // $cacheKey, gss('data_cache_time') ?? 3600, function($q) use($category, $taxonomy_type){
        //     return 
        // }

        if (!$page_size) {
            // $posts = $posts->get();
        } else {
            $posts = $posts->paginate($page_size);
        }

        return $posts;
    }
}

if (!function_exists('wncms_get_posts_by_keyword')) {
    function wncms_get_posts_by_keyword(Website $website = null, array|string|null $keywords = [], $count = 0, $page_size = 0, $order = 'id', $sequence = 'desc', $status = 'published')
    {
        if (empty($keywords)) $keywords = [];
        if (is_string($keywords)) $keywords = explode(',', $keywords);

        $cacheKey = "wncms_get_posts_by_keyword_" . implode(",", $keywords) . "_{$count}_{$page_size}_{$order}_{$sequence}_{$status}";
        wncms()->cache()->tags(['posts'])->forget($cacheKey);
        $posts = wncms()->cache()->tags(['posts'])->remember($cacheKey, gss('data_cache_time'), function () use ($website, $keywords, $count, $order, $sequence, $status) {
            return $website->posts()
                ->with('media')
                ->where(function ($q) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        // $q->orWhere('title','like',"%$keyword%");
                        $q->orWhereRaw("JSON_EXTRACT(title, '$.*') LIKE '%$keyword%'");
                    }
                })
                // ->when($count, fn($q)=>$q->limit($count))
                // ->orderBy($order, in_array($sequence, ['asc','desc']) ? $sequence : 'desc')
                // ->orderBy('id','desc')
                // ->where('status',$status)
                ->get();
        });

        // $cacheKey, gss('data_cache_time') ?? 3600, function($q) use($category, $taxonomy_type){
        //     return 
        // }

        if (!$page_size) {
            // $posts = $posts->get();
        } else {
            $posts = $posts->paginate($page_size);
        }

        return $posts;
    }
}

if (!function_exists('wncms_get_posts_by_tag')) {
    function wncms_get_posts_by_tag(Tag $tag, $count = 0, $page_size = 0, $order = 'id', $sequence = 'desc', $status = 'published')
    {
        if (empty($tag)) return;
        $cacheKey = "wncms_get_posts_by_tag_{$tag->id}_{$count}_{$page_size}_{$order}_{$sequence}_{$status}";
        wncms()->cache()->tags(['posts'])->forget($cacheKey);
        $posts = wncms()->cache()->tags(['posts'])->remember($cacheKey, gss('data_cache_time'), function () use ($tag, $count, $page_size, $order, $sequence, $status) {
            $website = wn('website')->get();
            if (!$website) return;

            return $website->posts()
                ->whereRelation('tags', 'id', $tag->id)
                ->with('media', function ($q) {
                    $q->where('collection_name', 'post_thumbnail');
                })
                ->when($count, fn ($q) => $q->limit($count))
                ->orderBy($order, in_array($sequence, ['asc', 'desc']) ? $sequence : 'desc')
                ->orderBy('id', 'desc')
                ->where('status', $status)
                ->get();
        });

        if (!$page_size) {
            // $posts = $posts->get();
        } else {
            $posts = $posts->paginate($page_size);
        }

        return $posts;
    }
}

if (!function_exists('wncms_get_related_posts')) {
    function wncms_get_related_posts(Post $post, array|string|null $category = [], $taxonomy_type = 'post_category', $count = 0, $page_size = 0, $order = 'id', $sequence = 'desc', $status = 'published')
    {
        if (!$post) return collect([]);
        if (empty($category)) $category = [];
        if (is_string($category)) $category = explode(',', $category);
        //% To DO

        $cacheKey = "wncms_get_related_posts_{$post->id}_" . implode(",", $category) . "_taxonomy_type_{$taxonomy_type}_{$count}_{$page_size}_{$order}_{$sequence}_{$status}";
        $cacheTags = ['posts'];
        wncms()->cache()->tags($cacheTags)->forget($cacheKey);
        $posts = wncms()->cache()->tags($cacheTags)->remember($cacheKey, gss('data_cache_time'), function () use ($post, $category, $taxonomy_type, $count, $page_size, $order, $sequence, $status) {
            return Post::query()
                ->with('media', function ($q) {
                    $q->where('collection_name', 'post_thumbnail');
                })
                ->when($category, function ($q) use ($category, $taxonomy_type) {
                    $q->withAnyTags($category, $taxonomy_type);
                })
                ->when($count, fn ($q) => $q->limit($count))
                ->orderBy($order, in_array($sequence, ['asc', 'desc']) ? $sequence : 'desc')
                ->orderBy('id', 'desc')
                ->where('status', $status)
                ->where('id', '<>' , $post->id)
                ->get();
        });

        // $cacheKey, gss('data_cache_time') ?? 3600, function($q) use($category, $taxonomy_type){
        //     return 
        // }

        if (!$page_size) {
            // $posts = $posts->get();
        } else {
            $posts = $posts->paginate($page_size);
        }

        return $posts;
    }
}
