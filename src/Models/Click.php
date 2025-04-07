<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Click extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $casts = [
        'parameters' => 'array',
    ];

    public const ICONS = [
        'fontaweseom' => 'fa-solid fa-computer-mouse'
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
        return $this->belongsTo(Channel::class);
    }
}
