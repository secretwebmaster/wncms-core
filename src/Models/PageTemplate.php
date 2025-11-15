<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Wncms\Models\BaseModel;

class PageTemplate extends BaseModel
{
    use HasFactory;

    /**
     * ----------------------------------------------------------------------------------------------------
     * Propertyies
     * ----------------------------------------------------------------------------------------------------
     */
    public static $modelKey = 'page_template';

    protected $guarded = [];

    protected $casts = [
        'value' => 'array'
    ];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-cube'
    ];

    /**
     * ----------------------------------------------------------------------------------------------------
     * Relationships
     * ----------------------------------------------------------------------------------------------------
     */
    // public function page()
    // {
    //     return $this->belongsTo(wncms()->getModelClass('page'));
    // }

}