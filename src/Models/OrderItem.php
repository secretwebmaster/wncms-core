<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Wncms\Services\Models\WncmsModel;

class OrderItem extends WncmsModel
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-boxes-stacked'
    ];

    public const ROUTES = [
        'index',
        'create',
    ];

    public function order()
    {
        return $this->belongsTo(wncms()->getModelClass('order'));
    }

    public function order_itemable()
    {
        return $this->morphTo();
    }
}
