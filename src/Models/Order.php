<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Wncms\Facades\Wncms;

class Order extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'total_amount' => 'decimal:2',
    ];

    public const ICONS = [
        'fontaweseom' => 'fa-solid fa-cube'
    ];

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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            do {
                $slug = Wncms::getUniqueSlug('orders', 'slug', 12, 'upper', 'ORD-');
            } while (self::where('slug', $slug)->exists());

            $model->slug = $slug;
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order_items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function payment_gateway()
    {
        return $this->belongsTo(PaymentGateway::class);
    }
}
