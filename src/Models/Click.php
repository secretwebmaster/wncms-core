<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Wncms\Models\BaseModel;

class Click extends BaseModel
{
    use HasFactory;

    /**
     * ----------------------------------------------------------------------------------------------------
     * Propertyies
     * ----------------------------------------------------------------------------------------------------
     */
    public static $modelKey = 'click';

    protected $guarded = [];

    protected $casts = [
        'parameters' => 'array',
    ];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-computer-mouse'
    ];

    public const ROUTES = [
        'index',
        [
            'name' => 'summary',
            'permission' => 'click_index',
        ],
    ];

    /**
     * ----------------------------------------------------------------------------------------------------
     * Relationships
     * ----------------------------------------------------------------------------------------------------
     */
    public function clickable()
    {
        return $this->morphTo();
    }

    public function channel()
    {
        return $this->belongsTo(wncms()->getModelClass('channel'));
    }
}
