<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    protected $guarded = [];

    public function isAvailable()
    {
        dd('isAvailable logic');
    }
}
