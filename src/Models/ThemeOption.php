<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Wncms\Translatable\Traits\HasTranslations;

class ThemeOption extends Model
{
    use HasFactory;
    use HasTranslations;

    /**
     * ----------------------------------------------------------------------------------------------------
     * Propertyies
     * ----------------------------------------------------------------------------------------------------
     */
    public static $modelKey = 'theme_option';

    protected $guarded = [];

    protected $translatable = ['value'];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-brush'
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
