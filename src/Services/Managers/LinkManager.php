<?php

namespace Wncms\Services\Managers;

use Wncms\Models\Link;

class LinkManager
{
    //Cache key prefix that prepend all cache key in this page
    protected $cacheKeyPrefix = "wncms_link";


    /**
     * ----------------------------------------------------------------------------------------------------
     * Get single link by id
     * ----------------------------------------------------------------------------------------------------
     * @param integer $linkId
     * @return mixed
     * TODO: 加入搜索多個欄位
     * ----------------------------------------------------------------------------------------------------
     */
    function get(?int $linkId = null)
    {
        if(empty($linkId)) return null;
        $method = "get";
        $shouldAuth = false;
        $cacheKeyDomain = empty($websiteId) ? wncms()->getDomain() : '';
        $cacheKey = wncms()->cache()->createKey($this->cacheKeyPrefix, $method, $shouldAuth, wncms()->getAllArgs(__METHOD__, func_get_args()), $cacheKeyDomain);
        $cacheTags = ['links'];
        $cacheTime = gss('enable_cache') ? gss('data_cache_time') : 0;
        //wncms()->cache()->clear($cacheKey, $cacheTags);
        // dd($cacheKey);

        return wncms()->cache()->tags($cacheTags)->remember($cacheKey, $cacheTime, function () use ($linkId) {

            $website = wncms()->website()->getCurrent();
            if (!$website) return null;

            $q = $website->links();
            $q->with(['media', 'translations']);
            $q->where('id', $linkId);
            $link = $q->first();

            return $link;
        });
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Get a collect of links
     * ----------------------------------------------------------------------------------------------------
     * @param integer $count Number of links to get
     * @param ?int $pageSize How many links per page. Will not call paginate() if set to null or 0, set to -1 to paginate all records in one page
     * @param string $order By default, link will be ordered by id
     * @param string $sequence By Defaultm link will be sorted in descending order
     * @param string $status 
     * @param integer|null $websiteId By default, links will be retrieved from current website
     * @return Illuminate\Database\Eloquent\Collection
     * TODO: 設定預設model type
     * ----------------------------------------------------------------------------------------------------
     */
    function getList(
        array|string|null $tags = [],
        ?string $tagType = 'link_category',
        array|string|null $keywords = [],
        ?int $count = 0,
        ?int $pageSize = 20,
        ?int $page = 0,
        ?int $offset = 0,
        string $order = 'id',
        string $sequence = 'desc',
        string $status = 'active',
        ?array $wheres = [],
        ?int $websiteId = null,
        array|string|int|null $excludedLinkIds = [],
        array|string|int|null $excludedTagIds = [],
        array|string|int|null $ids = [],
        array|string|int|null $select = [],
        array $withs = [],
        bool $excludedChildrenTags = false,
    )
    {
        //handle categpry
        if (empty($tags)) $tags = [];
        if (is_string($tags)) $tags = explode(',', $tags);

        //handle keywords
        if (empty($keywords)) $keywords = [];
        if (is_string($keywords)) $keywords = explode(',', $keywords);

        $method = "getList";
        $shouldAuth = false;
        $cacheKeyDomain = empty($websiteId) ? wncms()->getDomain() : '';
        $cacheKey = wncms()->cache()->createKey($this->cacheKeyPrefix, $method, $shouldAuth, wncms()->getAllArgs(__METHOD__, func_get_args()), $cacheKeyDomain);
        $cacheTags = ['links'];
        $cacheTime = gss('enable_cache') ? gss('data_cache_time') : 0;
        // wncms()->cache()->clear($cacheKey, $cacheTags);

        return wncms()->cache()->tags($cacheTags)->remember($cacheKey, $cacheTime, function () use ($tags, $tagType, $keywords, $count, $pageSize, $order, $sequence, $status, $wheres, $websiteId, $excludedLinkIds, $excludedTagIds, $ids, $select, $withs, $offset, $excludedChildrenTags) {

            $q = Link::query();

            if(!empty($excludedLinkIds)){
                if(is_string($excludedLinkIds)){
                    $excludedLinkIds = explode(',', $excludedLinkIds);
                }

                $q->whereNotIn('links.id', (array)$excludedLinkIds);
            }

            if(!empty($ids)){
                if(is_string($ids)){
                    $ids = explode(',', $ids);
                }
                $q->whereIn('links.id', $ids);
            }

            if (!empty($tags)) {
                $tagNames = [];
                
                foreach ($tags as $tag) {
                    if ($tag instanceof \Wncms\Models\Tag) {
                        $tagNames[] = $tag->name;
                    } elseif (is_numeric($tag)) {
                        $tagModel = \Wncms\Models\Tag::where('id', $tag)->where('type', $tagType)->first();
                        if ($tagModel) {
                            $tagNames[] = $tagModel->name;
                        }
                    } elseif (is_string($tag)) {
                        $tagModel = \Wncms\Models\Tag::where('name', $tag)->where('type', $tagType)->first();
                        if ($tagModel) {
                            $tagNames[] = $tagModel->name;
                        }
                    }
                }

                if (!empty($tagNames)) {
                    $q->where(function($subq) use ($tagNames, $tagType) {
                        $subq->withAnyTags($tagNames, $tagType);
                    });
                }
            }

            if(!empty($excludedTagIds)){

                if(is_string($excludedTagIds)){
                    $excludedTagIds = explode(',', $excludedTagIds);
                }

                $q->where(function($subq) use($excludedTagIds){
                    $subq->whereHas("tags", function($subsubq) use($excludedTagIds){
                        $subsubq->whereNotIn('tags.id', (array)$excludedTagIds);
                    })
                    ->orWhereDoesntHave('tags');
                });

            }
            
            if(!empty($keywords)){
                //search title
                //TODO set searchable item in system setting and allow override in theme option
                $q->where(function($subq) use($keywords){
                    foreach ($keywords as $keyword) {
                        // $q->orWhere('title','like',"%$keyword%");
                        $subq->orWhereRaw("JSON_EXTRACT(title, '$.*') LIKE '%$keyword%'");

                        if(gto('search_link_content')){
                            $subq->orWhereRaw("JSON_EXTRACT(content, '$.*') LIKE '%$keyword%'");
                        }
                    }
                });
            }
  
            //custom where query
            if(!empty($wheres)){
                foreach($wheres as $where){
                    if(!empty($where[0]) && !empty($where[1]) && !empty($where[2])){
                        $q->where($where[0],$where[1],$where[2]);
                    }elseif(!empty($where[0]) && !empty($where[1]) && empty($where[2])){
                        $q->where($where[0],$where[1]);
                    }else{
                        info('condition error in links query. $wheres = ' . json_encode($wheres));
                    }
                }
            }

            if(!empty($withs)){
                $q->with($withs);
            }else{
                $q->with(['media']);
            }
            $q->with(['translations']);

            //status
            $q->where('status', $status);

            //ordering
            $q->orderBy('is_pinned', 'desc');

            if($order == 'random'){
                $q->inRandomOrder();
            }else{
                $q->orderBy($order, in_array($sequence, ['asc', 'desc']) ? $sequence : 'desc');
            }
            $q->orderBy('id', 'desc');

            $q->distinct();

            if($count){
                $q->limit($count);
            }

            if(empty($select)){
                $select = ['*'];
            }if(is_string($select)){
                $select = explode(",", $select);
            }
            $q->select($select);
            
            if($offset){
                $q->offset($offset);
            }

            if($pageSize){
                $links = $q->paginate($pageSize);

                if($count){
                    $links = wncms()->paginateWithLimit(
                        collection:  $links,
                        pageSize: $pageSize, 
                        limit: $count,
                    );
                }
            }else{
                $links = $q->get();
            }

            return $links;
        });
    }

    
    public function getBySlug($slug, $websiteId = null)
    {
        return $this->getList(
            wheres: [
                ['slug',$slug],
            ],
            websiteId:$websiteId
        )?->first();
    }


}
