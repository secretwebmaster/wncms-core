<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Wncms\Services\Models\WncmsModel;

class Order extends WncmsModel
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'total_amount' => 'decimal:2',
    ];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-file-invoice-dollar'
    ];

    public const NAME_KEY = 'user_order';

    public const ROUTES = [
        'index',
        'create',
    ];

    public const STATUSES = [
        'pending_payment',
        // 'pending_verification',
        // 'pending_confirmation', 
        'pending_processing',
        'processing',
        'cancelled',
        'completed',
        'failed',
    ];

    public const ORDERS = [
        'id',
        'created_at',
        'updated_at',
        'status',
        'total_amount',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            do {
                $slug = wncms()->getUniqueSlug('orders', 'slug', 12, 'upper', 'ORD-');
            } while (self::where('slug', $slug)->exists());

            $model->slug = $slug;
        });
    }

    public function user()
    {
        return $this->belongsTo(wncms()->getModelClass('user'));
    }

    public function order_items()
    {
        return $this->hasMany(wncms()->getModelClass('order_item'));
    }

    public function transactions()
    {
        return $this->hasMany(wncms()->getModelClass('transaction'));
    }

    public function payment_gateway()
    {
        return $this->belongsTo(wncms()->getModelClass('payment_gateway'));
    }
}
