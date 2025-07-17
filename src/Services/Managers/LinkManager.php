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
     * - order: string - Column to sort by (default: 'order').
     * - sequence: string - Sort direction ('asc' or 'desc', default: 'desc').
     * - select: array|string - Columns to select (default: ['*']).
     * - offset: int - Query offset for pagination or batching.
     * - count: int - Limit the number of results (0 means no limit).
     *
     * Additional behavior:
     * - If 'order' is 'random', results will be returned in random order.
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

        $this->applyIds($q, 'links.id', $options['ids'] ?? []);
        $this->applyExcludeIds($q, 'links.id', $options['excluded_ids'] ?? []);
        $this->applyExcludedTags($q, $options['excluded_tag_ids'] ?? []);
        $this->applyTagFilter($q, $options['tags'] ?? [], $options['tag_type'] ?? null);
        $this->applyKeywordFilter($q, $options['keywords'] ?? [], ['name']);
        $this->applyWhereConditions($q, $options['wheres'] ?? []);
        $this->applyStatus($q, 'status', $options['status'] ?? 'active');
        $this->applyWiths($q, $options['withs'] ?? []);
        $this->applyOrdering($q, $options['order'] ?? 'order', $options['sequence'] ?? 'desc', ($options['order'] ?? '') === 'random');

        $select = $options['select'] ?? ['links.*'];

        // Auto-add any orderBy columns into select
        $select = $this->autoAddOrderByColumnsToSelect($q, $select);

        // Optional: if sorting by total_views_yesterday, make sure to explicitly join tv_y
        if (($options['order'] ?? null) === 'total_views_yesterday' && !in_array('tv_y.total', $select)) {
            $select[] = 'tv_y.total';
        }

        $this->applySelect($q, $select);

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

    protected function applyOrdering(Builder $q, string $order, string $sequence = 'desc', bool $isRandom = false)
    {
        if ($isRandom) {
            $q->inRandomOrder();
            return;
        }
    
        if ($order === 'total_views_yesterday') {

            // info("pinned");
            $q->orderBy('links.is_pinned', 'desc');

            info("triggered yesterday");
            $yesterday = now()->subDay()->toDateString();
    
            $q->leftJoin('total_views as tv_y', function ($join) use ($yesterday) {
                $join->on('links.id', '=', 'tv_y.link_id')
                    ->where('tv_y.date', $yesterday);
            });
    
            $q->orderBy('tv_y.total', in_array($sequence, ['asc', 'desc']) ? $sequence : 'desc');
            $q->orderBy('links.id', 'desc');
            return;
        }
    
        // 💡 Final fallback: just use requested column but preserve pinned
        if (!in_array($order, ['is_pinned', 'total_views_yesterday', 'random'])) {
            $q->orderBy("links.{$order}", in_array($sequence, ['asc', 'desc']) ? $sequence : 'desc');
            $q->orderBy('links.id', 'desc');
            return;
        }
    }
    

}