<?php

namespace Wncms\Models;

use Wncms\Traits\WnModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Wncms\Tags\HasTags;
use Wncms\Translatable\Traits\HasTranslations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Advertisement extends Model implements HasMedia
{
    use HasFactory;
    use HasTags;
    use InteractsWithMedia;
    use HasTranslations;
    use WnModelTrait;

    protected $guarded = [];

    protected $casts = [
        'expired_at'=>'datetime'
    ];

    protected $translatable = ['name','description','cta_text','cta_text_2'];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-rectangle-ad'
    ];

    public const ORDERS = [
        'status',
        'type',
        'expired_at',
        'name',
        'order',
        'position',
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
        'above_slider_menu',
        'below_slider_menu',
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
    ];
    
    public const TYPES = [
        'text',
        'image',
        'card',
        'script',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('advertisement_thumbnail')->singleFile();
    }

    
    /**
     * ----------------------------------------------------------------------------------------------------
     * ! Attributes Accessor
     * ----------------------------------------------------------------------------------------------------
     */
    public function getThumbnailAttribute()
    {
        $media = $this->getMedia('advertisement_thumbnail')->first();
        if ($media) return $media->getUrl();
        return $this->external_thumbnail;
    }



    /**
     * ----------------------------------------------------------------------------------------------------
     * ! Relationship
     * ----------------------------------------------------------------------------------------------------
     */
    public function website()
    {
        return $this->belongsTo(Website::class);
    }


     /**
     * ----------------------------------------------------------------------------------------------------
     * ! Handling Data
     * ----------------------------------------------------------------------------------------------------
     */

}

