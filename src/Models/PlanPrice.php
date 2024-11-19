<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanPrice extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public const DURATION_UNITS = [
       'day', 'week', 'month', 'year'
    ];

    public const ICONS = [
        'fontaweseom' => 'fa-solid fa-cube'
    ];

    public const ROUTES = [
        'index',
        'create',
    ];

    public function plan()
    {
        return $this->belongsTo(Plan::class);
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
