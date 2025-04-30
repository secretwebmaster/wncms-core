<?php

namespace Wncms\Models;

use Wncms\Traits\WnModelTrait;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Wncms\Tags\Tag as WncmsTag;

//TODO: Pending merge to HasTags
class Tag extends WncmsTag implements HasMedia
{
    use InteractsWithMedia;
    use WnModelTrait;

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-tag'
    ];

    public const SUBTYPES = [
        'category',
        'tag',
    ];

    public const ORDERS = [
        'order_column',
        'created_at',
        'updated_at',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('tag_thumbnail')->singleFile();
    }
    
    public function advertisement()
    {
        return $this->morphedByMany(Advertisement::class, 'taggable');
    }
    
    public function posts()
    {
        return $this->morphedByMany(Post::class, 'taggable');
    }

    public function templates()
    {
        return $this->morphedByMany(Template::class, 'taggable');
    }

    public function keywords()
    {
        return $this->hasMany(TagKeyword::class);
    }
    

    /**
     * ----------------------------------------------------------------------------------------------------
     * ! Attributes Accessor
     * ----------------------------------------------------------------------------------------------------
     */
    public function getUrlAttribute(): ?string
    {
        try {
            $parts = explode('_', $this->type);
            if (count($parts) !== 2) return null;

            [$model, $subType] = $parts;

            $model = str()->plural($model);

            $routeName = "frontend.{$model}.{$subType}";

            if (!\Illuminate\Support\Facades\Route::has($routeName)) return null;

            return route($routeName, ['tagName' => $this->name]);
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    public function getPostCategoryUrlAttribute()
    {
        return route('frontend.posts.category', ['tagName' => $this->name ]);
    }
    public function getPostTagUrlAttribute()
    {
        return route('frontend.posts.tag', ['tagName' => $this->name ]);
    }

    public function getThumbnailAttribute()
    {
        $media = $this->getMedia('tag_thumbnail')->first();
        if ($media) return $media->getUrl();
    }

    public function getBackgroundAttribute()
    {
        $media = $this->getMedia('tag_background')->first();
        if ($media) return $media->getUrl();
    }

    public function getAllDescendants()
    {
        $descendants = collect();

        // Get direct children
        $children = $this->children;

        foreach ($children as $child) {
            $descendants->push($child);
            // Recursively get descendants of each child
            $descendants = $descendants->merge($child->getAllDescendants());
        }

        return $descendants;
    }

    // Get all descendants and self
    public function getAllDescendantsAndSelf()
    {
        $descendantsAndSelf = collect([$this]); // Start with the tag itself

        // Get direct children
        $children = $this->children;

        foreach ($children as $child) {
            $descendantsAndSelf = $descendantsAndSelf->merge($child->getAllDescendantsAndSelf());
        }

        return $descendantsAndSelf;
    }


    /**
     * ----------------------------------------------------------------------------------------------------
     * !Query
     * ----------------------------------------------------------------------------------------------------
     */
    public function getPostList(array $options = [])
    {
        $options['tags'] = $this->name;
        $options['tag_type'] = $this->type;
    
        return wncms()->post()->getList($options);
    }
    

    


    /**
     * ----------------------------------------------------------------------------------------------------
     * ! Static functions
     * ----------------------------------------------------------------------------------------------------
     */
    public static function getAvailableTypes($model = null)
    {
        return self::select('type')
        ->whereIn('id', function ($q) use($model){
            $q->select('tag_id')->from('taggables');
            if($model){
                $q->where('taggable_type', 'Wncms\Models\\' . str()->studly($model));
            }
        })
        ->distinct()
        ->pluck('type')
        ->toArray();
    }

}