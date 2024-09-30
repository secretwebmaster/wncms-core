<?php

//Search Keyword

use Wncms\Models\Website;

if (!function_exists('wncms_get_search_keywords')) {
    function wncms_get_search_keywords($count, $order = 'random', $sequence = 'desc' ,Website $website = null)
    {
        $website = $website ?? wn('website')->get();
        if(!$website) return;
        $cacheKey = "wncms_get_search_keywords_{$count}_{$website->id}";
        $cacheTags = ['search_keywords'];
        $cacheTime = gss('enable_cache') ? gss('data_cache_time') : 0;
        // wncms()->cache()->clear($cacheKey, $cacheTags);

        return wncms()->cache()->tags($cacheTags)->remember($cacheKey, $cacheTime, function () use($count, $order, $sequence, $website){
            return $website->search_keywords()
                ->limit($count)
                ->when($order == 'random', function($q){
                    $q->inRandomOrder();
                })
                ->when(in_array($order, ['count', 'created_at', 'updated_at']), function($q) use($order, $sequence){
                    $q->orderBy($order, $sequence == 'desc' ? 'desc' : 'asc');
                })
                ->get();
        });
    }
}