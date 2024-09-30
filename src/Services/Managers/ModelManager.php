<?php

namespace Wncms\Services\Managers;

class ModelManager
{
    protected $cacheKeyPrefix = "wncms_model";

    public function getModelNameFromString(string $modelType)
    {
        $modelTypeMap = [
            'post',
            'page',
        ];

        $modelName = "Wncms\Models\\" . str($modelType)->ucfirst();

        if(
            in_array(str($modelType)->lower()->singular(), $modelTypeMap)
            || class_exists($modelName)
        ){
            return $modelName;
        }

        return null;
    }
}
