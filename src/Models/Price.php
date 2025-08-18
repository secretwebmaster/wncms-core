<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Wncms\Services\Models\WncmsModel;

class Price extends WncmsModel
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
        'attributes' => 'array',
    ];

    public const DURATION_UNITS = [
       'day',
       'week',
       'month',
       'year',
    ];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-cube'
    ];

    // public const ROUTES = [
    //     'index',
    //     'create',
    // ];

    public function priceable()
    {
        return $this->morphTo();
    }

    public function plan()
    {
        return $this->belongsTo(wncms()->getModelClass('plan'));
    }

    /**
     * Scope for lifetime prices.
     */
    public function scopeLifetime($query)
    {
        return $query->where('is_lifetime', true);
    }

    /**
     * Scope for regular prices (non-lifetime).
     */
    public function scopeRegular($query)
    {
        return $query->where('is_lifetime', false);
    }
}
