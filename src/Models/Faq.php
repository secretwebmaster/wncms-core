<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Wncms\Traits\WnContentModelTraits;
use Wncms\Traits\WnModelTrait;
use Wncms\Tags\HasTags;
use Wncms\Translatable\Traits\HasTranslations;


class Faq extends Model
{
    use HasFactory;
    use HasTags;
    use HasTranslations;
    use WnModelTrait;
    use WnContentModelTraits;

    protected $guarded = [];
    
    protected $translatable = ['question','answer','label'];

    public const ICONS = [
        'fontaweseom' => 'fa-solid fa-circle-question'
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


    //! Relationship
    public function website()
    {
        return $this->belongsTo(Website::class);
    }

}
