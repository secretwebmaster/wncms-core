<?php

namespace Wncms\Models;

use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Wncms\Tags\Tag as WncmsTag;

//TODO: Pending merge to HasTags
class Tag extends WncmsTag implements HasMedia
{
    use InteractsWithMedia;

    /**
     * ----------------------------------------------------------------------------------------------------
     * Propertyies
     * ----------------------------------------------------------------------------------------------------
     */
    public static $modelKey = 'tag';

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-tag'
    ];

    public const SUBTYPES = [
        'category',
        'tag',
    ];

    public const SORTS = [
        'sort',
        'created_at',
        'updated_at',
    ];

    /**
     * ----------------------------------------------------------------------------------------------------
     * Contracts
     * ----------------------------------------------------------------------------------------------------
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('tag_thumbnail')->singleFile();
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Relationships
     * ----------------------------------------------------------------------------------------------------
     */
    // public function posts()
    // {
    //     return $this->morphedByMany(wncms()->getModelClass('post'), 'taggable');
    // }
    
    public function keywords()
    {
        return $this->hasMany(wncms()->getModelClass('tag_keyword'));
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Attributes Accessor
     * ----------------------------------------------------------------------------------------------------
     */
    /**
     * Get URL using TagManager and tag meta.
     */
    public function getUrlAttribute(): ?string
    {
        try {
            return wncms()->tag()->getUrl($this) ?: null;
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
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
}
