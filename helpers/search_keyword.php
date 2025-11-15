<?php

//% Deprecated soon
//Search Keyword

use Wncms\Models\Website;

if (!function_exists('wncms_get_search_keywords')) {
    function wncms_get_search_keywords($count, $sort = 'random', $direction = 'desc' ,?Website $website = null)
    {
        $website = $website ?? wn('website')->get();
        if(!$website) return;
        $cacheKey = "wncms_get_search_keywords_{$count}_{$sort}_{$direction}_{$website->id}";
        $cacheTags = ['search_keywords'];
        $cacheTime = gss('enable_cache') ? gss('data_cache_time') : 0;
        // wncms()->cache()->clear($cacheKey, $cacheTags);

        return wncms()->cache()->tags($cacheTags)->remember($cacheKey, $cacheTime, function () use($count, $sort, $direction, $website){
            return $website->search_keywords()
                ->limit($count)
                ->when($sort == 'random', function($q){
                    $q->inRandomOrder();
                })
                ->when(in_array($sort, ['count', 'created_at', 'updated_at']), function($q) use($sort, $direction){
                    $q->orderBy($sort, $direction == 'desc' ? 'desc' : 'asc');
                })
                ->get();
        });
    }
}