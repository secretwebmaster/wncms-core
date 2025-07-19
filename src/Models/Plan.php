<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $guarded = [];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-money-check'
    ];

    public const ROUTES = [
        'index',
        'create',
    ];

    public const STATUSES = [
        'active',
        'inactive',
    ];

    public function subscriptions()
    {
        return $this->hasMany(wncms()->getModelClass('subscription'));
    }

    public function prices()
    {
        return $this->morphMany(wncms()->getModelClass('price'), 'priceable');
    }

    /**
     * Get the lifetime price for the plan.
     */
    public function getLifetimePrice()
    {
        return $this->prices()->lifetime()->first();
    }

    /**
     * Get the price for a specific duration.
     */
    public function getPriceForDuration(int $duration)
    {
        return $this->prices()->regular()->where('duration', $duration)->first();
    }

    /**
     * Get the latest active subscription for the plan.
     */
    public function getActiveSubscriptionAttribute()
    {
        return $this->subscriptions()->where('status', 'active')->latest()->first();
    }
}
