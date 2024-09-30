<?php

namespace Wncms\Traits;

use Illuminate\Database\Eloquent\Builder;

trait OwnModelTrait
{
    public function scopeOwn(Builder $query)
    {
        if (!isAdmin()) {
            $query->where('user_id', auth()->id());
        }
    }
}