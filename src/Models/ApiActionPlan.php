<?php

namespace Wncms\Models;

class ApiActionPlan extends BaseModel
{
    public static $modelKey = 'api_action_plan';

    protected $guarded = [];

    protected $hidden = ['confirmation_hash'];

    protected $casts = [
        'website_ids' => 'array',
        'reserved_at' => 'datetime',
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];
}
