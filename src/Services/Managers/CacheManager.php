<?php

namespace Wncms\Services\Managers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;

class CacheManager
{
    /**
     * ----------------------------------------------------------------------------------------------------
     * Get cache by key
     * ----------------------------------------------------------------------------------------------------
     * @since 3.0.0
     * @version 3.1.3
     * 
     * @param string|null $cacheKey Name of key
     * ----------------------------------------------------------------------------------------------------
     */
    public function get(string $cacheKey)
    {
        return cache()->get($cacheKey);
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Put cache by key
     * ----------------------------------------------------------------------------------------------------
     * @since 3.0.0
     * @version 3.1.3
     * 
     * @param string|null $cacheKey Name of key
     * ----------------------------------------------------------------------------------------------------
     */
    public function put(string $cacheKey, $cacheValue = null, $cacheTime = 0, array|string|null $cacheTags = null)
    {
        if(!empty($cacheTags)){
            if(is_string($cacheTags)){
                $cacheTags = explode(",", $cacheTags);
            }
            return $this->tags($cacheTags)->put($cacheKey, $cacheValue, $cacheTime);
        }
       return wncms()->put($cacheKey, $cacheValue, $cacheTime);
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Clear cache by key
     * ----------------------------------------------------------------------------------------------------
     * @since 3.0.0
     * @version 3.0.0
     * 
     * @param string|null $cacheKey Name of key
     * @param array|string|null $cacheTags
     * @return boolean  success = true, fail = false
     * TODO: Check if needed to clear cloudflare cache
     * ----------------------------------------------------------------------------------------------------
     */
    public function clear(string $cacheKey, array|string|null $cacheTags = null)
    {
        //沒有使用tag
        if (empty($cacheTags)) {
            if (empty($cacheKey)) return cache()->flush();
            return cache()->forget($cacheKey);
        }

        //有Tag
        $cacheTags = is_array($cacheTags) ? $cacheTags : [$cacheTags];

        if (empty($cacheKey)) return $this->tags($cacheTags)->flush();

        return $this->tags($cacheTags)->forget($cacheKey);
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Flush all cache
     * ----------------------------------------------------------------------------------------------------
     * @since 3.0.0
     * @version 3.0.0
     * 
     * @param array|string|null $cacheTags
     * @return boolean  success = true, fail = false
     * TODO: Check if needed to clear cloudflare cache
     * ----------------------------------------------------------------------------------------------------
     */
    public function flush(array|string|null $cacheTags = null)
    {
        //flush all if $cacheTags is not passed
        if (empty($cacheTags)) {
            return cache()->flush();
        }
        
        $cacheTags = is_array($cacheTags) ? $cacheTags : explode(",", $cacheTags);

        return $this->tags($cacheTags)->flush();
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Flush all cache
     * ----------------------------------------------------------------------------------------------------
     * @since 3.0.0
     * @version 3.1.4
     * 
     * @param mixed $args
     *      $args[0] = $class->cacheKeyPrefix
     *      $args[1] = __FUNCTION__
     *      $args[2] = $shouldAuth, 0 = no need to auth, pass user id if should cache data for logged user
     *      $args[0] = $cacheKeyPrefix
     * ----------------------------------------------------------------------------------------------------
     */
    public function createKey(...$args)
    {
        //base key
        $cacheKey = '';
        $cacheKey .= (!empty($args[0]) ? $args[0] : 'noPrefix') . '_';
        $cacheKey .= (!empty($args[1]) ? $args[1] : 'noMethod') . '_';
        $cacheKey .= (!empty($args[2]) ? $args[2] : 'noAuth') . ''; // $args[3] will add "_"
        
        //method parameters
        if(!empty($args[3])){
            foreach($args[3] as $arg){
                $cacheKey .= "_";
                if(empty($arg)){
                    $cacheKey .= '0';
                }elseif(is_array($arg)){
                    $cacheKey .=  json_encode($arg);
                }elseif(is_string($arg) || is_int($arg)){
                    $cacheKey .= $arg;
                }elseif($arg instanceof Model ){
                    $cacheKey .= $arg->getKey();
                }
            }
        }

        //domain specific
        if(!empty($args[4])){
            $cacheKey .= "_" . $args[4];
        }

        //pages
        // $cacheKey .= request()->page;
        $cacheKey .= json_encode(request()->all());

        return md5($cacheKey);
    }

    public function tags(array|string $cacheTags = 'wncms')
    {
        if (Cache::getStore() instanceof \Illuminate\Cache\TaggableStore) {
            return cache()->tags($cacheTags);
        } else {
            return cache();
        }

        
    }
}