<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Wncms\Services\Models\WncmsModel;

class Card extends WncmsModel
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'value' => 'decimal:2',
        'redeemed_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-credit-card',
    ];

    public const ROUTES = [
        'index',
        'create',
    ];

    public const STATUSES = [
        'active',
        'redeemed',
        'expired',
    ];
    
    public const TYPES = [
        'credit',
        'balance',
        'plan',
        'product',
    ];

    public function user()
    {
        return $this->belongsTo(wncms()->getModelClass('user'));
    }

    public function plan()
    {
        return $this->belongsTo(wncms()->getModelClass('plan'));
    }

    public function product()
    {
        return $this->belongsTo(wncms()->getModelClass('product'));
    }
}
