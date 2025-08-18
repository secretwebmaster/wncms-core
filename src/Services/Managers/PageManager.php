<?php

namespace Wncms\Services\Managers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class PageManager extends ModelManager
{
    protected string $cacheKeyPrefix = 'wncms_page';
    protected bool $shouldAuth = false;
    protected string|array $cacheTags = ['pages'];

    protected array $defaultWiths = ['comments', 'media', 'translations'];

    public function getModelClass(): string
    {
        return wncms()->getModelClass('page');
    }

    public function get(array $options = []): ?Model
    {
        $options['withs'] = array_merge($this->defaultWiths, $options['withs'] ?? []);
        return parent::get($options);
    }

    public function getList(array $options = []): Collection|LengthAwarePaginator
    {
        $options['withs'] = array_merge($this->defaultWiths, $options['withs'] ?? []);
        return parent::getList($options);
    }

    protected function buildListQuery(array $options): mixed
    {
        $q = $this->query();

        $keywords = $options['keywords'] ?? [];
        $count = $options['count'] ?? 0;
        $offset = $options['offset'] ?? 0;
        $order = $options['order'] ?? 'id';
        $sequence = $options['sequence'] ?? 'desc';
        $status = $options['status'] ?? 'published';
        $wheres = $options['wheres'] ?? [];
        $websiteId = $options['website_id'] ?? null;
        $excludedPageIds = $options['excluded_page_ids'] ?? [];
        $ids = $options['ids'] ?? [];
        $select = $options['select'] ?? ['*'];
        $withs = $options['withs'] ?? [];
        $isRandom = $options['is_random'] ?? false;

        $this->applyWebsiteId($q, $websiteId);
        $this->applyWiths($q, array_merge($this->defaultWiths, $withs));
        $this->applyKeywordFilter($q, $keywords, ['title', 'slug', 'content', 'remark']);
        $this->applyWhereConditions($q, $wheres);
        $this->applyIds($q, 'pages.id', $ids);
        $this->applyExcludeIds($q, 'pages.id', $excludedPageIds);
        $this->applySelect($q, $select);
        $this->applyOffset($q, $offset);
        $this->applyLimit($q, $count);
        $this->applyStatus($q, 'status', $status);
        $this->applyOrdering($q, $order, $sequence, $isRandom);

        $q->withCount('comments');

        if (!isset($options['select'])) {
            $q->distinct();
        }

        return $q;
    }

    //% Alpha function
    public function createDefaultThemeTemplatePages($website, $skipIfExists = true)
    {
        //get available pages nad names
        $templates = collect(config("theme.{$website->theme}.templates"));
        $templateBladeNames = $templates->pluck('blade_name')->toArray();

        //skip already exist
        if (!empty($skipIfExists)) {
            $existingBladeNames = $website->pages()->pluck('blade_name')->toArray();
            $bladeNamesToCreate = array_diff($templateBladeNames, $existingBladeNames);
        } else {
            $bladeNamesToCreate = $templateBladeNames;
        }

        // dd(
        //     $templates,
        //     $templateBladeNames,
        //     $existingBladeNames,
        //     $bladeNamesToCreate,
        // );

        //create page
        $count = 0;
        foreach ($bladeNamesToCreate as $bladeName) {
            $template = $templates->where('blade_name', $bladeName)->first();

            if ($template && view()->exists("frontend.theme.{$website->theme}.pages.{$bladeName}")) {
                $page = $website->pages()->create([
                    'user_id' => auth()->id(),
                    'title' => $template['title'] ?? 'untitled',
                    'slug' => $template['slug'],
                    'blade_name' => $template['blade_name'],
                    'status' => 'drafted',
                    'visibility' => 'public',
                    'type' => 'template',
                ]);

                $count++;
            } else {
                dd('page not exist');
            }
        }

        return $count;
    }
}
