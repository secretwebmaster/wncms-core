<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Credit extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public const ICONS = [
        'fontaweseom' => 'fa-solid fa-cube'
    ];

    public const ROUTES = [
        'index',
        'create',
        'recharge',
    ];

    public const TYPES = [
        'credit',
        'balance',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function add($user, $amount, $type = 'points')
    {
        $credit = $user->credits()->where('type', $type)->first();

        if (!$credit) {
            $credit = $user->credits()->create([
                'type' => $type,
                'amount' => 0,
            ]);
        }

        $credit->increment('amount', $amount);

        // record the transaction

        return $credit->amount;
    }
    
    public static function get($user, $type = 'points')
    {
        return $user->credits()->where('type', $type)->first()?->amount ?? 0;
    }
}
