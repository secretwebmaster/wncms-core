<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Wncms\Tags\HasTags;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Wncms\Services\Models\WncmsModel;
use Wncms\Translatable\Traits\HasTranslations;

class Link extends WncmsModel implements HasMedia
{
    use HasFactory;
    use HasTags;
    use InteractsWithMedia;
    use HasTranslations;

    protected $guarded = [];

    protected $translatable = ['name', 'description', 'slogan'];

    protected $casts = [
        'expired_at' => 'datetime',
        'hit_at' => 'datetime',
    ];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-link'
    ];

    public const ROUTES = [
        'index',
        'create',
    ];

    public const STATUSES = [
        'active',
        'inactive',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('link_thumbnail')->singleFile();
        $this->addMediaCollection('link_icon')->singleFile();
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * ! Attributes Accessor
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
}
