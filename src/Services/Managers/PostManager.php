<?php

namespace Wncms\Services\Managers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class PostManager extends ModelManager
{
    protected string $cacheKeyPrefix = 'wncms_post';
    protected string $defaultTagType = 'post_category';
    protected bool $shouldAuth = false;
    protected string|array $cacheTags = ['posts'];

    public function getModelClass(): string
    {
        return wncms()->getModelClass('post');
    }

    public function get(array $options = []): ?Model
    {
        $options['withs'] = array_merge(['media', 'comments', 'tags', 'translations'], $options['withs'] ?? []);
        return parent::get($options);
    }

    public function getList(array $options = []): Collection|LengthAwarePaginator
    {
        $options['withs'] = array_merge(['media', 'comments', 'tags', 'translations'], $options['withs'] ?? []);
        return parent::getList($options);
    }

    public function getRelated(array|Model|int|null $post, array $options = []): Collection|LengthAwarePaginator
    {
        if (is_int($post)) {
            $post = $this->get(['id' => $post, 'cache' => $options['cache'] ?? true]);
        }

        if (!$post instanceof Model) {
            return collect();
        }

        $tagType = $options['tag_type'] ?? 'post_category';
        $tagNames = $post->tagsWithType($tagType)->pluck('name')->toArray();

        return $this->getList(array_merge([
            'excluded_post_ids' => $post->id,
            'tags' => $tagNames,
            'tag_type' => $tagType,
            'website_id' => $post->website_id,
        ], $options));
    }
    
    protected function buildListQuery(array $options): mixed
    {
        $q = $this->query();
        
        $tags = $options['tags'] ?? [];
        $tagType = $options['tag_type'] ?? 'post_category';
        $keywords = $options['keywords'] ?? [];
        $count = $options['count'] ?? 0;
        $offset = $options['offset'] ?? 0;
        $sort = $options['sort'] ?? 'id';
        $direction= $options['direction'] ?? 'desc';
        $status = $options['status'] ?? 'published';
        $wheres = $options['wheres'] ?? [];
        $websiteId = $options['website_id'] ?? null;
        $excludedPostIds = $options['excluded_post_ids'] ?? [];
        $excludedTagIds = $options['excluded_tag_ids'] ?? [];
        $ids = $options['ids'] ?? [];
        $select = $options['select'] ?? ['*'];
        $withs = $options['withs'] ?? [];
        $isRandom = $options['is_random'] ?? false;

        // $pageSize = $options['page_size'] ?? 0;
        // $pageName = $options['page_name'] ?? 'page';

        $this->applyWebsiteId($q, $websiteId);

        $this->applyWiths($q, array_merge(['media', 'comments', 'tags', 'translations'], $withs));
        $this->applyTagFilter($q, $tags, $tagType);
        $this->applyKeywordFilter($q, $keywords, ['title', 'label', 'excerpt', 'content']);

        $this->applyWhereConditions($q, $wheres);
        $this->applyIds($q, 'posts.id', $ids);
        $this->applyExcludeIds($q, 'posts.id', $excludedPostIds);
        $this->applySelect($q, $select);
        $this->applyOffset($q, $offset);
        $this->applyLimit($q, $count);

        if (!empty($excludedTagIds)) {
            if (is_string($excludedTagIds)) {
                $excludedTagIds = explode(',', $excludedTagIds);
            }

            $q->where(function ($subq) use ($excludedTagIds) {
                $subq->whereHas("tags", function ($subsubq) use ($excludedTagIds) {
                    $subsubq->whereNotIn('tags.id', (array) $excludedTagIds);
                })->orWhereDoesntHave('tags');
            });
        }

        $this->applyStatus($q, 'status', $status);
        $this->applyOrdering($q, $sort, $direction, $isRandom);

        $q->withCount('comments');
        if (!isset($options['select'])) {
            $q->distinct();
        }

        return $q;
    }
}
