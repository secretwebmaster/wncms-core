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

class User extends Authenticatable implements MustVerifyEmail,HasMedia
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
        return $this->hasMany(Post::class);
    }

    public function websites()
    {
        return $this->belongsToMany(Website::class);
    }

    public function emails_received()
    {
        return $this->hasMany(Email::class, 'to_user_id', 'id');
    }

  
    //! Attribues
    public function getAvatarAttribute()
    {
        return $this->getFirstMediaUrl('avatar') ?: asset('wncms/media/avatars/blank.png');
    }

}