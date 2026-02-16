<?php

namespace Wncms\Services\Managers;

class StarterManager extends ModelManager
{
    protected string $cacheKeyPrefix = 'wncms_starter';
    protected string|array $cacheTags = ['starters'];
    protected bool $shouldAuth = false;
    protected string $defaultTagType = 'starter_category';

    public function getModelClass(): string
    {
        return wncms()->getModelClass('starter');
    }

    protected function buildListQuery(array $options): mixed
    {
        $q = $this->query();

        $this->applyStatus($q, 'status', $options['status'] ?? 'active');
        $this->applyTagFilter($q, $options['tags'] ?? [], $options['tag_type'] ?? $this->defaultTagType);
        $this->applyKeywordFilter($q, $options['keywords'] ?? [], $options['search_fields'] ?? ['title', 'name']);
        $this->applyWhereConditions($q, $options['wheres'] ?? []);
        $this->applyWebsiteId($q, $options['website_id'] ?? null);
        $this->applyIds($q, 'id', $options['ids'] ?? []);
        $this->applyExcludeIds($q, 'id', $options['excluded_ids'] ?? []);
        $this->applyWiths($q, $options['withs'] ?? []);
        $this->applySelect($q, $options['select'] ?? ['*']);
        $this->applyOffset($q, $options['offset'] ?? 0);
        $this->applyLimit($q, $options['count'] ?? 0);
        $this->applyOrdering(
            $q,
            $options['sort'] ?? 'id',
            $options['direction'] ?? 'desc',
            $options['is_random'] ?? false
        );

        return $q;
    }
}
