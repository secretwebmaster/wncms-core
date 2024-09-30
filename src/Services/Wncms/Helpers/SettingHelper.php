<?php

namespace Wncms\Services\Wncms\Helpers;

use Wncms\Models\Setting;

class SettingHelper
{
    //Cache key prefix that prepend all cache key in this page
    protected $cacheKeyPrefix = "setting";

    /**
     * ----------------------------------------------------------------------------------------------------
     * Get system setting by key
     * ----------------------------------------------------------------------------------------------------
     * @link https://wncms.cc
     * @since 1.0.0
     * @version 3.0.0
     * @param string|array|null $key The key in database of table settings
     * @param string|array|null $fallback Return when key is not found or value is null
     * @param boolean $fromCache pass false to get value without cache
     * @return string|null
     * @example wncms()->system_seetings()->get('data_cache_time', 3600)
     * @alias gss('data_cache_time', 3600)
     * ----------------------------------------------------------------------------------------------------
     */
    function get(string $key, $fallback = null, $fromCache = true)
    {
        if(empty($fromCache)){
            return Setting::where('key', $key)->first()?->value ?? $fallback;
        }
        $systemSettings = $this->getList();
        return $systemSettings[$key] ?? $fallback;
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Get system settings by multiple keys
     * ----------------------------------------------------------------------------------------------------
     * @link https://wncms.cc
     * @since 1.0.0
     * @version 3.0.0
     * @param array|null $keys Return only specific keys. Return all keys if $keys is not set
     * @return array
     * @example wncms()->system_seetings()->getList()
     * ----------------------------------------------------------------------------------------------------
     */
    function getList(string|array|null $keys = [])
    {
        $method = "getList";
        $shouldAuth = false;
        $cacheKey = wncms()->cache()->createKey($this->cacheKeyPrefix, $method, $shouldAuth, wncms()->getAllArgs(__METHOD__, func_get_args()));
        $cacheTags = ['settings'];
        //! gss() will call this getList funciton. Cannot use gss insite this
        $cacheTime = 3600;
        // wncms()->cache()->clear($cacheKey, $cacheTags);

        return wncms()->cache()->tags($cacheTags)->remember($cacheKey, $cacheTime, function () use($keys){
            // info('no system setting cache');
            try{
                if(!wncms_is_installed()) return [];
                $q = Setting::query();
 
                if(!empty($keys)){
                    if(is_string($keys)){
                        $keys = explode(",", $keys);
                    }
                    $q->whereIn('key', $keys);
                }
    
                //return all keys if empty $keys is passed
                return $q->pluck('value', 'key')->toArray();;
            }catch(\Exception $e){
                logger()->error($e);
                return [];
            }
          
        });
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Update system setting by key
     * ----------------------------------------------------------------------------------------------------
     * @link https://wncms.cc
     * @since 1.0.0
     * @version 3.0.0
     * @param string|array|null $key The key in database of table settings. Create key if key does not exist
     * @param string|array|null $value The value to be updated
     * @return boolean success = true, fail = false
     * @example wncms()->system_seetings()->update('version', '1.0.1');
     * @alias uss('system_name', "WNCMS")
     * ----------------------------------------------------------------------------------------------------
     */
    function update($key, $value)
    {
        $result = Setting::query()->updateOrCreate(
            [
                'key' => $key
            ],
            [
                'value' => $value
            ]
        );

        wncms()->cache()->flush(['settings']);

        return $result !== false;
    }
    
    /**
     * ----------------------------------------------------------------------------------------------------
     * Delete system setting by key
     * ----------------------------------------------------------------------------------------------------
     * @link https://wncms.cc
     * @since 1.0.0
     * @version 3.0.0
     * @param string|array|null $key The key in database of table settings.
     * @return int|boolean 
     *      success = number of data deleted
     *      fail = false
     * @example 
     *      wncms()->system_seetings()->delete('version'); //return false because version is core key
     *      wncms()->settings()->delete('test'); //return 1 if one key is delete
     * ----------------------------------------------------------------------------------------------------
     */
    function delete($key)
    {
        $core_keys = [
            'version',
            'active_models',
            'request_timeout',
        ];

        if(in_array($key, $core_keys)){
            return false;
        }
        
        $result = Setting::where('key', $key)->delete();
        
        wncms()->cache()->tags(['settings'])->flush();

        return $result;
    }

}
