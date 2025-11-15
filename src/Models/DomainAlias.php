<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class DomainAlias extends BaseModel
{
    use HasFactory;

    /**
     * ----------------------------------------------------------------------------------------------------
     * Propertyies
     * ----------------------------------------------------------------------------------------------------
     */
    public static $modelKey = 'domain_alias';

    protected $guarded = [];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-cube'
    ];

    /**
     * ----------------------------------------------------------------------------------------------------
     * Relationships
     * ----------------------------------------------------------------------------------------------------
     */
    public function website()
    {
        return $this->belongsTo(wncms()->getModelClass('website'));
    }
}
