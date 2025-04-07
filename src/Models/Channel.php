<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
    use HasFactory;

    protected $guarded = [];

    public const ICONS = [
        'fontaweseom' => 'fa-solid fa-star'
    ];

    public const ROUTES = [
        'index',
        'create',
    ];

    public function clicks()
    {
        return $this->hasMany(Click::class);
    }
}
