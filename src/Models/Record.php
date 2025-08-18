<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Wncms\Services\Models\WncmsModel;

class Record extends WncmsModel
{
    use HasFactory;

    protected $fillable = [
        'type',
        'sub_type',
        'status',
        'message',
        'detail',
    ];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-clipboard'
    ];


    public const ROUTES = [
        'index',
    ];

    public const TYPES = [
        'collect',
        'payment',
        'recharge',
        'collect',
    ];

    public const SUBTYPES = [
        'paypal',
        'by_user',
        'by_admin',
    ];

    public const STATUSES = [
        'success',
        'fail',
    ];

    public const ORDERS = [
        'created_at',
        'type',
        'sub_type'
    ];

    
}
