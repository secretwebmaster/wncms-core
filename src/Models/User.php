<?php

namespace Wncms\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class User extends Authenticatable implements MustVerifyEmail, HasMedia
{
    use HasFactory, Notifiable;
    use HasRoles;
    use InteractsWithMedia;

    protected $guarded = [];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-user'
    ];

    public const ROUTES = [
        'index',
        'create',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    public function getRememberToken()
    {
        return $this->remember_token;
    }

    public function setRememberToken($value)
    {
        $this->remember_token = $value;
    }


    //! Relationships
    public function comments()
    {
        return $this->hasMany(wncms()->getModelClass('comment'));
    }

    public function pages()
    {
        return $this->hasMany(wncms()->getModelClass('page'));
    }

    public function posts()
    {
        return $this->hasMany(wncms()->getModelClass('post'));
    }

    public function websites()
    {
        return $this->belongsToMany(wncms()->getModelClass('website'));
    }

    public function emails_received()
    {
        return $this->hasMany(wncms()->getModelClass('email'), 'to_user_id', 'id');
    }

    // public function credits()
    // {
    //     return $this->hasMany(wncms()->getModelClass('credit'));
    // }

    // public function subscriptions()
    // {
    //     return $this->hasMany(wncms()->getModelClass('subscription'));
    // }

    // public function orders()
    // {
    //     return $this->hasMany(wncms()->getModelClass('order'));
    // }


    //! Attribues
    public function getAvatarAttribute()
    {
        return $this->getFirstMediaUrl('avatar') ?: asset('wncms/media/avatars/blank.png');
    }

    // public function getBalanceAttribute()
    // {
    //     return $this->credits->where('type', 'balance')->first()->amount ?? 0;
    // }

    // public function getCredit($type)
    // {
    //     return $this->credits->where('type', $type)->first()->amount ?? 0;
    // }

    // public function getPlans()
    // {
    //     // get an collections of unique plans
    //     return $this->subscriptions->map(function ($subscription) {
    //         return $subscription->plan;
    //     })->unique();
    // }

    // public function hasPlan($planId = null)
    // {
    //     if (!$planId) {
    //         return $this->subscriptions->where('status', 'active')->count() > 0;
    //     }

    //     return $this->getPlans()->contains('id', $planId);
    // }
}
