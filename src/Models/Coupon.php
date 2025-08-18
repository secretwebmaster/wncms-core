<?php

namespace Wncms\Models;

use Wncms\Services\Models\WncmsModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends WncmsModel
{
    protected $guarded = [];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-ticket'
    ];

    public function isAvailable()
    {
        dd('isAvailable logic');
    }
}
