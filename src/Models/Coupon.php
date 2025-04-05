<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    protected $guarded = [];

    public const ICONS = [
        'fontaweseom' => 'fa-solid fa-ticket'
    ];

    public function isAvailable()
    {
        dd('isAvailable logic');
    }
}
