<?php

namespace Wncms\Models;

use Wncms\Traits\WnModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DomainAlias extends Model
{
    use HasFactory;
    use WnModelTrait;

    protected $guarded = [];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-cube'
    ];

    public const ROUTES = [
        'index',
        'create',
    ];

    public function website()
    {
        return $this->belongsTo(wncms()->getModelClass('website'));
    }
}
