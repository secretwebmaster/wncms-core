<?php

namespace Wncms\Http\Resources;

class MenuResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'items' => MenuItemResource::collection($this->whenLoaded('menu_items')),
        ];
    }
}
