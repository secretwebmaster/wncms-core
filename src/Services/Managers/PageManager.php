<?php

namespace Wncms\Services\Managers;


use Wncms\Models\Page;
use Wncms\Models\Tag;
use Wncms\Models\Website;
use Illuminate\Pagination\LengthAwarePaginator;

class PageManager
{
    /**
     * @var string $cacheKeyPrefix Common cache key for all method in the PageManager class
     */
    protected $cacheKeyPrefix = "wncms_page";

    /**
     * @var array $cacheTags Common cache tags for all method in the PageManager class
     */
    protected $cacheTags = ['pages'];

    /**
     * @var ?int $cacheTime Common cache time for all method in the PageManager class. Value retreived from Wncms\Models\Setting
     */
    protected $cacheTime;


    public function __construct()
    {
        $this->cacheTime = gss('enable_cache') ? gss('data_cache_time') : 0;
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Get a single page model
     * ----------------------------------------------------------------------------------------------------
     * @since 3.0.0
     * @version 3.1.4
     * 
     * @param ?int $pageId           - Id of Page model
     * @param ?int $websiteId           - Id of Page model
     * @param ?int $slug             - Slug of Page model
     * @param ?bool $withComments     - Eagar load comments
     * @return Wncms\Models\Page|null
     * ----------------------------------------------------------------------------------------------------
     */
    public function get(?int $pageId = null, ?int $websiteId = null, ?string $slug = null, ?bool $withComments = false)
    {
        if(empty($pageId) && empty($slug)) return null;

        $shouldAuth = false;
        $cacheKeyDomain = empty($websiteId) ? wncms()->getDomain() : '';
        $cacheKey = wncms()->cache()->createKey($this->cacheKeyPrefix, __FUNCTION__, $shouldAuth, wncms()->getAllArgs(__METHOD__, func_get_args()), $cacheKeyDomain);
        // wncms()->cache()->clear($cacheKey, $this->cacheTags);

        return wncms()->cache()->tags($this->cacheTags)->remember($cacheKey, $this->cacheTime, function () use ($pageId, $websiteId, $slug, $withComments) {
            if($websiteId){
                $website = wncms()->website()->get(websiteId:$websiteId, fallbackToCurrent:false);    
            }else{
                $website = wncms()->website()->getCurrent();
            }


            if (!$website) return null;

            $q = $website->pages();

            if($pageId){
                $q->where('id', $pageId);
            }

            if($slug){
                $q->where('slug', $slug);
            }

            //Eager load only thunbnail instead of all content media to save RAM
            $q->with('media', function ($subq) {
                $subq->where('collection_name', 'page_thumbnail');
            });

            if($withComments){
                $q->with(['comments']);
            }

            return $q->first();
        });
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Get page collection by parameters
     * ----------------------------------------------------------------------------------------------------
     * @since 3.0.0
     * @version 3.1.4
     * 
     * @param array|string|null $tags           - Name of tag in current locale
     * @param ?string $tagType                  - By daefult, its set to page_category, should be overriden for other tag tyype
     * @param array|string|null $keywords       - Keyword to be search in title
     * @param ?int $count                       - Number of record to retrieve
     * @param ?int $pageSize                    - How many pages per page. Will not call paginate() if set to null or 0, set to -1 to paginate all records in one page
     * @param string $order                     - The order the record
     * @param string $sequence                  - The sequence used when ordering by $order
     * @param string $status                    - Supported statuses: publish, draft, trash
     * @param ?array $wheres                    - Custom condition to query to pages. ['column1', 'operator', 'value1'] or ['column2', 'value2']. Pass only 2 parameter when using the like operater
     * @param integer|null $websiteId           - By default, null is passed and will only get pages of current domain 
     * @return Illuminate\Database\Eloquent\Collection
     * TODO: 加入where條件，加入whereIn，加入page count
     * ----------------------------------------------------------------------------------------------------
     */
    public function getList(
        ?int $count = 0,
        ?int $pageSize = 0,
        string $order = 'id',
        string $sequence = 'desc',
        string $status = 'published',
        ?array $wheres = [],
        ?int $websiteId = null,
        array|string|int|null $excludedPageIds = [],
        array|string|int|null $ids = [],
        array|string|int|null $select = [],
    ) {
        $method = "getList";
        $shouldAuth = false;
        $cacheKeyDomain = empty($websiteId) ? wncms()->getDomain() : '';
        $cacheKey = wncms()->cache()->createKey($this->cacheKeyPrefix, $method, $shouldAuth, wncms()->getAllArgs(__METHOD__, func_get_args()), $cacheKeyDomain);
        $cacheTags = ['pages'];
        $cacheTime = gss('enable_cache') ? gss('data_cache_time') : 0;
        // wncms()->cache()->clear($cacheKey, $cacheTags);
        // dd($cacheKey);

        return wncms()->cache()->tags($cacheTags)->remember($cacheKey, $cacheTime, function () use ($count, $pageSize, $order, $sequence, $status, $wheres, $websiteId, $excludedPageIds, $ids, $select) {
            $website = wncms()->website()->get($websiteId, false);
            if (empty($website)) return collect([]);

            $q = $website->pages();

            if (!empty($excludedPageIds)) {
                if (is_string($excludedPageIds)) {
                    $excludedPageIds = explode(',', $excludedPageIds);
                }
                $q->whereNotIn('pages.id', (array)$excludedPageIds);
            }

            if (!empty($ids)) {
                if (is_string($ids)) {
                    $ids = explode(',', $ids);
                }
                $q->whereIn('pages.id', $ids);
            }

            //custom where query
            if (!empty($wheres)) {
                foreach ($wheres as $where) {
                    if (!empty($where[0]) && !empty($where[1]) && !empty($where[2])) {
                        $q->where($where[0], $where[1], $where[2]);
                    } elseif (!empty($where[0]) && !empty($where[1]) && empty($where[2])) {
                        $q->where($where[0], $where[1]);
                    } else {
                        info('condition error in pages query. $wheres = ' . json_encode($wheres));
                    }
                }
            }

            // eagar loading
            $q->with('media', function ($subq) {
                $subq->where('collection_name', 'page_thumbnail');
            });

            $q->with(['comments']);
            $q->withCount('comments');

            //status
            $q->where('status', $status);

            //ordering
            $q->orderBy($order, in_array($sequence, ['asc', 'desc']) ? $sequence : 'desc');
            $q->orderBy('id', 'desc');
            // $select = ['id'];
            if (!empty($select)) {
                if (is_string($select)) {
                    $select = explode(",", $select);
                }
                $q->select($select);;
            }

            if($count){
                $q->limit($count);
            }

            if($pageSize){
                $pages =wncms()->paginateWithLimit(
                    collection: $q->paginate($pageSize),
                    pageSize: $pageSize, 
                    limit: $count,
                );
            }else{
                $pages = $q->get();
            }
            
            return $pages;
        });
    }

    public function getBySlug($slug, $websiteId = null)
    {
        return $this->get(slug:$slug,websiteId:$websiteId);
    }

    public function createDefaultThemeTemplatePages($website, $skipIfExists = true)
    {
        //get available pages nad names
        $templates = collect(config("theme.{$website->theme}.templates"));
        $templateBladeNames = $templates->pluck('blade_name')->toArray();

        //skip already exist
        if (!empty($skipIfExists)) {
            $existingBladeNames = $website->pages()->pluck('blade_name')->toArray();
            $bladeNamesToCreate = array_diff($templateBladeNames, $existingBladeNames);
        } else {
            $bladeNamesToCreate = $templateBladeNames;
        }

        // dd(
        //     $templates,
        //     $templateBladeNames,
        //     $existingBladeNames,
        //     $bladeNamesToCreate,
        // );

        //create page
        $count = 0;
        foreach ($bladeNamesToCreate as $bladeName) {
            $template = $templates->where('blade_name', $bladeName)->first();

            if ($template && view()->exists("frontend.theme.{$website->theme}.pages.{$bladeName}")) {
                $page = $website->pages()->create([
                    'user_id' => auth()->id(),
                    'title' => $template['title'] ?? 'untitled',
                    'slug' => $template['slug'],
                    'blade_name' => $template['blade_name'],
                    'status' => 'drafted',
                    'visibility' => 'public',
                    'type' => 'template',
                ]);

                $count++;
            } else {
                dd('page not exist');
            }
        }

        return $count;
    }
}
