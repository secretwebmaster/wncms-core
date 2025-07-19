<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-money-bill-transfer'
    ];

    public const ROUTES = [
        'index',
        'create',
    ];

    public const STATUSES = [
        'completed',
        'refunded',
        'failed',
    ];

    public function order()
    {
        return $this->belongsTo(wncms()->getModelClass('order'));
    }
}
