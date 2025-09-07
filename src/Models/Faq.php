<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Wncms\Services\Models\WncmsModel;
use Wncms\Traits\WnContentModelTrait;
use Wncms\Translatable\Traits\HasTranslations;


class Faq extends WncmsModel
{
    use HasFactory;
    use HasTranslations;
    use WnContentModelTrait;

    protected $guarded = [];
    
    protected $translatable = ['question','answer','label'];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-circle-question'
    ];

    public const ORDERS = [
        'id',
        'status',
        'order',
        'is_pinned',
        'created_at',
        'updated_at',
    ];

    public const ROUTES = [
        'index',
        'create',
    ];

    public const STATUSES = [
        'active',
        'inactive',
    ];
}
