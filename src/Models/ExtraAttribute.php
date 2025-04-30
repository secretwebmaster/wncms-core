<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Wncms\Translatable\Traits\HasTranslations;

class ExtraAttribute extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $guarded = [];
    protected $translatable = ['model_attributes'];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-cube'
    ];

    // public const ROUTES = [
    //     'index',
    //     'create',
    // ];

    public function extra_attributable(): MorphTo
    {
        return $this->morphTo();
    }
}
