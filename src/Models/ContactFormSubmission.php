<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Wncms\Services\Models\WncmsModel;

class ContactFormSubmission extends WncmsModel
{
    use HasFactory;

    protected $guarded = [];
    
    protected $casts = [
        'content' => 'array'
    ];

    public const ICONS = [
        'fontawesome' => 'fa-solid fa-envelope-open-text'
    ];

    public const ORDERS = [
        'created_at',
        'status',
    ];

    public const ROUTES = [
        // 'index',
    ];

    public function contact_form()
    {
        return $this->belongsTo(wncms()->getModelClass('contact_form'));
    }
}
