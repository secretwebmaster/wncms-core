<?php

namespace Wncms\Models;

use Wncms\Traits\WnModelTrait;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
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
    use LogsActivity;
    use HasRoles;
    use InteractsWithMedia;
    use WnModelTrait;

    protected $guarded = [];

    public const ICONS = [
        'fontaweseom' => 'fa-solid fa-user'
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['username']);
        // Chain fluent methods for configuration options
    }

    public function getRememberToken()
    {
        return $this->remember_token;
    }

    public function setRememberToken($value)
    {
        $this->remember_token = $value;
    }


    //! Relationships
    public function pages()
    {
        return $this->hasMany(Page::class);
    }

    public function posts()
    {
        $model = config('wncms.default_post_model', Post::class);
        return $this->hasMany($model);
    }

    public function websites()
    {
        return $this->belongsToMany(Website::class);
    }

    public function emails_received()
    {
        return $this->hasMany(Email::class, 'to_user_id', 'id');
    }

    public function credits()
    {
        return $this->hasMany(Credit::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }


    //! Attribues
    public function getAvatarAttribute()
    {
        return $this->getFirstMediaUrl('avatar') ?: asset('wncms/media/avatars/blank.png');
    }

    public function getBalanceAttribute()
    {
        return $this->credits->where('type', 'balance')->first()->amount ?? 0;
    }

    public function getPlans()
    {
        // get an collections of unique plans
        return $this->subscriptions->map(function ($subscription) {
            return $subscription->plan;
        })->unique();
    }

    public function hasPlan($planId = null)
    {
        if (!$planId) {
            return $this->subscriptions->where('status', 'active')->count() > 0;
        }

        return $this->getPlans()->contains('id', $planId);
    }
}
