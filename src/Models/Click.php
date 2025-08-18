<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Wncms\Services\Models\WncmsModel;

class Click extends WncmsModel
{
    use HasFactory;

    protected $guarded = [];
    protected $casts = [
        'parameters' => 'array',
    ];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-computer-mouse'
    ];

    public const ROUTES = [
        'index',
        // 'create',
    ];

    public function clickable()
    {
        return $this->morphTo();
    }

    public function channel()
    {
        return $this->belongsTo(wncms()->getModelClass('channel'));
    }
}
