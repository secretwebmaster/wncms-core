<?php

//Theme
if (!function_exists('wncms_get_theme_option')) {
    function wncms_get_theme_option($key = null, $fallback = '', $locale = null, $fallbackWhenEmpty = true)
    {
        $locale ??= app()->getLocale();
        $cacheKey = "theme_options_{$locale}_" . wncms()->getDomain();
        $cacheTags = ['websites'];
        $cacheTime = gss('data_cache_time', 3600);
        // wncms()->cache()->clear($cacheKey, $cacheTags);

        $theme_options = wncms()->cache()->tags($cacheTags)->remember($cacheKey, $cacheTime, function () use($locale){
            $website = wncms()->website()->get();
            if (!$website) return;
            return  $website->get_options($locale);
        });

        if(empty($key)){
            return $theme_options;
        }
        
        if($fallbackWhenEmpty && array_key_exists($key, $theme_options) && empty($theme_options[$key])){
            return $fallback;
        }

        return array_key_exists($key, $theme_options) ? $theme_options[$key] : $fallback;
    }
}