<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Wncms\Models\BaseModel;
use Wncms\Translatable\Traits\HasTranslations;

class Link extends BaseModel implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use HasTranslations;

    /**
     * ----------------------------------------------------------------------------------------------------
     * Propertyies
     * ----------------------------------------------------------------------------------------------------
     */
    public static $modelKey = 'link';

    protected $guarded = [];

    protected $translatable = ['name', 'description', 'slogan'];

    protected $casts = [
        'expired_at' => 'datetime',
        'hit_at' => 'datetime',
    ];

    protected static array $tagMetas = [
        [
            'key'   => 'link_category',
            'short' => 'category',
            'route' => '',
        ],
        [
            'key'   => 'link_tag',
            'short' => 'tag',
            'route' => '',
        ],
    ];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-link'
    ];

    public const STATUSES = [
        'active',
        'inactive',
    ];

    /**
     * ----------------------------------------------------------------------------------------------------
     * Contracts
     * ----------------------------------------------------------------------------------------------------
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('link_thumbnail')->singleFile();
        $this->addMediaCollection('link_icon')->singleFile();
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * Attributes Accessor
     * ----------------------------------------------------------------------------------------------------
     */
    public function getThumbnailAttribute()
    {
        $media = $this->getMedia('link_thumbnail')->first();
        if ($media) return $media->getUrl();
        return $this->external_thumbnail;
    }

    public function getIconAttribute()
    {
        $media = $this->getMedia('link_icon')->first();
        if ($media) return $media->getUrl();
    }

    public function getImageAttribute()
    {
        return $this->icon ?? $this->thumbnail;
    }
}
