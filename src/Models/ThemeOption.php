<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Wncms\Translatable\Traits\HasTranslations;

class ThemeOption extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $guarded = [];
    protected $translatable = ['value'];

    public const ICONS = [
        'fontaweseom' => 'fa-solid fa-brush'
    ];

    public function website()
    {
        return $this->belongsTo(Website::class);
    }
}
