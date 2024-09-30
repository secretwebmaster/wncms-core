<?php

namespace Wncms\Services\Wncms\Helpers;

use Wncms\Models\User;
use Illuminate\Support\Facades\App;

class UserHelper
{

    //Cache key prefix that prepend all cache key in this page
    protected $cacheKeyPrefix = "wncms_user";

    public function get(string|int|null $userId = null)
    {
        if(empty($userId)) return null;
        $method = "get";
        $shouldAuth = false;
        $cacheKey = wncms()->cache()->createKey($this->cacheKeyPrefix, $method, $shouldAuth, wncms()->getAllArgs(__METHOD__, func_get_args()));
        $cacheTags = ['users'];
        $cacheTime = gss('enable_cache') ? gss('data_cache_time') : 0;
        // wncms()->cache()->clear($cacheKey, $cacheTags);
        // dd($cacheKey);

        return wncms()->cache()->tags($cacheTags)->remember($cacheKey, $cacheTime, function () use ($userId) {
            return  User::find($userId);
        });

    }

    public function query()
    {
        return User::query();
    }

    public function __get($name)
    {
        // dd($name);
        return;
    }

    public function __call($method, $args)
    {
        // dd($method, $args);
        return;
    }
}
