<?php

namespace Wncms\Traits;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait WnMediaTraits
{
    public function addThumbnailFromRequest($request, $collection)
    {
        $remove = $collection . "_remove";
        $cloneId = $collection . "_clone_id";

        if (!empty($request->{$remove})) {
            dd(1);
            $this->clearMediaCollection($collection);
        }
        
        if (!empty($request->{$cloneId})) {
            dd(2);
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
