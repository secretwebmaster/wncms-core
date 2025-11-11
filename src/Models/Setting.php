<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Wncms\Models\BaseModel;
use Wncms\Translatable\Traits\HasTranslations;

class Setting extends BaseModel
{
    use HasFactory;
    // use HasTranslations;

    protected $translatable = ['value'];

    protected $guarded = [];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-gear'
    ];
}
