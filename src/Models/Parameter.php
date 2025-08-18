<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Wncms\Services\Models\WncmsModel;

class Parameter extends WncmsModel
{
    use HasFactory;

    protected $guarded = [];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-cube'
    ];

    public const ROUTES = [
        'index',
        'create',
    ];
}
