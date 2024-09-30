<?php

namespace Wncms\Services\Managers;

use Wncms\Models\Tag;
use Illuminate\Support\Facades\File;
use LaravelLocalization;
use Str;

class TagManager
{
    //Cache key prefix that prepend all cache key in this page
    protected $cacheKeyPrefix = "wncms_tag";

    public function get(int $tagId)
    {
        //prepare cache key and tag
        $method = "get";
        $shouldAuth = false;
        $cacheKey = wncms()->cache()->createKey($this->cacheKeyPrefix, $method, $shouldAuth, wncms()->getAllArgs(__METHOD__, func_get_args()));
        $cacheTags = ['tags'];
        $cacheTime = gss('enable_cache') ? gss('data_cache_time') : 0;
        // wncms()->cache()->clear($cacheKey, $cacheTags);

        return wncms()->cache()->tags($cacheTags)->remember($cacheKey, $cacheTime, function () use ($tagId) {
            // info('no cache from TagManager get()');
            return Tag::find($tagId);
        });
    }

    public function getByName(string $tagName, string $tagType = null, string $locale = null, $withs = [], int $websiteId = null)
    {
        $method = "getByName";
        $shouldAuth = false;
        $cacheKeyDomain = empty($websiteId) ? wncms()->getDomain() : '';
        $cacheKey = wncms()->cache()->createKey($this->cacheKeyPrefix, $method, $shouldAuth, wncms()->getAllArgs(__METHOD__, func_get_args()), $cacheKeyDomain);
        $cacheTags = ['tags'];
        $cacheTime = gss('enable_cache') ? gss('data_cache_time') : 0;
        // wncms()->cache()->clear($cacheKey, $cacheTags);

        return wncms()->cache()->tags($cacheTags)->remember($cacheKey, $cacheTime, function () use ($tagName, $tagType, $locale, $withs, $websiteId) {
            // info('no cache from TagManager getByName()');

            $locale ??= LaravelLocalization::getCurrentLocale();
            $website = wncms()->website()->get($websiteId);

            $q = Tag::query();

            $q->where('type', $tagType);

            $q->where(function($subq) use ($tagName, $locale) {
                $subq->where("name", $tagName)
                    ->orWhere("slug", $tagName)
                    ->orWhereHas('translations', function($subsubq) use ($tagName, $locale){
                        $subsubq->where('field', 'name')->where('value', $tagName)->where('locale', $locale);
                    });
            });

            if(!empty($withs)){
                $q->with($withs);

                if(!in_array("psots", $withs)){
                    $q->with(['posts' => function($subq) use($website){
                        $subq->whereHas('websites', function($subsubq) use($website){
                            $subsubq->where('websites.id', $website->id);
                        });
                    }]);
                }

                // If 'children' is included in 'withs', also eager load posts for child tags
                if (in_array('children', $withs)) {
                    $q->with(['children' => function ($subq) {
                        $subq->with(['posts']);
                    }]);
                }
            }

            return $q->first();
        });
    }

    public function getList(
        string $tagType = 'post_category',
        ?int $count = 0,
        ?int $pageSize = 0,
        ?int $page = 0,
        array|string|null $tagIds = null,
        array $withs = [],
        bool $hasModels = false,
        string $modelType = 'posts',
        bool $onlyCurrentWebsite = false,
        string|null $locale = null,
        bool $isRandom = false,
        bool $parentOnly = false,
        string $order = 'order_column',
        string $sequence = 'desc',
    )
    {
        $method = "getList";
        $shouldAuth = false;
        $websiteId = wncms()->website()->getCurrent()?->id;
        $page = request()->page;
        $cacheKey = wncms()->cache()->createKey($this->cacheKeyPrefix, $method, $shouldAuth, wncms()->getAllArgs(__METHOD__, func_get_args()), $websiteId);
        $cacheTags = ['tags'];
        if($isRandom){
            $cacheTime = 0;
        }else{
            $cacheTime = gss('enable_cache') ? gss('data_cache_time') : 0;
        }
        // wncms()->cache()->clear($cacheKey, $cacheTags);

        return wncms()->cache()->tags($cacheTags)->remember($cacheKey, $cacheTime, function () use ($tagType, $count, $pageSize, $tagIds, $withs, $hasModels, $modelType, $onlyCurrentWebsite, $websiteId, $locale, $isRandom, $parentOnly, $order, $sequence) {
            // info('no cache from TagManager getList()');
    
            $q = Tag::query();

            $q->where('type', $tagType);

            $locale ??= LaravelLocalization::getCurrentLocale();

            if (!empty($tagIds)) {

                if (is_string($tagIds)) {
                    $tagIds = explode(",", $tagIds);
                }

                $q->where(function ($subq) use ($tagIds, $locale) {
                    // $subq->orWhereIn('tags.id', $tagIds);
                    $subq->orWhere(function ($subsubq) use ($locale, $tagIds) {
                        $subsubq->whereIn("tags.name", $tagIds)
                            ->orWhereIn("tags.slug", $tagIds)
                            ->orWhereHas('translations', function($subsubsubq) use ($locale, $tagIds){
                                $subsubsubq->where('field', 'name')->where('locale', $locale)->whereIn('value', $tagIds);
                            })
                            ->orWhereIn('tags.id', $tagIds);
                    });
                });
            }

            if($hasModels && !empty($modelType)){
                if($onlyCurrentWebsite){
                   $q->whereHas($modelType, function($subq) use($websiteId){
                    $subq->whereRelation('websites','websites.id',$websiteId);
                   });
                }else{
                    $q->whereHas($modelTypes);
                }
            }

            foreach ($withs as $with) {
                $q->with($with);
            }

            if($isRandom){
                $q->inRandomOrder();
            }

            if($parentOnly){
                $q->whereNull('parent_id');
            }

            if ($count) {
                $q->limit($count);
            }

            if(in_array($order, Tag::ORDERS) && in_array($sequence, ['asc', 'desc'])){
                $q->orderBy($order, $sequence);
            }

            if($pageSize){
                $tags = $q->paginate($pageSize);
            }else{
                $tags = $q->get();
            }
            
            return $tags;
        });
    }

    public function getArray(string $tagType = 'post_category', int $count = 0, string $columnName = 'name', string $keyName = null, array|string|null $tagIds = null)
    {
        //prepare cache key and tag
        $method = "getArray";
        $shouldAuth = false;
        $cacheKey = wncms()->cache()->createKey($this->cacheKeyPrefix, $method, $shouldAuth, wncms()->getAllArgs(__METHOD__, func_get_args()));
        $cacheTags = ['tags'];
        $cacheTime = gss('enable_cache') ? gss('data_cache_time') : 0;
        //wncms()->cache()->clear($cacheKey, $cacheTags);

        return wncms()->cache()->tags($cacheTags)->remember($cacheKey, $cacheTime, function () use ($tagType, $count, $columnName, $keyName, $tagIds) {
            // info('no cache from TagManager getArray()');
            $q = Tag::query();
            $q->where('type', $tagType);

            if ($count) {
                $q->limit($count);
            }

            if (!empty($tagIds)) {
                if (is_string($tagIds)) {
                    $tagIds = explode(',', $tagIds);
                }
                $q->whereIn('id', $tagIds);
            }

            $array = [];

            $q->get()->map(function($tag) use($columnName, $keyName, &$array) {
                // If $keyName is null, use the array's current count as the index
                if ($keyName) {
                    $array[$tag->{$keyName}] = $tag->{$columnName};
                } else {
                    $array[] = $tag->{$columnName}; // Automatically assigns an integer index
                }
            });
            
            return $array;

            // return $q->get()->map(function($tag) use($columnName, $keyName){
            //     return [$keyName => $tag->{$columnName}];
            // });

            // $tagArray = $q->pluck($columnName, $keyName)->toArray();
            // return $tagArray;
        });
    }

    public function getTypes(array|string|null $tagIds = null)
    {
        //prepare cache key and tag
        $method = "getTypes";
        $shouldAuth = false;
        $cacheKey = wncms()->cache()->createKey($this->cacheKeyPrefix, $method, $shouldAuth, wncms()->getAllArgs(__METHOD__, func_get_args()));
        $cacheTags = ['tags'];
        $cacheTime = gss('enable_cache') ? gss('data_cache_time') : 0;
        //wncms()->cache()->clear($cacheKey, $cacheTags);

        return wncms()->cache()->tags($cacheTags)->remember($cacheKey, $cacheTime, function () use ($tagIds) {

            // info('no cache from TagManager getTypes()');
            $q = Tag::query();

            if (!empty($tagIds)) {
                $q->whereIn('id', $tagIds);
            }

            $tagTypes = $q->distinct()->pluck('type')->toArray();

            return $tagTypes;
        });
    }

    public function getTagKeywordList(string $tagType = 'post_category', $column = 'id')
    {
        //get all tags and keywords into array
        $tags = $this->getList(
            tagType: $tagType,
            withs: ['keywords'],
        );

        $availableTagKeywords = [];
        foreach ($tags as $tag) {
            foreach ($tag->keywords as $keyword) {
                $availableTagKeywords[$tag->{$column}][] = $keyword->name;
            }
        }

        return $availableTagKeywords;
    }

    public function getTagsToBind(string $tagType = 'post_category', array $contents = [], $column = 'id')
    {
        $availableTagKeywords = $this->getTagKeywordList($tagType, $column);

        //prepare ids array
        $tagKeysToBind = [];

        //foreach data
        foreach ($availableTagKeywords as $tagKey => $keywords) {
            foreach ($contents as $content) {

                // Check if any of the keywords in $keywords are present in $content
                foreach ($keywords as $keyword) {
                    if (stripos($content, $keyword) !== false) {
                        // If a keyword is found in $content, add the $tagKey to $tagKeys
                        $tagKeysToBind[] = $tagKey;
                    }
                }
            }
        }

        $tagKeysToBind = array_unique($tagKeysToBind);

        return $tagKeysToBind;
    }

    public function getModelsWithHasTagsTraits()
    {
        // dd($request->all());
        $modelsWithHasTagsTraits = collect(File::allFiles(app_path('Models')))
        ->map(function ($file) {
            //$class = 'Wncms\\Models\\' . Str::replace('.php', '', $file->getBasename());
            //return app($class);

            $relativePath = Str::replaceFirst(app_path('Models') . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $class = 'Wncms\\Models\\' . Str::replace('.php', '', str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath));
            return class_exists($class) ? app($class) : null;

        })
        ->filter(function ($model) {
            $reflection = new \ReflectionClass($model);
            return in_array("Wncms\Tags\HasTags", $reflection->getTraitNames());
        });

        $tagTypes = [];
        $index = 0;
        foreach($modelsWithHasTagsTraits as $modelsWithHasTagsTrait){
            foreach(Tag::SUBTYPES as $subType){
            $tagTypes[$index]['slug'] = str()->singular($modelsWithHasTagsTrait->getTable()) . "_" . $subType;
            $tagTypes[$index]['name'] = $this->getWord(str()->singular($modelsWithHasTagsTrait->getTable()), $subType);
            $index++;
            }
        }
        return $tagTypes;
    }

    public function getWord(string $modelName = 'post', $subType = 'category')
    {
        if(strpos($subType, $modelName . "_") !== false){
            $subType = str_replace($modelName . "_", "",$subType);
        }
        return __('word.'.$modelName) . __('word.word_separator') . __('word.' . $subType);
    }

    public function getTagifyDropdownItems($type, $nameColumn = 'name', $valueColumn = null)
    {
        return Tag::where('type', $type)->get()->map(function($tag) use($nameColumn, $valueColumn){
            return [
                'name' => $tag->{$nameColumn},
                'value' => $tag->{$valueColumn ?? $nameColumn}
            ];
        })->toArray();
    }
}
