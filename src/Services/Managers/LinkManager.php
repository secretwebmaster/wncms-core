<?php

namespace Wncms\Services\Managers;


class LinkManager extends ModelManager
{
    protected string $cacheKeyPrefix = 'wncms_link';
    protected string $defaultTagType = 'link_category';
    protected bool $shouldAuth = false;
    protected string|array $cacheTags = ['links'];

    public function getModelClass(): string
    {
        return wncms()->getModelClass('link');
    }

    public function get(array $options = []): ?\Illuminate\Database\Eloquent\Model
    {
        return parent::get($options);
    }

    public function getList(array $options = []): mixed
    {
        return parent::getList($options);
    }

    protected function buildListQuery(array $options): mixed
    {
        $q = $this->query();

        $this->applyIds($q, 'links.id', $options['ids'] ?? []);
        $this->applyExcludeIds($q, 'links.id', $options['excluded_ids'] ?? []);
        $this->applyExcludedTags($q, $options['excluded_tag_ids'] ?? []);
        $this->applyTagFilter($q, $options['tags'] ?? [], $options['tag_type'] ?? null);
        $this->applyKeywordFilter($q, $options['keywords'] ?? [], ['title']);
        $this->applyWhereConditions($q, $options['wheres'] ?? []);
        $this->applyStatus($q, 'status', $options['status'] ?? 'active');
        $this->applyWiths($q, $options['withs'] ?? []);
        $this->applyOrdering($q, $options['order'] ?? 'order', $options['sequence'] ?? 'desc', ($options['order'] ?? '') === 'random');
        $this->applySelect($q, $options['select'] ?? ['*']);
        $this->applyOffset($q, $options['offset'] ?? 0);
        $this->applyLimit($q, $options['count'] ?? 0);

        $q->with('media');
        $q->distinct();

        return $this->finalizeResult($q, $options);
    }

    public function getBySlug(string $slug, ?int $websiteId = null)
    {
        return $this->getList([
            'wheres' => [['slug', $slug]],
            'website_id' => $websiteId,
        ])?->first();
    }
}