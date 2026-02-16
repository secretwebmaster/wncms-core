<?php

namespace Wncms\Services\Managers;

use Illuminate\Database\Eloquent\Builder;


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
        // info($options);
        return parent::getList($options);
    }

    /**
     * Build the base query for retrieving a list of links.
     *
     * Supported $options keys:
     * - ids: array|string|int - Include only specific link IDs.
     * - excluded_ids: array|string|int - Exclude specific link IDs.
     * - excluded_tag_ids: array|string|int - Exclude links with these tag IDs.
     * - tags: array|string|int - Filter by tags (used with tag_type).
     * - tag_type: string|null - The type of tag used for filtering (e.g., 'link_category', 'link_tag').
     * - keywords: array|string - Filter links by keywords (applies to the 'title' field).
     * - wheres: array - Additional raw where conditions.
     * - status: string|null - Filter by link status (e.g., 'active', 'draft').
     * - withs: array - Eloquent relationships to eager load.
     * - sort: string - Column to sort by (default: 'sort').
     * - direction: string - Sort direction ('asc' or 'desc', default: 'desc').
     * - select: array|string - Columns to select (default: ['*']).
     * - offset: int - Query offset for pagination or batching.
     * - count: int - Limit the number of results (0 means no limit).
     *
     * Additional behavior:
     * - If 'sort' is 'random', results will be returned in random order.
     * - Automatically eager loads 'media' relation and removes duplicate rows using DISTINCT.
     *
     * @param array $options
     * @return mixed
     */

    protected function buildListQuery(array $options): mixed
    {
        // info("In buildListQuery");
        // info($options);
        $q = $this->query();
        $sort = $this->normalizeSortColumn((string) ($options['sort'] ?? 'sort'));
        $direction = strtolower((string) ($options['direction'] ?? 'desc'));
        $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : 'desc';
        $isRandom = (bool) ($options['is_random'] ?? false) || $sort === 'random';

        $this->applyIds($q, 'links.id', $options['ids'] ?? []);
        $this->applyExcludeIds($q, 'links.id', $options['excluded_ids'] ?? []);
        $this->applyExcludedTags($q, $options['excluded_tag_ids'] ?? []);
        $this->applyTagFilter($q, $options['tags'] ?? [], $options['tag_type'] ?? null);
        $this->applyKeywordFilter($q, $options['keywords'] ?? [], ['name']);
        $this->applyWhereConditions($q, $options['wheres'] ?? []);
        $this->applyStatus($q, 'status', $options['status'] ?? 'active');
        $this->applyWiths($q, $options['withs'] ?? []);
        $this->applyOrdering($q, $sort, $direction, $isRandom);
        $this->applyWebsiteId($q, $options['website_id'] ?? null);

        $select = $options['select'] ?? ['links.*'];
        if (is_string($select)) {
            $select = array_filter(array_map('trim', explode(',', $select)));
        }

        // Auto-add any orderBy columns into select
        $select = $this->autoAddOrderByColumnsToSelect($q, $select);

        // Temporarily disabled total_views_yesterday select injection until wn_total_views is available.

        $this->applySelect($q, $select);

        $this->applyOffset($q, $options['offset'] ?? 0);
        $this->applyLimit($q, $options['count'] ?? 0);

        $q->with('media');
        $q->distinct();

        return $q;
    }

    public function getBySlug(string $slug, ?int $websiteId = null)
    {
        return $this->getList([
            'wheres' => [['slug', $slug]],
            'website_id' => $websiteId,
        ])?->first();
    }

    protected function applyOrdering(Builder $q, string $sort, string $direction = 'desc', bool $isRandom = false)
    {
        if ($isRandom) {
            $q->inRandomOrder();
            return;
        }

        if ($sort === 'total_views_yesterday') {
            // Temporarily disable yesterday-views ordering to avoid SQL errors when wn_total_views is missing.
            $q->orderBy('links.sort', in_array($direction, ['asc', 'desc'], true) ? $direction : 'desc');
            $q->orderBy('links.id', 'desc');
            return;
        }

        $q->orderBy("links.{$sort}", in_array($direction, ['asc', 'desc'], true) ? $direction : 'desc');
        $q->orderBy('links.id', 'desc');
    }

    protected function normalizeSortColumn(string $sort): string
    {
        $sort = trim(str_replace('links.', '', $sort));
        if ($sort === '') {
            return 'sort';
        }

        if (in_array($sort, ['random', 'total_views_yesterday'], true)) {
            return $sort;
        }

        if (!preg_match('/^[A-Za-z0-9_]+$/', $sort)) {
            return 'sort';
        }

        return $sort;
    }
}
