<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $guarded = [];

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
