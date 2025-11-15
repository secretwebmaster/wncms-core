<?php

namespace Wncms\Models;

use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Wncms\Tags\Tag as WncmsTag;
use Illuminate\Support\Facades\Route;

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
    public function posts()
    {
        return $this->morphedByMany(wncms()->getModelClass('post'), 'taggable');
    }

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
            if (! function_exists('wncms')) return null;
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

    /**
     * Generate URL for a tag based on its type (key) and meta.
     *
     * @param  \Wncms\Models\Tag|\Wncms\Tags\Tag  $tag
     */
    public function getUrl($tag): string
    {
        if (empty($tag) || empty($tag->type)) {
            return 'javascript:;';
        }

        $entry = $this->getTagMetaByKey($tag->type);
        if (! $entry) {
            return 'javascript:;';
        }

        $meta      = $entry['meta'];
        $routeName = $meta['route'] ?? null;
        $short     = $meta['short'] ?? null;

        if (! $routeName || ! function_exists('wncms_route_exists') || ! wncms_route_exists($routeName)) {
            return 'javascript:;';
        }

        // Common parameter pattern; you can adjust if needed
        return route($routeName, [
            'type' => $short,
            'slug' => $tag->slug,
        ]);
    }
}
