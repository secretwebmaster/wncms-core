<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $guarded = [];

    public const ICONS = [
        'fontaweseom' => 'fa-solid fa-cube'
    ];

    public const ROUTES = [
        'index',
        'create',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function prices()
    {
        return $this->hasMany(PlanPrice::class);
    }

    /**
     * Get the lifetime price for the plan.
     */
    public function getLifetimePrice(): ?PlanPrice
    {
        return $this->prices()->lifetime()->first();
    }

    /**
     * Get the price for a specific duration.
     */
    public function getPriceForDuration(int $duration): ?PlanPrice
    {
        return $this->prices()->regular()->where('duration', $duration)->first();
    }
}
