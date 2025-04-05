<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'value' => 'decimal:2',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public const ICONS = [
        'fontaweseom' => 'fa-solid fa-comments-dollar'
    ];

    public const ROUTES = [
        'index',
        'create',
    ];
}
