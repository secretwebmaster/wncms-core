<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
        'variants' => 'array',
        'attributes' => 'array',
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
        return $this->morphMany(OrderItem::class, 'item');
    }

    /**
     * Get all of the product's prices.
     */
    public function prices()
    {
        return $this->morphMany(Price::class, 'priceable');
    }
}
