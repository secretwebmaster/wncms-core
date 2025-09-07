<?php

namespace Wncms\Traits;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait WnContentModelTrait
{

    public function getExtraAttribute($key, $fallback = null)
    {
        if(!empty($this->extra_attribute->model_attributes) && !empty($this->extra_attribute->model_attributes[$key])){
            return $this->extra_attribute->model_attributes[$key];
        }
        return $fallback;
    }

    public function saveExtraAttribute(array $modelAttributes = null)
    {
        $modelAttributeData = ['model_attributes' => [wncms()->getLocale() => $modelAttributes]];

        if($this->extra_attribute) {
            // If the extraAttribute record exists, update it
            $this->extra_attribute->update($modelAttributeData);
            return true;
        } else {
            // If the extraAttribute record doesn't exist, create a new one
            $this->extra_attribute()->create($modelAttributeData);
            return true;
        }

        return false;
    }

    public function handleThumbnailFromRequest($request, $collection)
    {
        $remove = $collection . "_remove";
        $cloneId = $collection . "_clone_id";

        if (!empty($request->{$remove})) {
            $this->clearMediaCollection($collection);
        }
        
        if (!empty($request->{$cloneId})) {
            $mediaToClone = Media::find($request->{$cloneId});
            if ($mediaToClone) {
                $mediaToClone->copy($this, $collection);
            }
        }
        
        if (!empty($request->{$collection})) {
            $this->addMediaFromRequest($collection)->toMediaCollection($collection);
        }
    }
}