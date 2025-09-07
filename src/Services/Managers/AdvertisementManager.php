<?php

namespace Wncms\Services\Managers;

use Illuminate\Database\Eloquent\Model;

class AdvertisementManager extends ModelManager
{
    protected string $cacheKeyPrefix = 'wncms_advertisement';
    protected string|array $cacheTags = ['advertisements'];
    protected bool $shouldAuth = false;

    public function getModelClass(): string
    {
        return wncms()->getModelClass('advertisement');
    }

    public function get(array $options = []): ?Model
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

        // Website scope
        if (!empty($options['website_id'])) {
            $q = $this->getWebsiteQuery('advertisements', $options['website_id']);
        }

        // Status (default active)
        $this->applyStatus($q, 'status', $options['status'] ?? 'active');

        // Expired handling
        $includeExpired = $options['include_expired'] ?? false;
        $excludeExpired = $options['exclude_expired'] ?? true;

        if ($excludeExpired) {
            $q->where(function ($subq) {
                $subq->whereNull('expired_at')
                    ->orWhere('expired_at', '>=', now());
            });
        }

        if (!$includeExpired) {
            $q->where(
                fn($subq) =>
                $subq->whereDate('expired_at', '>=', now())
                    ->orWhereNull('expired_at')
            );
        }

        // Positions filter
        if (!empty($options['positions'])) {
            $positions = is_string($options['positions'])
                ? explode(',', $options['positions'])
                : (array) $options['positions'];

            $q->whereIn('position', $positions);
        }



        // Generic filters
        $this->applyIds($q, 'advertisements.id', $options['ids'] ?? []);
        $this->applyExcludeIds($q, 'advertisements.id', $options['excluded_ids'] ?? []);
        $this->applyKeywordFilter($q, $options['keywords'] ?? [], ['name', 'remark', 'cta_text', 'url']);
        $this->applyWhereConditions($q, $options['wheres'] ?? []);
        $this->applyWiths($q, array_merge(['media'], $options['withs'] ?? []));
        $this->applyOrdering(
            $q,
            $options['order'] ?? 'order',
            $options['sequence'] ?? 'desc',
            ($options['order'] ?? '') === 'random'
        );


        $this->applySelect($q, $options['select'] ?? ['*']);
        $this->applyOffset($q, $options['offset'] ?? 0);
        $this->applyLimit($q, $options['count'] ?? 0);
        $q->distinct();

        return $q;
    }

    public function getByPosition(string|array $positions, ?int $websiteId = null)
    {
        return $this->getList([
            'positions' => $positions,
            'website_id' => $websiteId,
            'count' => 1,
        ])?->first();
    }
}
