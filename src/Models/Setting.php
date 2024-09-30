<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Wncms\Translatable\Traits\HasTranslations;

class Setting extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $translatable = ['value'];

    protected $guarded = [];

    public const ICONS = [
        'fontaweseom' => 'fa-solid fa-gear'
    ];
}
