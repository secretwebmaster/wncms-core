<?php

namespace Wncms\Models;

class ApiStepUpProof extends BaseModel
{
    public static $modelKey = 'api_step_up_proof';

    protected $guarded = [];

    protected $hidden = ['proof_hash'];

    protected $casts = [
        'purposes' => 'array',
        'reserved_at' => 'datetime',
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];
}
