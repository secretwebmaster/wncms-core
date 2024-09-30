<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TagKeyword extends Model
{
    use HasFactory;

    protected $guarded = [];

    public const ICONS = [
        'fontaweseom' => 'fa-solid fa-cube'
    ];

    public function tag()
    {
        return $this->belongsTo(Tag::class);
    }

}
