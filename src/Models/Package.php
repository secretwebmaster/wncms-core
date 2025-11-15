<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    /**
     * ----------------------------------------------------------------------------------------------------
     * Propertyies
     * ----------------------------------------------------------------------------------------------------
     */
    public static $modelKey = 'package';

    protected $guarded = [];

    /**
     * ----------------------------------------------------------------------------------------------------
     * Model Methods
     * ----------------------------------------------------------------------------------------------------
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
