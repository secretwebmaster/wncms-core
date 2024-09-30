<?php

namespace Wncms\Services\Managers;

use Wncms\Models\Banner;

class BannerManager
{
    //Cache key prefix that prepend all cache key in this page
    protected $cacheKeyPrefix = "wncms_banner";


    /**
     * ----------------------------------------------------------------------------------------------------
     * Get single banner by id
     * ----------------------------------------------------------------------------------------------------
     * @since 3.0.0
     * @version 3.0.0
     * 
     * @param integer $bannerId
     * @return mixed
     * TODO: 加入搜索多個欄位
     * ----------------------------------------------------------------------------------------------------
     */
    function get(?int $bannerId = null)
    {
        if(empty($bannerId)) return null;
        $method = "get";
        $shouldAuth = false;
        $cacheKeyDomain = empty($websiteId) ? wncms()->getDomain() : '';
        $cacheKey = wncms()->cache()->createKey($this->cacheKeyPrefix, $method, $shouldAuth, wncms()->getAllArgs(__METHOD__, func_get_args()), $cacheKeyDomain);
        $cacheTags = ['banners'];
        $cacheTime = gss('enable_cache') ? gss('data_cache_time') : 0;
        //wncms()->cache()->clear($cacheKey, $cacheTags);
        // dd($cacheKey);

        return wncms()->cache()->tags($cacheTags)->remember($cacheKey, $cacheTime, function () use ($bannerId) {
            info("no cache");
            $website = wncms()->website()->getCurrent();
            if (!$website) return null;

            $q = $website->banners();
            $q->where('id', $bannerId);
            $q->with('media', function ($subq) {
                $subq->where('collection_name', 'banner_thumbnail');
            });
            $banner = $q->first();

            return $banner;
        });
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Get a collect of banners
     * ----------------------------------------------------------------------------------------------------
     * @since 3.0.0
     * @version 3.0.0
     * @param integer $count Number of banners to get
     * @param ?int $pageSize How many banners per page. Will not call paginate() if set to null or 0, set to -1 to paginate all records in one page
     * @param string $order By default, banner will be ordered by id
     * @param string $sequence By Defaultm banner will be sorted in descending order
     * @param string $status 
     * @param integer|null $websiteId By default, banners will be retrieved from current website
     * @param integer|null $websiteId By default, banners will be retrieved from current website
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
    )
    {
        $method = "getList";
        $shouldAuth = false;
        $cacheKeyDomain = empty($websiteId) ? wncms()->getDomain() : '';
        $cacheKey = wncms()->cache()->createKey($this->cacheKeyPrefix, $method, $shouldAuth, wncms()->getAllArgs(__METHOD__, func_get_args()), $cacheKeyDomain);
        $cacheTags = ['banners'];
        $cacheTime = gss('enable_cache') ? gss('data_cache_time') : 0;
        // wncms()->cache()->clear($cacheKey, $cacheTags);
        // dd($cacheKey);

        return wncms()->cache()->tags($cacheTags)->remember($cacheKey, $cacheTime, function () use ($count, $pageSize, $order, $sequence, $status, $wheres, $websiteId, $includeExpired, $positions) {
            if($websiteId){
                $website = wncms()->website()->get($websiteId, false);
            }else{
                $website = wncms()->website()->getCurrent();
            }
            if (!$website) return collect([]);


            $q = $website->banners();
            $q->where('status', $status);

            if(!$includeExpired){
                $q->where(fn($q) => $q->whereDate('expired_at', '>=', now())->orWhereNull('expired_at'));
            }

            if($positions){
                if(is_string($positions)){
                    $positions = explode(",", $positions);
                }
                $q->where(function($subq) use($positions){
                    foreach ($positions as $position) {
                        $subq->orWhereJsonContains('positions', $position);
                    }
                });
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
                        info('condition error in banners query. $wheres = ' . json_encode($wheres));
                    }
                }
            }

            $banners = $q->get();

            if($pageSize > 0){
                $banners = $banners->paginate($pageSize);
            }elseif($pageSize == -1){
                $banners = $banners->paginate($banners->count());
            }
            return $banners;
        });
    }



}
