<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Wncms\Translatable\Traits\HasTranslations;

class Menu extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $guarded = [];

    protected $translatable = ['name'];

    protected $with = [
        'menu_items',
    ];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-bars'
    ];

    public const ROUTES = [
        'index',
        'create',
    ];

    //! Relationship
    // public function website()
    // {
    //     return $this->belongsTo(Website::class);
    // }

    public function menu_items()
    {
        return $this->hasMany(wncms()->getModelClass('menu_item'));
    }

    public function direct_menu_items()
    {
        return $this->hasMany(wncms()->getModelClass('menu_item'))->whereNull('parent_id');
    }

    //! Asttribute

}
