<?php

namespace Wncms\Services\Managers;

use Wncms\Models\Faq;

class FaqManager
{
    //Cache key prefix that prepend all cache key in this page
    protected $cacheKeyPrefix = "wncms_faq";


    /**
     * ----------------------------------------------------------------------------------------------------
     * Get single faq by id
     * ----------------------------------------------------------------------------------------------------
     * @since 3.1.9
     * @version 3.1.9
     * 
     * @param integer $faqId
     * @return mixed
     * TODO: 加入搜索多個欄位
     * ----------------------------------------------------------------------------------------------------
     */
    function get(?int $faqId = null)
    {
        if(empty($faqId)) return null;
        $method = "get";
        $shouldAuth = false;
        $cacheKeyDomain = empty($websiteId) ? wncms()->getDomain() : '';
        $cacheKey = wncms()->cache()->createKey($this->cacheKeyPrefix, $method, $shouldAuth, wncms()->getAllArgs(__METHOD__, func_get_args()), $cacheKeyDomain);
        $cacheTags = ['faqs'];
        $cacheTime = gss('enable_cache') ? gss('data_cache_time') : 0;
        //wncms()->cache()->clear($cacheKey, $cacheTags);
        // dd($cacheKey);

        return wncms()->cache()->tags($cacheTags)->remember($cacheKey, $cacheTime, function () use ($faqId) {
            info("no cache");
            $website = wncms()->website()->getCurrent();
            if (!$website) return null;

            $q = $website->faqs();
            $q->where('id', $faqId);
            $faq = $q->first();

            return $faq;
        });
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Get a collect of faqs
     * ----------------------------------------------------------------------------------------------------
     * @since 3.1.9
     * @version 3.1.9
     * @param integer $count Number of faqs to get
     * @param ?int $pageSize How many faqs per page. Will not call paginate() if set to null or 0, set to -1 to paginate all records in one page
     * @param string $order By default, faq will be ordered by id
     * @param string $sequence By Defaultm faq will be sorted in descending order
     * @param string $status 
     * @param integer|null $websiteId By default, faqs will be retrieved from current website
     * @param integer|null $websiteId By default, faqs will be retrieved from current website
     * @return Illuminate\Database\Eloquent\Collection
     * TODO: 設定預設model type
     * ----------------------------------------------------------------------------------------------------
     */
    function getList(
        array|string|null $tags = [],
        ?string $tagType = 'faq_tag',
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
        array|string|int|null $excludedFaqIds = [],
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
        $cacheTags = ['faqs'];
        $cacheTime = gss('enable_cache') ? gss('data_cache_time') : 0;
        wncms()->cache()->clear($cacheKey, $cacheTags);
        // dd($cacheKey);

        return wncms()->cache()->tags($cacheTags)->remember($cacheKey, $cacheTime, function () use ($tags, $tagType, $keywords, $count, $pageSize, $order, $sequence, $status, $wheres, $websiteId, $excludedFaqIds, $excludedTagIds, $ids, $select, $withs, $offset, $excludedChildrenTags) {
            $website = wncms()->website()->get($websiteId, false);
            if(empty($website)) return collect([]);

            $q = $website->faqs();

            if(!empty($excludedFaqIds)){
                if(is_string($excludedFaqIds)){
                    $excludedFaqIds = explode(',', $excludedFaqIds);
                }

                $q->whereNotIn('faqs.id', (array)$excludedFaqIds);
            }

            //% Add tag from PostManager
            // if (!empty($tags)) {
            //     if(!$excludedChildrenTags){
            //         $_tags = wncms()->tag()->getList(tagType:$tagType,tagIds:$tags,count:999,withs:[]);
            //         $tagNames = [];
            //         foreach($_tags as $_tag){
            //             $tagNames = array_merge($tagNames, $_tag->descendantsAndSelf?->pluck('name')->toArray() ?? [], );
            //         }

            //         $q->withAnyTags($tagNames, $tagType);
            //     }else{
                    
            //         $q->where(function($subq)use($tagIds, $tagType){
            //             $subq->withAnyTags($tagIds, $tagType)->orWhereIn('tags.id', $tagIds);
            //         });
            //     }
            // }

            if(!empty($ids)){
                if(is_string($ids)){
                    $ids = explode(',', $ids);
                }
                $q->whereIn('faqs.id', $ids);
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

                        if(gto('search_faq_content')){
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
                        info('condition error in faqs query. $wheres = ' . json_encode($wheres));
                    }
                }
            }

            if(!empty($withs)){
                $q->with($withs);
            }

            //status
            $q->where('status', $status);
            
            //ordering
            $q->orderBy('is_pinned', 'desc');
            $q->orderBy($order, in_array($sequence, ['asc', 'desc']) ? $sequence : 'desc');
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
                $faqs = $q->paginate($pageSize);

                if($count){
                    $faqs = wncms()->paginateWithLimit(
                        collection:  $faqs,
                        pageSize: $pageSize, 
                        limit: $count,
                    );
                }
            }else{
                $faqs = $q->get();
            }

            return $faqs;
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
