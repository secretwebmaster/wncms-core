<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Wncms\Models\BaseModel;

class Channel extends BaseModel
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
