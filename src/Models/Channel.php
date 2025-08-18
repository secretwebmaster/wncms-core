<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Wncms\Services\Models\WncmsModel;

class Channel extends WncmsModel
{
    use HasFactory;

    protected $guarded = [];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-star'
    ];

    public const ROUTES = [
        'index',
        'create',
    ];

    public function clicks()
    {
        return $this->hasMany(wncms()->getModelClass('click'));
    }
}
