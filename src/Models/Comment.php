<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Wncms\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Wncms\Translatable\Traits\HasTranslations;

class Comment extends BaseModel
{
    use HasFactory;
    use HasTranslations;
    use SoftDeletes;

    /**
     * ----------------------------------------------------------------------------------------------------
     * Propertyies
     * ----------------------------------------------------------------------------------------------------
     */
    public static $modelKey = 'comment';

    protected $guarded = [];

    protected $translatable = ['content'];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-comments'
    ];

    /**
     * ----------------------------------------------------------------------------------------------------
     * Relationships
     * ----------------------------------------------------------------------------------------------------
     */
    // Define the user that this Comment belongs to
    public function user()
    {
        return $this->belongsTo(wncms()->getModelClass('user'));
    }

    // Define the parent Comment (the comment this one is replying to)
    public function parent()
    {
        return $this->belongsTo(wncms()->getModelClass('comment'), 'parent_id');
    }

    // Define the child Comments (the replies to this comment)
    public function children()
    {
        return $this->hasMany(wncms()->getModelClass('comment'), 'parent_id');
    }

    // Define the Post or Video that this Comment belongs to
    public function commentable()
    {
        return $this->morphTo();
    }
}
