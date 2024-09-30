<?php

namespace Wncms\Services\Managers;

use Wncms\Models\Advertisement;

class AdvertisementManager
{
    //Cache key prefix that prepend all cache key in this page
    protected $cacheKeyPrefix = "wncms_advertisement";


    /**
     * ----------------------------------------------------------------------------------------------------
     * Get single advertisement by id
     * ----------------------------------------------------------------------------------------------------
     * @since 3.0.0
     * @version 3.0.0
     * 
     * @param integer $advertisementId
     * @return mixed
     * TODO: 加入搜索多個欄位
     * ----------------------------------------------------------------------------------------------------
     */
    function get(?int $advertisementId = null)
    {
        if(empty($advertisementId)) return null;
        $method = "get";
        $shouldAuth = false;
        $cacheKeyDomain = empty($websiteId) ? wncms()->getDomain() : '';
        $cacheKey = wncms()->cache()->createKey($this->cacheKeyPrefix, $method, $shouldAuth, wncms()->getAllArgs(__METHOD__, func_get_args()), $cacheKeyDomain);
        $cacheTags = ['advertisements'];
        $cacheTime = gss('enable_cache') ? gss('data_cache_time') : 0;
        //wncms()->cache()->clear($cacheKey, $cacheTags);
        // dd($cacheKey);

        return wncms()->cache()->tags($cacheTags)->remember($cacheKey, $cacheTime, function () use ($advertisementId) {
            info("no cache");
            $website = wncms()->website()->getCurrent();
            if (!$website) return null;

            $q = $website->advertisements();
            $q->where('id', $advertisementId);
            $q->with('media', function ($subq) {
                $subq->where('collection_name', 'advertisement_thumbnail');
            });
            $advertisement = $q->first();

            return $advertisement;
        });
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Get a collect of advertisements
     * ----------------------------------------------------------------------------------------------------
     * @since 3.0.0
     * @version 3.0.0
     * @param integer $count Number of advertisements to get
     * @param ?int $pageSize How many advertisements per page. Will not call paginate() if set to null or 0, set to -1 to paginate all records in one page
     * @param string $order By default, advertisement will be ordered by id
     * @param string $sequence By Defaultm advertisement will be sorted in descending order
     * @param string $status 
     * @param integer|null $websiteId By default, advertisements will be retrieved from current website
     * @param integer|null $websiteId By default, advertisements will be retrieved from current website
     * @return Illuminate\Database\Eloquent\Collection
     * TODO: 設定預設model type
     * ----------------------------------------------------------------------------------------------------
     */
    function getList(
        int $count = 0,
        int $pageSize = 0,
        string $order = 'order',
        string $sequence = 'desc',
        string $status = 'active',
        ?array $wheres = [],
        int $websiteId = null,
        bool $includeExpired = false,
        string|array|null $positions = null,
        bool $excludeExpired = true,
    )
    {
        $method = "getList";
        $shouldAuth = false;
        $cacheKeyDomain = empty($websiteId) ? wncms()->getDomain() : '';
        $cacheKey = wncms()->cache()->createKey($this->cacheKeyPrefix, $method, $shouldAuth, wncms()->getAllArgs(__METHOD__, func_get_args()), $cacheKeyDomain);
        $cacheTags = ['advertisements'];
        $cacheTime = gss('enable_cache') ? gss('data_cache_time') : 0;
        // wncms()->cache()->clear($cacheKey, $cacheTags);

        // dd($cacheKey);

        return wncms()->cache()->tags($cacheTags)->remember($cacheKey, $cacheTime, function () use ($count, $pageSize, $order, $sequence, $status, $wheres, $websiteId, $includeExpired, $positions, $excludeExpired) {

            if($websiteId){
                $website = wncms()->website()->get($websiteId, false);
            }else{
                $website = wncms()->website()->getCurrent();
            }

            if (!$website) return collect([]);

            $q = $website->advertisements();

            $q->where('status', $status);

            if($excludeExpired){
                $q->where(function($subq){
                    $subq->whereNull('expired_at')->orWhere('expired_at', ">=", now());
                });
            }

            if(!$includeExpired){
                $q->where(fn($q) => $q->whereDate('expired_at', '>=', now())->orWhereNull('expired_at'));
            }

            if($positions){
                if(is_string($positions)){
                    $positions = explode(",", $positions);
                }
                $q->whereIn('position', $positions);
            }

            $q->orderBy($order, $sequence);
            $q->oldest();
            $q->with('media');
            $q->when($count > 0, fn ($q) => $q->take($count));

            //custom where query
            if(!empty($wheres)){
                foreach($wheres as $where){
                    if(!empty($where[0]) && !empty($where[1]) && !empty($where[2])){
                        $q->where($where[0],$where[1],$where[2]);
                    }elseif(!empty($where[0]) && !empty($where[1]) && empty($where[2])){
                        $q->where($where[0],$where[1]);
                    }else{
                        info('condition error in advertisements query. $wheres = ' . json_encode($wheres));
                    }
                }
            }

            $advertisements = $q->get();

            if($pageSize > 0){
                $advertisements = $advertisements->paginate($pageSize);
            }elseif($pageSize == -1){
                $advertisements = $advertisements->paginate($advertisements->count());
            }
            
            return $advertisements;
        });
    }



}
