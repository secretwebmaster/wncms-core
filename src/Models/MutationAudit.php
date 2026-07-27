<?php

namespace Wncms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Wncms\Models\BaseModel;

class MutationAudit extends BaseModel
{
    use HasFactory;

    public static $modelKey = 'mutation_audit';

    protected $guarded = [];

    protected $casts = [
        'actor_id' => 'integer',
        'model_id' => 'integer',
        'website_ids' => 'array',
        'input_summary' => 'array',
        'result_code' => 'integer',
        'error_summary' => 'array',
        'context' => 'array',
    ];

    public const ROUTES = [];

    public const SURFACES = [
        'ui',
        'cli',
        'api_v1',
        'api_v2',
        'mcp',
        'system',
    ];

    public const STATUSES = [
        'success',
        'fail',
    ];

    public const SORTS = [
        'created_at',
        'surface',
        'domain',
        'action',
        'result_status',
    ];
}
