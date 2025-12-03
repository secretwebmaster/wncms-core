<?php

namespace Wncms\Http\Resources;

class MenuItemResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'model_type' => $this->model_type,
            'model_id' => $this->model_id,
            'icon' => $this->icon,
            'type' => $this->type,
            'name' => $this->name,
            'url' => $this->validated_url,
            'is_new_window' => $this->is_new_window,
            'is_mega_menu' => $this->is_mega_menu,
            'sort' => $this->sort,
            'description' => $this->description,
            
            'items' => $this->children->count() ? MenuItemResource::collection($this->whenLoaded('children')) : [],
        ];
    }
}
