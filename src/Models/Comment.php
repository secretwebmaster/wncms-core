<?php

namespace Wncms\Models;

use Wncms\Traits\WnModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Wncms\Translatable\Traits\HasTranslations;

class Comment extends Model
{
    use HasFactory;
    use HasTranslations;
    use SoftDeletes;
    use WnModelTrait;

    protected $guarded = [];
    
    protected $translatable = ['content'];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-comments'
    ];

    // 定義 Comment 所屬的用戶
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 定義 Comment 所屬的父節點，即回覆的 Comment
    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    // 定義 Comment 所屬的子節點，即被回覆的 Comment
    public function children()
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    // 定義 Comment 所屬的 Post 或 Video
    public function commentable()
    {
        return $this->morphTo();
    }
}
