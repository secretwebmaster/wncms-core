<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Wncms\Models\BaseModel;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Plugin extends BaseModel implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $guarded = [];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-plug'
    ];

    public const ROUTES = [
        'index',
        'create',
    ];

    public const STATUSES = [
        'active',
        'inactive',
        'safe',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('thumbnail')>singleFile();
    }
}
