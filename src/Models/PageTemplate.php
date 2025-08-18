<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Wncms\Services\Models\WncmsModel;

class PageTemplate extends WncmsModel
{
    use HasFactory;

    protected $guarded = [];
    protected $casts = [
        'value' => 'array'
    ];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-cube'
    ];

}
