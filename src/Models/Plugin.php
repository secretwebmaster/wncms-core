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

    /**
     * ----------------------------------------------------------------------------------------------------
     * Propertyies
     * ----------------------------------------------------------------------------------------------------
     */
    public static $modelKey = 'plugin';

    protected $guarded = [];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-plug'
    ];

    public const STATUSES = [
        'active',
        'inactive',
        'safe',
    ];

    /**
     * ----------------------------------------------------------------------------------------------------
     * Contracts
     * ----------------------------------------------------------------------------------------------------
     */
    public static function getModelKey(): string
    {
        return self::$modelKey;
    }

    public static function getTagMeta(): array
    {
        return [];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('thumbnail')->singleFile();
    }
}
