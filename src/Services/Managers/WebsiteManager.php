<?php

namespace Wncms\Services\Managers;

use Wncms\Models\Website;

/**
 * ----------------------------------------------------------------------------------------------------
 * Global Manager class for website models
 * ----------------------------------------------------------------------------------------------------
 */
class WebsiteManager
{
    /**
     * @var string $cacheKeyPrefix Common cache key for all method in the WebsiteManager class
     */
    protected $cacheKeyPrefix = "wncms_website";

    /**
     * @var array $cacheTags Common cache tags for all method in the WebsiteManager class
     */
    protected $cacheTags = ['websites'];

    /**
     * @var ?int $this->cacheTime Common cache time for all method in the WebsiteManager class. Value retreived from Wncms\Models\Setting
     */
    protected $cacheTime;

    public function __construct()
    {
        $this->cacheTime = gss('enable_cache') ? gss('data_cache_time') : 0;
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Get a single website model
     * ----------------------------------------------------------------------------------------------------
     * @link https://wncms.cc
     * @since 3.0.0
     * @version 3.1.4
     * 
     * @param ?int $websiteId           - Id of the website model
     * @param bool $fallbackToCurrent   - Fallback to current website if no website is found
     * @param array $withs              - Eagar load relationships
     * @return Wncms\Models\Website
     * TODO: Add where?
     * TODO: Add auth to user?
     * ----------------------------------------------------------------------------------------------------
     */
    public function get(?int $websiteId = null, ?bool $fallbackToCurrent = true, array $withs = [])
    {
        try{
            $domain = wncms()->getDomain();
        
            if (empty($websiteId)) {
                $website = $this->getByDomain($domain);
    
                if ($website || !$fallbackToCurrent) {
                    return $website;
                }
            }
    
            $shouldAuth = false;
            $cacheKey = wncms()->cache()->createKey($this->cacheKeyPrefix, __FUNCTION__, $shouldAuth, wncms()->getAllArgs(__METHOD__, func_get_args()));
            // wncms()->cache()->clear($cacheKey, $this->cacheTags);
    
            return wncms()->cache()->tags($this->cacheTags)->remember($cacheKey, $this->cacheTime, function () use ($websiteId, $fallbackToCurrent, $withs, $domain) {
      
                $q = Website::query();
    
                $q->with(['media', 'translations']);
    
                $q->with($withs);
    
                if (!empty($websiteId)) {
                    $website = $q->where('id', $websiteId)->first();
                } else {
                    $website = $q->where(function($subq) use($domain){
                        $subq->where('domain', request()->getHost())
                        ->orWhere('domain', wncms()->getDomain())
                        ->orWhereRelation('domain_aliases', 'domain', $domain);
                    })
                    ->first();
                }
    
                if (!$website && $fallbackToCurrent) {
                    $website = Website::first();
                }
         
                return $website;
            });
        }catch(\Exception $e){
            logger()->error($e);
        }

    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Get a website model by domain
     * ----------------------------------------------------------------------------------------------------
     * @link https://wncms.cc
     * @since 3.0.0
     * @version 3.1.4
     * 
     * @param string|null $domain
     * @return Wncms\Models\Website
     * TODO:: Also search for alias
     * ----------------------------------------------------------------------------------------------------
     */
    public function getByDomain(?string $domain = null)
    {
        try{
            if (empty($domain)) return null;
            $shouldAuth = false;
            $cacheKey = wncms()->cache()->createKey($this->cacheKeyPrefix, __FUNCTION__, $shouldAuth, wncms()->getAllArgs(__METHOD__, func_get_args()));
            // wncms()->cache()->clear($cacheKey, $this->cacheTags);
    
            $website = wncms()->cache()->tags($this->cacheTags)->remember($cacheKey, $this->cacheTime, function () use ($domain) {

                $q = Website::query();
                $q->where('domain', $domain);
                $q->orWhereRelation('domain_aliases', 'domain', $domain);
                $q->with(['media', 'translations']);

                // return null placeholder to avoid null value not being cached
                return $q->first() ?? false;
            });

            return $website ? $website : null;

        }catch(\Exception $e){
            logger()->error($e);
        }
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Get website of current request. No fallback
     * ----------------------------------------------------------------------------------------------------
     * @link https://wncms.cc
     * @since 3.0.0
     * @version 3.0.0
     * 
     * @return Wncms\Models\Website|null
     * ----------------------------------------------------------------------------------------------------
     */
    public function getCurrent()
    {
        return $this->get(websiteId:null, fallbackToCurrent:false);
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Get a collect of website
     * ----------------------------------------------------------------------------------------------------
     * @link https://wncms.cc
     * @since 1.0.0
     * @version 3.1.15
     * 
     * @param string|int|array|null $websiteIds     - Array of id of the models
     * @param int $count                            - Number of models to get
     * @param int $page_size                        - 
     * @param array $wheres
     * @return Illuminate\Database\Eloquent\Collection
     * @example getList([1,2])
     * ----------------------------------------------------------------------------------------------------
     */
    public function getList(
        string|int|array|null $websiteIds = [],
        int $count = 0,
        int $page_size = 0,
        array $wheres = [],
        string $order = 'id',
        string $sequence = 'desc',
    ) {
        $shouldAuth = false;
        $cacheKey = wncms()->cache()->createKey($this->cacheKeyPrefix, __FUNCTION__, $shouldAuth, wncms()->getAllArgs(__METHOD__, func_get_args()));
        // wncms()->cache()->clear($cacheKey, $this->cacheTags);
        // dd($cacheKey);

        return wncms()->cache()->tags($this->cacheTags)->remember($cacheKey, $this->cacheTime, function () use ($websiteIds, $count, $page_size, $wheres, $order, $sequence) {
            $q = Website::query();

            if (!empty($websiteIds)) {
                $q->whereIn('id', $websiteIds);
            }

            //custom where query
            if (!empty($wheres)) {
                foreach ($wheres as $where) {
                    if (!empty($where[0]) && !empty($where[1]) && !empty($where[2])) {
                        $q->where($where[0], $where[1], $where[2]);
                    } elseif (!empty($where[0]) && !empty($where[1]) && empty($where[2])) {
                        $q->where($where[0], $where[1]);
                    } else {
                        info('condition error in website query. $wheres = ' . json_encode($wheres));
                    }
                }
            }

            $q->with(['media', 'translations']);

            $q->orderBy($order, in_array($sequence, ['asc', 'desc']) ? $sequence : 'desc');

            if ($count > 0) {
                $q->limit($count);
            }

            if (!$page_size) {
                $websites = $q->get();
            } else {
                $websites = $q->paginate($page_size);
            }

            return $websites;
        });
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Get a collect of website of a specific user
     * ----------------------------------------------------------------------------------------------------
     * @link https://wncms.cc
     * @since 1.0.0
     * @version 3.1.15
     * @param integer|null $userId Pass null to get current auth user
     * @return Illuminate\Database\Eloquent\Collection
     * @example getListByUser(1)
     * ----------------------------------------------------------------------------------------------------
     */
    public function getListByUser(string|int|null $userId = null)
    {
        $shouldAuth = false;
        $userId ??= auth()->id();
        $cacheKey = wncms()->cache()->createKey($this->cacheKeyPrefix, __FUNCTION__, $shouldAuth, wncms()->getAllArgs(__METHOD__, func_get_args()), $userId);
        // wncms()->cache()->clear($cacheKey, $this->cacheTags);
        // dd($cacheKey);

        return wncms()->cache()->tags($this->cacheTags)->remember($cacheKey, $this->cacheTime, function () use ($userId) {
            $user = wncms()->user()->get($userId);
            if (empty($user)) return null;
            if(isAdmin()){
                return wncms()->website()->getList();
            }
            return  $user->websites;
        });
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Get value of single website model
     * ----------------------------------------------------------------------------------------------------
     * @link https://wncms.cc
     * @since 1.0.0
     * @version 3.0.0
     * @param Website|string|array|null $websiteIds Website
     * @return Illuminate\Database\Eloquent\Collection
     * @example getList([1,2])
     * ----------------------------------------------------------------------------------------------------
     */
    public function getValue(Website|string|int|null $websiteId, string $column, $fallback = null, $fallbackOnNullOnly = true)
    {
        if (empty($websiteId)) {
            return null;
        } elseif ($websiteId instanceof Website) {
            $website = $websiteId;
        } else {
            $website = $this->get($websiteId, false);
            if (empty($website)) return null;
        }

        //only return when value is null, not including empty string
        if ($fallbackOnNullOnly && $website->{$column} === null) {
            return $fallback;
        }
        return $website->{$column} ?: $fallback;
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Return list of domains, including aliases, which enabled full page cache 
     * ----------------------------------------------------------------------------------------------------
     * @link https://wncms.cc
     * @since 1.0.7
     * @version 3.0.7
     * @return array
     * ----------------------------------------------------------------------------------------------------
     */
    public function getDomainsWithCache()
    {
        $shouldAuth = false;
        $cacheKey = wncms()->cache()->createKey($this->cacheKeyPrefix, __FUNCTION__, $shouldAuth, wncms()->getAllArgs(__METHOD__, func_get_args()));
        // wncms()->cache()->clear($cacheKey, $this->cacheTags);

        $domains = wncms()->cache()->tags($this->cacheTags)->rememberForever($cacheKey, function () {
            $domainArray = [];
            $websites = Website::where('enabled_page_cache', true)->get();
            foreach ($websites as $website) {
                $domainArray[] = $website->domain;
                $domainArray = array_merge($domainArray, $website->domain_aliases()->pluck('domain')->toArray());
            }
            return $domainArray;
        });
        return $domains;
    }
}
