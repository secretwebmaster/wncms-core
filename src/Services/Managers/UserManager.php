<?php

namespace Wncms\Services\Managers;

use Wncms\Models\User;
use Illuminate\Support\Facades\App;

class UserManager extends ModelManager
{

    //Cache key prefix that prepend all cache key in this page
    protected string $cacheKeyPrefix = "wncms_user";
    protected string|array $cacheTags = ['users'];

    public function getModelClass(): string
    {
        return wncms()->getModelClass('user');
    }

    protected function buildListQuery(array $options): mixed
    {
        $q = $this->query();

        $keywords = $options['keywords'] ?? [];
        $count = $options['count'] ?? 0;
        $offset = $options['offset'] ?? 0;
        $order = $options['order'] ?? 'id';
        $sequence = $options['sequence'] ?? 'desc';
        $wheres = $options['wheres'] ?? [];
        $websiteId = $options['website_id'] ?? null;
        $excludedPostIds = $options['excluded_user_ids'] ?? [];
        $ids = $options['ids'] ?? [];
        $select = $options['select'] ?? ['*'];
        $withs = $options['withs'] ?? [];
        $isRandom = $options['is_random'] ?? false;

        // Scope by website
        if (gss('multi_website') && $websiteId !== false) {
            try {
                $q = $this->getWebsiteQuery('users', $websiteId);
            } catch (\Throwable $e) {
                logger()->warning("Website relation error: " . $e->getMessage());
                return $q->whereRaw('1=0');
            }
        }

        $this->applyWiths($q, $withs);
        $this->applyKeywordFilter($q, $keywords, ['first_name', 'last_name', 'nickname', 'username', 'email']);
        $this->applyWhereConditions($q, $wheres);
        $this->applyIds($q, 'users.id', $ids);
        $this->applyExcludeIds($q, 'users.id', $excludedPostIds);
        $this->applySelect($q, $select);
        $this->applyOffset($q, $offset);
        $this->applyLimit($q, $count);
        $this->applyOrdering($q, $order, $sequence, $isRandom);
        if (!isset($options['select'])) {
            $q->distinct();
        }
        return $q;
    }
}
