<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Wncms\Services\Models\WncmsModel;

class Discount extends WncmsModel
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'value' => 'decimal:2',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-comments-dollar'
    ];

    public const ROUTES = [
        'index',
        'create',
    ];
}
