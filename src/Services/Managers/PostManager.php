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
        $searchFields = $options['search_fields'] ?? ['title', 'label', 'excerpt', 'content'];
        $count = $options['count'] ?? 0;
        $offset = $options['offset'] ?? 0;
        $sort = $this->normalizeSortColumn((string) ($options['sort'] ?? 'id'));
        $direction = strtolower((string) ($options['direction'] ?? 'desc'));
        $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : 'desc';
        $status = $options['status'] ?? 'published';
        $wheres = $options['wheres'] ?? [];
        $websiteId = $options['website_id'] ?? null;
        $excludedPostIds = $options['excluded_post_ids'] ?? [];
        $excludedTagIds = $options['excluded_tag_ids'] ?? [];
        $ids = $options['ids'] ?? [];
        $select = $options['select'] ?? ['*'];
        $withs = $options['withs'] ?? [];
        $isRandom = $options['is_random'] ?? ($sort === 'random');
        $userId = $options['user_id'] ?? null;

        // $pageSize = $options['page_size'] ?? 0;
        // $pageName = $options['page_name'] ?? 'page';

        $this->applyWebsiteId($q, $websiteId);
        $this->applyUserId($q, $userId);

        $this->applyWiths($q, array_merge(['media', 'comments', 'tags', 'translations'], $withs));
        $this->applyTagFilter($q, $tags, $tagType);
        $this->applyKeywordFilter($q, $keywords, $searchFields);

        $this->applyWhereConditions($q, $wheres);
        $this->applyIds($q, 'posts.id', $ids);
        $this->applyExcludeIds($q, 'posts.id', $excludedPostIds);
        if (is_string($select)) {
            $select = array_filter(array_map('trim', explode(',', $select)));
        }
        $select = $this->autoAddOrderByColumnsToSelect($q, $select);
        $this->applySelect($q, $select);
        $this->applyOffset($q, $offset);
        $this->applyLimit($q, $count);

        $this->applyExcludedTags($q, $excludedTagIds);

        $this->applyStatus($q, 'status', $status);
        $this->applyOrdering($q, $sort, $direction, $isRandom);

        $q->withCount('comments');
        if (!isset($options['select'])) {
            $q->distinct();
        }

        return $q;
    }

    protected function normalizeSortColumn(string $sort): string
    {
        $sort = trim(str_replace('posts.', '', $sort));
        if ($sort === '') {
            return 'id';
        }

        if ($sort === 'random') {
            return $sort;
        }

        if (!preg_match('/^[A-Za-z0-9_]+$/', $sort)) {
            return 'id';
        }

        return $sort;
    }
}
