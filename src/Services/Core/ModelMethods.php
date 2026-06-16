<?php

namespace Wncms\Services\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;

trait ModelMethods
{
    protected array $modelClassCache = [];

    public function getModelWord(string $modelName, ?string $action = null): string
    {
        $modelTranslationKey = "wncms::word.{$modelName}";
        $translatedModelName = __($modelTranslationKey);

        if ($translatedModelName === $modelTranslationKey) {
            $translatedModelName = ucfirst(str_replace('_', ' ', $modelName));
        }

        if (empty($action)) {
            return $translatedModelName;
        }

        $actionKey = "wncms::word.model_{$action}";
        $translatedAction = __($actionKey, ['model_name' => $translatedModelName]);

        if ($translatedAction === $actionKey) {
            $translatedAction = __("wncms::word.{$action}", ['model_name' => $translatedModelName]);
        }

        if ($translatedAction === $actionKey || empty($translatedAction)) {
            return $translatedModelName . ' ' . ucfirst($action);
        }

        return $translatedAction;
    }

    public function getModelNames()
    {
        $appModelPath = app_path('Models');
        $packageModelPath = dirname(__DIR__, 2) . '/Models';
        $preferredUserModelClass = null;

        try {
            $preferredUserModelClass = $this->getModelClass('user');
        } catch (\Throwable $e) {
            $preferredUserModelClass = null;
        }

        $appModels = collect(is_dir($appModelPath) ? File::allFiles($appModelPath) : [])
            ->map(function ($file) use ($appModelPath) {
                $relativePath = Str::replaceFirst($appModelPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $namespacePath = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);
                return 'App\\Models\\' . Str::replace('.php', '', $namespacePath);
            })
            ->filter(function (string $modelClass) use ($preferredUserModelClass) {
                if ($modelClass !== 'App\\Models\\User') {
                    return true;
                }

                return empty($preferredUserModelClass) || $preferredUserModelClass === $modelClass;
            });

        $appModelBasenames = $appModels->map(fn($model) => class_basename($model))->unique();

        $packageModels = collect(is_dir($packageModelPath) ? File::allFiles($packageModelPath) : [])
            ->map(function ($file) use ($packageModelPath) {
                $relativePath = Str::replaceFirst($packageModelPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $namespacePath = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);
                return 'Wncms\\Models\\' . Str::replace('.php', '', $namespacePath);
            })
            ->filter(function ($modelName) use ($appModelBasenames, $preferredUserModelClass) {
                if (class_basename($modelName) === 'User' && $preferredUserModelClass === $modelName) {
                    return true;
                }

                return !$appModelBasenames->contains(class_basename($modelName));
            });

        return $appModels->merge($packageModels)
            ->map(function ($modelName) {
                if (!class_exists($modelName)) {
                    return null;
                }

                $ref = new ReflectionClass($modelName);
                if ($ref->isAbstract()) {
                    return null;
                }

                $model = new $modelName;
                if (!$model instanceof Model) {
                    return null;
                }

                $modelNameBase = class_basename($modelName);

                return [
                    // Legacy helper-compatible keys
                    'model_name' => $modelNameBase,
                    'model_key' => property_exists($model, 'modelKey') ? $model::$modelKey : null,
                    'model_name_with_namespace' => $modelName,
                    'priority' => property_exists($model, 'menuPriority') ? $model->menuPriority : 0,
                    'routes' => defined($modelName . '::ROUTES') ? $modelName::ROUTES : null,
                    'normalized_routes' => method_exists($modelName, 'getNormalizedRoutes') ? $modelName::getNormalizedRoutes() : null,
                    // Backward compatibility for existing getModelNames() consumers
                    'name' => $modelNameBase,
                ];
            })
            ->filter()
            ->values();
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
            $cachedModelClass = $this->modelClassCache[$key];

            if (!$this->isEloquentModelClass($cachedModelClass)) {
                unset($this->modelClassCache[$key]);
            } else {
                if ($key === 'user') {
                    $authModelClass = $this->resolveAuthUserModelClass();
                    if (!empty($authModelClass) && $authModelClass !== $cachedModelClass) {
                        return $this->modelClassCache[$key] = $authModelClass;
                    }
                }

                return $cachedModelClass;
            }
        }

        // Check config override
        $configModel = config("wncms.models.{$key}");
        if (is_array($configModel) && !empty($configModel['class']) && $this->isEloquentModelClass($configModel['class'])) {
            return $this->modelClassCache[$key] = $configModel['class'];
        }
        if (is_string($configModel) && $this->isEloquentModelClass($configModel)) {
            return $this->modelClassCache[$key] = $configModel;
        }

        // Keep user model aligned with Laravel auth provider / AUTH_MODEL.
        if ($key === 'user') {
            $authModelClass = $this->resolveAuthUserModelClass();
            if (!empty($authModelClass) && class_exists($authModelClass)) {
                return $this->modelClassCache[$key] = $authModelClass;
            }
        }

        // Check package-defined models
        if (property_exists($this, 'packages') && !empty($this->packages)) {
            foreach ($this->packages as $packageName => $packageData) {
                $models = $packageData['models'] ?? [];

                // exact key
                if (!empty($models[$key]) && $this->isEloquentModelClass($models[$key])) {
                    return $this->modelClassCache[$key] = $models[$key];
                }

                // plural form fallback
                if (!empty($models[Str::plural($key)]) && $this->isEloquentModelClass($models[Str::plural($key)])) {
                    return $this->modelClassCache[$key] = $models[Str::plural($key)];
                }
            }
        }

        // Fallbacks
        $studlyKey = Str::studly($key);
        $appModel = "App\\Models\\{$studlyKey}";
        if ($this->isEloquentModelClass($appModel)) {
            return $this->modelClassCache[$key] = $appModel;
        }

        $wncmsModel = "Wncms\\Models\\{$studlyKey}";
        if ($this->isEloquentModelClass($wncmsModel)) {
            return $this->modelClassCache[$key] = $wncmsModel;
        }

        throw new \RuntimeException("Model class not found for key [{$key}].");
    }

    protected function resolveAuthUserModelClass(): ?string
    {
        $fromEnv = env('AUTH_MODEL');
        if (is_string($fromEnv) && !empty($fromEnv) && $this->isEloquentModelClass($fromEnv)) {
            return $fromEnv;
        }

        $configured = config('auth.providers.users.model');
        if (is_string($configured) && !empty($configured) && $this->isEloquentModelClass($configured)) {
            return $configured;
        }

        return null;
    }

    protected function isEloquentModelClass(string $class): bool
    {
        return class_exists($class) && is_subclass_of($class, Model::class);
    }

    public function registerModel(string $modelClass): void
    {
        if (!$this->isEloquentModelClass($modelClass)) {
            return;
        }

        $key = strtolower(class_basename($modelClass));

        $this->modelClassCache[$key] = $modelClass;
    }

    public function getModels(): array
    {
        return array_values($this->modelClassCache);
    }

    public function getModelByKey(string $key)
    {
        // get from modelClassCache
        if (isset($this->modelClassCache[$key])) {
            $class = $this->modelClassCache[$key];
            return new $class;
        }
    }

    public function isModelActive(string|Model $model): bool
    {
        $modelClass = $model instanceof Model ? get_class($model) : (string) $model;

        if (!class_exists($modelClass)) {
            try {
                $modelClass = $this->getModelClass($modelClass);
            } catch (\Throwable $e) {
                return false;
            }
        }

        $isPackageModel = !str_starts_with($modelClass, 'Wncms\\') && !str_starts_with($modelClass, 'App\\');
        if ($isPackageModel) {
            return true;
        }

        $raw = function_exists('gss') ? gss('active_models', '[]') : '[]';
        $activeModels = is_array($raw) ? $raw : json_decode((string) $raw, true);

        if (!is_array($activeModels) || empty($activeModels)) {
            return true;
        }

        $modelBaseName = class_basename($modelClass);
        $modelKey = Str::snake(Str::singular($modelBaseName));

        if (property_exists($modelClass, 'modelKey') && !empty($modelClass::$modelKey)) {
            $modelKey = Str::snake(Str::singular((string) $modelClass::$modelKey));
        }

        $activeTokens = collect($activeModels)
            ->map(fn($item) => Str::lower((string) $item))
            ->filter()
            ->flatMap(function (string $item) {
                $base = class_basename($item);
                return [
                    $item,
                    Str::lower($base),
                    Str::lower(Str::snake(Str::singular($item))),
                    Str::lower(Str::snake(Str::singular($base))),
                ];
            })
            ->unique()
            ->values()
            ->all();

        return in_array(Str::lower($modelClass), $activeTokens, true)
            || in_array(Str::lower($modelBaseName), $activeTokens, true)
            || in_array(Str::lower($modelKey), $activeTokens, true);
    }
}
