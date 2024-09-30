<?php

namespace Wncms\Traits;

use Illuminate\Database\Eloquent\Builder;

trait WnModelTrait
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        
        $trait_class_name = "Wncms\\Traits\\Custom". class_basename(static::class) ."Traits";
        if (class_exists($trait_class_name)) {
            info("Trait found: $trait_class_name");
            class_uses($this, $trait_class_name);
        }else{
            // info("Trait not found: $trait_class_name");
        }
    }

    public function getParentModelName()
    {
        return class_basename($this);
    }

    public function scopeOwn(Builder $query)
    {
        if (!isAdmin()) {
            $query->where('user_id', auth()->id());
        }
    }
}