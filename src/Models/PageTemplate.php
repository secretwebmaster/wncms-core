<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PageTemplate extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $casts = [
        'value' => 'array'
    ];

    public const ICONS = [
        'fontaweseom' => 'fa-solid fa-cube'
    ];

}
