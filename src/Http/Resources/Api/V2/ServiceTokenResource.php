<?php

namespace Wncms\Http\Resources\Api\V2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceTokenResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->token_id,
            'name' => (string) $this->name,
            'template' => (string) $this->ability_template,
            'abilities' => array_values((array) $this->abilities),
            'website_ids' => array_map('intval', (array) $this->website_ids),
            'last_used_at' => $this->last_used_at?->toAtomString(),
            'expires_at' => $this->expires_at?->toAtomString(),
            'revoked_at' => $this->revoked_at?->toAtomString(),
            'created_at' => $this->created_at?->toAtomString(),
            'updated_at' => $this->updated_at?->toAtomString(),
        ];
    }
}
