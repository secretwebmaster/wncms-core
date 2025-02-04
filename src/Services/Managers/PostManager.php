<?php

namespace Wncms\Services\Managers;


use Wncms\Models\Post;
use Wncms\Models\Tag;
use Illuminate\Pagination\LengthAwarePaginator;

class PostManager
{
    //Cache key prefix that prepend all cache key in this page
    protected $cacheKeyPrefix = "wncms_post";

    /**
     * ----------------------------------------------------------------------------------------------------
     * Get single post by id
     * ----------------------------------------------------------------------------------------------------
     * @since 3.0.0
     * @version 3.1.4
     * 
     * @param string|null $postId
     *      預設值: -
     *      描述: 調用Wncms\Manager目錄下的模組
     *      命名規則: snake_case的model名稱，例如 post，collect_source，系統自動轉換為Post CollectSource
     * 
     * @param array|string|null $args 參數
     *      預設值: null
     *      描述: 傳入Manager方法的參數
     *      例子: wncms()->posts()->getPost(12, true) 中的 $id, $isPublished
     * 
     * @return mixed
     * TODO: 加入搜索多個欄位
     * ----------------------------------------------------------------------------------------------------
     */
    public function get($postId)
    {
        $method = "get";
        $shouldAuth = false;
        $cacheKey = wncms()->cache()->createKey($this->cacheKeyPrefix, $method, $shouldAuth, wncms()->getAllArgs(__METHOD__, func_get_args()));
        $cacheTags = ['posts'];
        $cacheTime = gss('enable_cache') ? gss('data_cache_time') : 0;
        //wncms()->cache()->clear($cacheKey, $cacheTags);

        return wncms()->cache()->tags($cacheTags)->remember($cacheKey, $cacheTime, function () use ($postId) {

            $website = wncms()->website()->getCurrent();
            if (!$website) return null;

            $q = Post::query();

            $q->where('id', $postId);

            $q->with('media', function ($subq) {
                $subq->where('collection_name', 'post_thumbnail');
            });

            $q->with(['comments', 'tags']);

            $post = $q->first();

            return $post;
        });
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Get post collection by parameters
     * ----------------------------------------------------------------------------------------------------
     * @since 3.0.0
     * @version 3.1.5
     * 
     * @param array|string|null $tags Name of tag in current locale
     * @param ?string $tagType By daefult, its set to post_category, should be overriden for other models
     * @param array|string|null $keywords Keyword to be search in title
     * @param ?int $count $number of record to retrieve
     * @param ?int $pageSize How many posts per page. Will not call paginate() if set to null or 0, set to -1 to paginate all records in one page
     * @param string $order The order the record
     * @param string $sequence The sequence used when ordering by $order
     * @param string $status Supported statuses: publish, draft, trash
     * @param ?array $wheres 
     *      custom condition to query to posts
     *      format should follow Eloquent query builder format
     * 
     *      Example:
     *          $wheres = [
     *              ['column', 'operator', 'value'],
     *              ['column', 'operator', 'value'],
     *          ]
     * 
     *      Supported column:
     *          user_id,websiteId
     *          status
     *          publish
     *          visibility
     *          public
     *          external_thumbnail
     *          slug
     *          title
     *          label
     *          excerpt
     *          content
     *          remark
     *          order
     *          password
     *          price
     *          is_pinned
     *          is_recommended
     *          is_dmca
     *          traffic_today
     *          traffic_yesterday
     *          traffic_week
     *          traffic_month
     *          traffic_total
     *          published_at
     *          expired_at
     *          source
     *          ref_id
     * @param integer|null $websiteId By default, null is passed and will only get posts of current domain 
     * @return Collection
     * ! When calling  
     * TODO: 加入where條件，加入whereIn，加入post count
     * ----------------------------------------------------------------------------------------------------
     */
    public function getList(
        array|string|null $tags = [],
        ?string $tagType = 'post_category',
        array|string|null $keywords = [],
        ?int $count = 0,
        ?int $pageSize = 0,
        ?int $page = 0,
        ?int $offset = 0,
        string $order = 'id',
        string $sequence = 'desc',
        string $status = 'published',
        ?array $wheres = [],
        ?int $websiteId = null,
        array|string|int|null $excludedPostIds = [],
        array|string|int|null $excludedTagIds = [],
        array|string|int|null $ids = [],
        array|string|int|null $select = [],
        array $withs = [],
        bool $excludedChildrenTags = false,
        bool $isRandom = false,
        string $pageName = 'page'
    ) {
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
        $cacheTags = ['posts'];
        if ($isRandom) {
            $cacheTime = 0;
        } else {
            $cacheTime = gss('enable_cache') ? gss('data_cache_time') : 0;
        }

        // wncms()->cache()->clear($cacheKey, $cacheTags);

        return wncms()->cache()->tags($cacheTags)->remember($cacheKey, $cacheTime, function () use ($tags, $tagType, $keywords, $count, $pageSize, $order, $sequence, $status, $wheres, $websiteId, $excludedPostIds, $excludedTagIds, $ids, $select, $withs, $offset, $excludedChildrenTags, $isRandom, $pageName) {

            // $website = wncms()->website()->get($websiteId, false);
            $website = wncms()->website()->get($websiteId);
            if (empty($website)) {
                if ($pageSize) {
                    // return empty LengthAwarePaginator
                    return new LengthAwarePaginator([], 0, $pageSize, 0, [
                        'path' => request()->url(),
                        'pageName' => $pageName,
                    ]);
                } else {
                    return collect([]);
                }
            }

            $q = $website->posts();

            if (!empty($excludedPostIds)) {
                if (is_string($excludedPostIds)) {
                    $excludedPostIds = explode(',', $excludedPostIds);
                }

                $q->whereNotIn('posts.id', (array)$excludedPostIds);
            }

            // if (!empty($tags)) {
            //     if(!$excludedChildrenTags){
            //         $_tags = wncms()->tag()->getList(tagType:$tagType,tagIds:$tags,count:999,withs:[]);
            //         $tagNames = [];
            //         foreach($_tags as $_tag){
            //             $tagNames = array_merge($tagNames, $_tag->getAllDescendantsAndSelf()->pluck('name')->toArray() ?? [], );
            //         }
            //         $q->withAnyTags($tagNames, $tagType);
            //     }else{
            //         $q->where(function($subq)use($tagIds, $tagType){
            //             $subq->withAnyTags($tagIds, $tagType)->orWhereIn('tags.id', $tagIds);
            //         });
            //     }
            // }

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
                    $q->where(function ($subq) use ($tagNames, $tagType) {
                        $subq->withAnyTags($tagNames, $tagType);
                    });
                }
            }


            if (!empty($ids)) {
                if (is_string($ids)) {
                    $ids = explode(',', $ids);
                }
                $q->whereIn('posts.id', $ids);
            }

            if (!empty($excludedTagIds)) {

                if (is_string($excludedTagIds)) {
                    $excludedTagIds = explode(',', $excludedTagIds);
                }

                $q->where(function ($subq) use ($excludedTagIds) {
                    $subq->whereHas("tags", function ($subsubq) use ($excludedTagIds) {
                        $subsubq->whereNotIn('tags.id', (array)$excludedTagIds);
                    })
                        ->orWhereDoesntHave('tags');
                });
            }

            if (!empty($keywords)) {
                //search title
                //TODO set searchable item in system setting and allow override in theme option
                $q->where(function ($subq) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        $subq->orWhere('title', 'like', "%$keyword%");
                        // $subq->orWhereRaw("JSON_EXTRACT(title, '$.*') LIKE '%$keyword%'");

                        // TODO: optimzie performance
                        if (gto('search_post_content')) {
                            $subq->orWhere('content', 'like', "%$keyword%");
                            // $subq->orWhereRaw("JSON_EXTRACT(content, '$.*') LIKE '%$keyword%'");
                        }
                    }
                });
            }

            //custom where query
            if (!empty($wheres)) {
                foreach ($wheres as $where) {
                    if (!empty($where[0]) && !empty($where[1]) && !empty($where[2])) {
                        $q->where($where[0], $where[1], $where[2]);
                    } elseif (!empty($where[0]) && !empty($where[1]) && empty($where[2])) {
                        $q->where($where[0], $where[1]);
                    } else {
                        info('condition error in posts query. $wheres = ' . json_encode($wheres));
                    }
                }
            }

            //eagar loading
            $q->with('media', function ($subq) {
                $subq->where('collection_name', 'post_thumbnail');
            });

            $q->with(['comments', 'tags', 'translations']);

            $q->withCount('comments');

            if (!empty($withs)) {
                $q->with($withs);
            }

            //status
            $q->where('status', $status);

            if ($isRandom) {
                $q->inRandomOrder();
            }

            //ordering
            $q->orderBy('is_pinned', 'desc');
            $q->orderBy($order, in_array($sequence, ['asc', 'desc']) ? $sequence : 'desc');
            $q->orderBy('id', 'desc');

            $q->distinct();

            if ($count) {
                $q->limit($count);
            }

            if (is_string($select)) {
                $select = explode(",", $select);
            }
            $q->select($select);

            if ($offset) {
                $q->offset($offset);
            }

            if ($pageSize) {
                $posts = $q->paginate(perPage: $pageSize, columns: ['*'], pageName: $pageName);

                if ($count) {
                    $posts = wncms()->paginateWithLimit(
                        collection: $posts,
                        pageSize: $pageSize,
                        limit: $count,
                        pageName: $pageName,
                    );
                }
            } else {
                $posts = $q->get();
            }

            return $posts;
        });
    }

    public function getBySlug($slug, $websiteId = null)
    {
        return $this->getList(
            wheres: [
                ['slug', $slug],
            ],
            websiteId: $websiteId
        )?->first();
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Get post collection by keywords
     * ----------------------------------------------------------------------------------------------------
     * @since 3.0.0
     * @version 3.0.0
     * 
     * @param array|string|null $keywords Keywords to be search. Will automatically transform to array if string is passed. Suggest to use array in all the time to avoid unexpected explode of string by comma
     * @param ?string $tagType By daefult, its set to post_category, should be overriden for other models
     * @param ?int $count $number of record to retrieve
     * @param ?int $pageSize How many posts per page. Will not call paginate() if set to null or 0, set to -1 to paginate all records in one page
     * @param ?string $order The order the record
     * @param ?string $sequence The sequence used when ordering by $order
     * @param ?string $status Supported statuses: publish, draft, trash
     * @param ?array $wheres 
     *      custom condition to query to posts
     *      format should follow Eloquent query builder format
     * 
     *      Example:
     *          $wheres = [
     *              ['column', 'operator', 'value'],
     *              ['column', 'operator', 'value'],
     *          ]
     * 
     *      Supported column:
     *          user_id,websiteId
     *          status
     *          publish
     *          visibility
     *          public
     *          external_thumbnail
     *          slug
     *          title
     *          label
     *          excerpt
     *          content
     *          remark
     *          order
     *          password
     *          price
     *          is_pinned
     *          is_recommended
     *          is_dmca
     *          traffic_today
     *          traffic_yesterday
     *          traffic_week
     *          traffic_month
     *          traffic_total
     *          published_at
     *          expired_at
     *          source
     *          ref_id
     * @param integer|null $websiteId By default, null is passed and will only get posts of current domain 
     * @return mixed
     * TODO: 加入where條件，加入whereIn
     * ----------------------------------------------------------------------------------------------------
     */
    public function getByKeyword(
        array|string|null $keywords = [],
        ?int $count = 0,
        ?int $pageSize = 0,
        ?string $order = 'id',
        ?string $sequence = 'desc',
        ?string $status = 'published',
        ?int $websiteId = null,
    ) {
        return $this->getList(
            keywords: $keywords,
            count: $count,
            pageSize: $pageSize,
            order: $order,
            sequence: $sequence,
            status: $status,
            websiteId: $websiteId,
        );
    }

    public function getByTag(Tag|int $tag, $count = 0, $pageSize = 0, $order = 'id', $sequence = 'desc', $status = 'published', $wheres = [], $websiteId = null)
    {
        if (is_int($tag)) $tag = wncms()->tag()->get($tag);
        if (empty($tag)) return;

        return $this->getList(
            tags: [$tag->name],
            tagType: $tag->type,
            count: $count,
            pageSize: $pageSize,
            order: $order,
            sequence: $sequence,
            status: $status,
            wheres: $wheres,
            websiteId: $websiteId,
        );
    }

    public function getRelated(
        Post|int $post,
        $tagType = 'post_category',
        $count = 0,
        $pageSize = 0,
        $order = 'id',
        $sequence = 'desc',
        $status = 'published',
    ) {
        if (is_int($post)) $post = wncms()->post()->get($post);
        if (!$post) return collect([]);

        return $this->getList(
            excludedPostIds: $post->id,
            tags: $post->tagsWithType($tagType)->pluck('name')->toArray(),
            tagType: $tagType,
            count: $count,
            pageSize: $pageSize,
            order: $order,
            sequence: $sequence,
            status: $status,
            websiteId: $post->website?->id,
        );
    }

    public function search(string $keyword, ?int $websiteId = null, $count = 0, $pageSize = 0, $page = 1)
    {
        return $this->getList(
            keywords: $keyword,
            websiteId: $websiteId,
            count: $count,
            pageSize: $pageSize,
            page: $page,
        );
    }

    //getter
    public function getCacheKeyPrefix()
    {
        return $this->cacheKeyPrefix;
    }
}
