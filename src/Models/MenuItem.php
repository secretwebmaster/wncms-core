<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Wncms\Translatable\Traits\HasTranslations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class MenuItem extends Model implements HasMedia
{
    use HasFactory;
    use HasTranslations;
    use InteractsWithMedia;

    public array $translatable = ['name', 'display_name','description'];
    protected $guarded = [];
    protected $with = [
        'children',

        //%測試隱藏後是否有Bug
        // 'children.children',
    ];

    public const ORDERS = [
        'id',
        'name',
        'order',
        'created_at',
        'updated_at',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('menu_item_thumbnail')->singleFile();
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function parent()
    {
        return $this->hasOne(self::class, 'id', 'parent_id');
    }

    public function menu()
    {
        return $this->belongsTo(wncms()->getModelClass('menu'));
    }

    //! Asttribute
    public function getThumbnailAttribute()
    {
        $media = $this->getMedia('menu_item_thumbnail')->first();
        if ($media) return $media->getUrl();
        return $this->external_thumbnail;
    }

    public function getValidatedUrlAttribute()
    {
        return wncms()->menu()->getMenuItemUrl($this);
    }

    
}
