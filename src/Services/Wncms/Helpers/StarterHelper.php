<?php

namespace Wncms\Services\Wncms\Helpers;

class StarterHelper
{

    //Cache key prefix that prepend all cache key in this page
    protected $cacheKeyPrefix = "wncms_starter";

    /**
     * ----------------------------------------------------------------------------------------------------
     * Get domain from string
     * ----------------------------------------------------------------------------------------------------
     * @since 3.0.0
     * @version 3.0.0
     * @param string|null $url A valid url
     * @return string
     * ----------------------------------------------------------------------------------------------------
     */
    public function get($websiteId = null)
    {
        $method = "get";
        $shouldAuth = false;
        $cacheKeyDomain = empty($websiteId) ? wncms()->getDomain() : '';
        $cacheKey = wncms()->cache()->createKey($this->cacheKeyPrefix, $method, $shouldAuth, wncms()->getAllArgs(__METHOD__, func_get_args()), $cacheKeyDomain);
        $cacheTags = ['starters'];
        $cacheTime = gss('enable_cache') ? gss('data_cache_time') : 0;
        //wncms()->cache()->clear($cacheKey, $cacheTags);
        // dd($cacheKey);

        return wncms()->cache()->tags($cacheTags)->remember($cacheKey, $cacheTime, function () use($websiteId){
            $q = Website::query();
            $q->with('media');

            if(!empty($websiteId)){
                $website = $q->where('id', $websiteId);
            }else{
                $website = $q->where('domain', request()->getHost())->orWhere('domain', wncms()->getDomain());
            }

            $website = $q->first();

            return $website;
        });
    }
}
