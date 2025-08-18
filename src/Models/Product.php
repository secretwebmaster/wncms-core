<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Wncms\Services\Models\WncmsModel;

class Product extends WncmsModel
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
        'variants' => 'array',
        'properties' => 'array',
    ];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-cube'
    ];

    public const ROUTES = [
        'index',
        'create',
    ];

    public const STATUSES = [
        'active',
        'inactive',
    ];

    public const TYPES = [
        'virtual',
        'physical',
    ];

    public function orderItems()
    {
        return $this->morphMany(wncms()->getModelClass('order_item'), 'item');
    }

    /**
     * Get all of the product's prices.
     */
    public function prices()
    {
        return $this->morphMany(wncms()->getModelClass('price'), 'priceable');
    }
}
