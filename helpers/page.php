<?php
/**
 * Page helpers
 */

use Wncms\Models\Page;

if (!function_exists('wncms_get_page')) {
    function wncms_get_page($id = null, $slug = null)
    {

        $cacheKey = "wncms_get_page_{$id}_{$slug}";
        $cacheTags = ['pages'];
        // wncms_clear_cache($cacheKey, $cacheTags);
        $page = wncms()->cache()->tags($cacheTags)->remember($cacheKey, gss('data_cache_time'), function () use ($id, $slug) {
            return Page::query()
                ->where(function ($q) use ($id, $slug) {
                    $q->where('id', $id)->orWhere('slug', $slug);
                })
                ->with('media', function ($q) {
                    $q->where('collection_name', 'page_thumbnail');
                })
                ->first();
        });
        return $page;
    }
}

if (!function_exists('wncms_get_pages')) {
    function wncms_get_pages(Website $website = null)
    {
        $cacheKey = "wncms_get_pages" . $website?->id;
        $cacheTags = ['pages'];
        // wncms_clear_cache($cacheKey, $cacheTags);
        $pages = wncms()->cache()->tags($cacheTags)->remember($cacheKey, gss('data_cache_time'), function () use ($website) {
            if ($website) {
                return $website->pages()->with('media')->get();
            } else {
                return Page::query()->with('media')->get();
            }
        });
        return $pages;
    }
}