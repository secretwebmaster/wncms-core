<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Wncms\Models\BaseModel;

class PageTemplate extends BaseModel
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
