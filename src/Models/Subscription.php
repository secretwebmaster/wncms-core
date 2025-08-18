<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Wncms\Services\Models\WncmsModel;

class Subscription extends WncmsModel
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'subscribed_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-arrows-spin'
    ];

    public const ROUTES = [
        'index',
        'create',
    ];

    public const STATUSES = [
        'active',
        'expired',
        'cancelled',
    ];

    public function user()
    {
        return $this->belongsTo(wncms()->getModelClass('user'));
    }

    public function plan()
    {
        return $this->belongsTo(wncms()->getModelClass('plan'));
    }

    public function price()
    {
        return $this->belongsTo(wncms()->getModelClass('price'));
    }
}
