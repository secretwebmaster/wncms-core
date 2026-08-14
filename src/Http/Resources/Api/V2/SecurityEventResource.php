<?php

namespace Wncms\Http\Resources\Api\V2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SecurityEventResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return $this->resource->only([
            'event_id', 'occurred_at', 'event_type', 'severity', 'outcome', 'surface',
            'request_id', 'run_id', 'actor_type', 'actor_id', 'target_type', 'target_id',
            'credential_type', 'credential_id', 'session_id', 'website_ids', 'error_code', 'http_status',
        ]);
    }
}
