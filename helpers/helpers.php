<?php

if (!function_exists('wncms')) {
    function wncms(): \Wncms\Services\Wncms
    {
        static $instance = null;

        if (!$instance) {
            if (function_exists('app')) {
                $instance = app(\Wncms\Services\Wncms::class);
            } else {
                $instance = new \Wncms\Services\Wncms();
            }
        }

        return $instance;
    }
}

if (!function_exists('gss')) {
    function gss($key, $fallback = null, $fromCache = true)
    {
        try {
            return wncms()->setting()->get($key, $fallback, $fromCache);
        } catch (\Exception $e) {
            if (wncms_is_installed()) {
                logger()->error("Call gss error. key: $key");
            }
        }
    }
}

if (!function_exists('uss')) {
    function uss($key, $value)
    {
        return wncms()->setting()->update($key, $value);
    }
}

if (!function_exists('gto')) {
    /**
     * get theme option or full theme option list
     */
    function gto($key = null, $fallback = '', $locale = null, $fallbackWhenEmpty = true)
    {
        // get current website
        $website = wncms()->website()->get();
        if (!$website) return $fallback;

        $scope = 'theme';
        $group = $website->theme;
        $locale ??= app()->getLocale();

        $cacheKey = "theme_options_{$locale}_" . wncms()->getDomain();
        $cacheTags = ['websites'];
        $cacheTime = gss('theme_options_cache_time', 86400);
        // cache()->tags($cacheTags)->clear($cacheKey);

        $themeptions = wncms()->cache()->tags($cacheTags)->remember($cacheKey, $cacheTime, function () use ($website, $scope, $group, $locale) {
            return $website->getOptions($scope, $group)->pluck('value', 'key')->toArray();
        });

        // get all theme options
        if ($key === null) {
            return $themeptions;
        }

        if($fallbackWhenEmpty && array_key_exists($key, $themeptions) && empty($themeptions[$key])){
            return $fallback;
        }

        return array_key_exists($key, $themeptions) ? $themeptions[$key] : $fallback;
        // return wncms_get_theme_option($key, $fallback, $locale, $fallbackWhenEmpty);
    }
}

if (!function_exists('isAdmin')) {
    /**
     * ----------------------------------------------------------------------------------------------------
     * Check if user is admin
     * ----------------------------------------------------------------------------------------------------
     * @link https://wncms.cc
     * @since 3.0.0
     * @version 3.0.0
     * @param string|null $user_id User model id. If $user_id is not passed. Check auth()->user() instead
     * @return boolean true if user is admin
     * TODO: Store $adminRoles in database and let users to edit
     * ----------------------------------------------------------------------------------------------------
     */
    function isAdmin($user_id = null)
    {
        $adminRoles = ['superadmin', 'admin'];
        if (!empty($user_id)) {
            $user = wn('user')->get($user_id);
            if ($user) {
                return $user->hasRole($adminRoles);
            } else {
                return false;
            }
        }

        return auth()->user()?->hasRole($adminRoles) ? true : false;
    }
}
