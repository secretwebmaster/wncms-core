<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Wncms\Services\Models\WncmsModel;
use Wncms\Translatable\Traits\HasTranslations;

class Setting extends WncmsModel
{
    use HasFactory;
    use HasTranslations;

    protected $translatable = ['value'];

    protected $guarded = [];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-gear'
    ];
}
