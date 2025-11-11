<?php

namespace Wncms\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * This follows Laravel’s standard JsonResource behavior.
     */
    public function toArray($request): array
    {
        // Just return the Eloquent model attributes
        return parent::toArray($request);
    }
}
