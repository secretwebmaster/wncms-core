<?php

namespace Wncms\Services\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait ModelMethods
{
    protected array $modelClassCache = [];

    public function getModelNames()
    {
        $path = app_path('Models') . '/*.php';
        return collect(glob($path))->map(function ($file) {
            $modelName = "\Wncms\Models\\" . basename($file, '.php');
            $model = new $modelName;
            return [
                'name' => basename($file, '.php'),
                'priority' => $model->menuPriority ?? 0,
                'routes' => defined(get_class($model) . "::ROUTES") ? $model::ROUTES : null,
            ];
        });
    }

    public function getModel(string $key): Model
    {
        $class = $this->getModelClass($key);
        return new $class;
    }

    public function getModelClass(string $key): string
    {
        $key = Str::snake(Str::singular($key));

        // Cache hit
        if (isset($this->modelClassCache[$key])) {
            return $this->modelClassCache[$key];
        }

        // Check config override
        $configModel = config("wncms.models.{$key}");
        if (is_array($configModel) && !empty($configModel['class']) && class_exists($configModel['class'])) {
            return $this->modelClassCache[$key] = $configModel['class'];
        }
        if (is_string($configModel) && class_exists($configModel)) {
            return $this->modelClassCache[$key] = $configModel;
        }

        // Check package-defined models
        if (property_exists($this, 'packages') && !empty($this->packages)) {
            foreach ($this->packages as $packageName => $packageData) {
                $models = $packageData['models'] ?? [];

                // exact key
                if (!empty($models[$key]) && class_exists($models[$key])) {
                    return $this->modelClassCache[$key] = $models[$key];
                }

                // plural form fallback
                if (!empty($models[Str::plural($key)]) && class_exists($models[Str::plural($key)])) {
                    return $this->modelClassCache[$key] = $models[Str::plural($key)];
                }
            }
        }

        // Fallbacks
        $studlyKey = Str::studly($key);
        $appModel = "App\\Models\\{$studlyKey}";
        if (class_exists($appModel)) {
            return $this->modelClassCache[$key] = $appModel;
        }

        $wncmsModel = "Wncms\\Models\\{$studlyKey}";
        if (class_exists($wncmsModel)) {
            return $this->modelClassCache[$key] = $wncmsModel;
        }

        throw new \RuntimeException("Model class not found for key [{$key}].");
    }

    public function registerModel(string $modelClass): void
    {
        if (!class_exists($modelClass)) {
            throw new \RuntimeException("Model class [$modelClass] does not exist.");
        }

        $key = strtolower(class_basename($modelClass));

        $this->modelClassCache[$key] = $modelClass;
    }

    public function getModels(): array
    {
        return array_values($this->modelClassCache);
    }
}
