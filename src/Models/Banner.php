<?php

namespace Wncms\Models;

use Wncms\Traits\WnModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Banner extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use WnModelTrait;

    protected $guarded = [];

    protected $casts = [
        'expired_at' => 'datetime',
        'positions' => 'array',
    ];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-images'
    ];

    public const ORDERS = [
        'order',
        'created_at',
        'expired_at',
        'updated_at',
    ];

    public const POSITIONS = [
        'header',
        'footer',
        'above_list',
        'below_list',
        'floatng_top',
        'floatng_bottom',
        'floatng_left',
        'floatng_right',
        'header_carousel',
        'footer_carousel',
        'page_sidebar',
        'above_page_content',
        'below_page_content',
        'post_sidebar',
        'above_post_content',
        'below_post_content',
        'below_player',
        'custom_position_1',
        'custom_position_2',
        'custom_position_3',
        'custom_position_4',
        'custom_position_5',
        'custom_position_6',
        'custom_position_7',
        'custom_position_8',
        'custom_position_9',
        'custom_position_10',
    ];

    public const ROUTES = [
        'index',
        'create',
    ];

    public const STATUSES = [
        'active',
        'paused',
        'suspended',
        'pending'
    ];


    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('banner_thumbnail')->singleFile();
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * ! Checking
     * ----------------------------------------------------------------------------------------------------
     */
    public function isExpired()
    {
        return $this->expired_at <= now();
    }

    /**
     * ----------------------------------------------------------------------------------------------------
     * ! Attributes Accessor
     * ----------------------------------------------------------------------------------------------------
     */
    public function getThumbnailAttribute()
    {
        return $this->external_thumbnail ?? $this->getFirstMediaUrl('banner_thumbnail');
    }

    public function getPositionsAttribute($value)
    {
        if(empty($value)) return [];
        return json_decode($value, true);
    }
}
