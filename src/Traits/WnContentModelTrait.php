<?php

namespace Wncms\Traits;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait WnContentModelTrait
{
    //! Accessor
    public function getSingleUrlAttribute()
    {
        $table = $this->getTable();
        $route = "frontend.{$table}.single";
        if(wncms_route_exists($route)){
            return route($route, ['slug' => $this->slug]);
        }
    }

    //! Handling data
    public function getTagAttributeArray($type, $attribute = "name")
    {
        $table = $this->getTable();
        $singular = str($table)->singular();
        $shouldAuth = false;
        $cacheKey = wncms()->cache()->createKey("wncms_{$singular}", __FUNCTION__, $shouldAuth, wncms()->getAllArgs(__METHOD__, func_get_args()), $this->id);
        $cacheTags = [$table,'tags'];
        $cacheTime = gss('enable_cache') ? gss('data_cache_time') : 0;
        // wncms()->cache()->clear($cacheKey, $cacheTags);
        
        return wncms()->cache()->tags($cacheTags)->remember($cacheKey, $cacheTime, function () use($type, $attribute){
            return $this->tagsWithType($type)->pluck($attribute)->toArray();
        });
    }

    public function getTagNameArray($type)
    {
        return $this->getTagAttributeArray($type, 'name');
    }

    public function getTagIds($type)
    {
        return $this->getTagAttributeArray($type, 'id');
    }

    public function getTagNameString($type, string $separator = ",")
    {
        return implode($separator, $this->getTagNameArray($type));
    }

    public function getTagNameWithWrapper($type, $prefix = "", $suffix = "")
    {
        $html = "";
        foreach($this->getTagNameArray($type) as $tagName){
            $html .= $prefix . $tagName . $suffix;
        }
        return $html;
    }

    public function getTagNameWitHtmlTag($type, $htmlTag = "span", $class = "", $id = "")
    {
        $html = "";
        foreach($this->getTagNameArray($type) as $tagName){
            $html .= "<{$htmlTag}" . (!empty($class) ? " class=\"{$class}\"" : '') . (!empty($id) ? " id=\"{$id}\"" : '') .">" . $tagName . "</{$htmlTag}>";
        }
        return $html;
    }

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

    /**
     * ----------------------------------------------------------------------------------------------------
     * 轉換圖片為webp
     * ----------------------------------------------------------------------------------------------------
     * @link https://wncms.cc
     * @since 3.1.12
     * @version 3.1.12
     * 
     * @param $imageUrl request or image Url
     * @param string $collection collection name
     * ----------------------------------------------------------------------------------------------------
     */
    public function addWebpToCollection($imageUrl, $collection)
    {
        if(method_exists($this, 'addMediaFromString')){

            $imageContents = file_get_contents($imageUrl);

            // Create an image resource from the image contents
            $image = imagecreatefromstring($imageContents);
    
            // Create a new image in .webp format
            ob_start();
            imagewebp($image, null, 90); // 90 is the quality level (adjust as needed)
            $webpImageContents = ob_get_clean();
    
            // Free up memory
            imagedestroy($image);
    
            $fileName = str()->random(16); 
            $extension = 'webp';
    
    
            // Store the image in the media library using Spatie with the original file name
            $media = $this->addMediaFromString($webpImageContents)
                ->usingFileName("{$fileName}.{$extension}")
                ->toMediaCollection($collection); // You can define your own collection name

            return $media;
        }
        
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