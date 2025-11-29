<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageBuilderContent extends Model
{
    protected $table = 'page_builder_contents';

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(wncms()->getModelClass('page'));
    }
}
