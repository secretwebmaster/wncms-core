<?php

if (!function_exists('wncms')) {
    /**
     * ----------------------------------------------------------------------------------------------------
     * WNCMS Core function class
     * ----------------------------------------------------------------------------------------------------
     * @link https://wncms.cc
     * @since 3.0.0
     * @version 3.0.0
     */
    function wncms()
    {
        return new \Wncms\Services\Wncms;
    }
}

if (!function_exists('gss')) {
    function gss($key, $fallback = null, $fromCache = true)
    {
        try{
            return wncms()->setting()->get($key, $fallback, $fromCache);
        }catch(\Exception $e){
            if(wncms_is_installed()){
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
    function gto($key = null, $fallback = '', $locale = null, $fallbackWhenEmpty = true)
    {
        return wncms_get_theme_option($key, $fallback, $locale, $fallbackWhenEmpty);
    }
}

if (!function_exists('wn')) {
    function wn(?string $model = null)
    {
        if(!empty($model)){
            return wncms()->$model();
        }else{
            return wncms();
        }
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


/**
 * -------------------------------
 * % Methods belowing are deprecating soon
 * -------------------------------
 */

if (! function_exists('isActive')) {
    /**
     * Set the active class to the current opened menu.
     *
     * @param  string|array $route
     * @param  string       $className
     * @return string
     */
    function isActive($route, $className = 'active')
    {
        if (is_array($route)) {
            return in_array(Route::currentRouteName(), $route) ? $className : '';
        }
        if (Route::currentRouteName() == $route) {
            return $className;
        }
        if (strpos(URL::current(), $route)) {
            return $className;
        }
    }
}