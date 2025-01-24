<?php

namespace Wncms\Services\MacroableModels;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MacroableModels
{
    private $macros = [];

    /**
     * Get all registered macros.
     *
     * @return array
     */
    public function getAllMacros()
    {
        return $this->macros;
    }

    /**
     * Add a macro to a specific model.
     *
     * @param string $model The model class name.
     * @param string $name The name of the macro.
     * @param \Closure $closure The macro implementation.
     * @return void
     */
    public function addMacro(String $model, String $name, \Closure $closure)
    {
        $this->checkModelSubclass($model);

        if (!isset($this->macros[$name])) $this->macros[$name] = [];
        $this->macros[$name][$model] = $closure;
        $this->syncMacros($name);
    }

    /**
     * Remove a macro from a specific model.
     *
     * @param string $model The model class name.
     * @param string $name The name of the macro to remove.
     * @return bool True if the macro was removed, false otherwise.
     */
    public function removeMacro($model, String $name)
    {
        $this->checkModelSubclass($model);

        if (isset($this->macros[$name]) && isset($this->macros[$name][$model])) {
            unset($this->macros[$name][$model]);
            if (count($this->macros[$name]) == 0) {
                unset($this->macros[$name]);
            }
            $this->syncMacros($name);
            return true;
        }

        return false;
    }

    /**
     * Check if a specific model has a macro registered.
     *
     * @param string $model The model class name.
     * @param string $name The name of the macro.
     * @return bool True if the model has the macro, false otherwise.
     */
    public function modelHasMacro($model, $name)
    {
        $this->checkModelSubclass($model);
        return (isset($this->macros[$name]) && isset($this->macros[$name][$model]));
    }

    /**
     * Get a list of models that implement a specific macro.
     *
     * @param string $name The name of the macro.
     * @return array The list of models.
     */
    public function modelsThatImplement($name)
    {
        if (!isset($this->macros[$name])) return [];
        return array_keys($this->macros[$name]);
    }

    /**
     * Get all macros registered for a specific model.
     *
     * @param string $model The model class name.
     * @return array An array of macros with their parameters.
     */
    public function macrosForModel($model)
    {
        $this->checkModelSubclass($model);

        $macros = [];

        foreach ($this->macros as $macro => $models) {
            if (isset($models[$model])) {
                $params = (new \ReflectionFunction($this->macros[$macro][$model]))->getParameters();
                $macros[$macro] = [
                    'name' => $macro,
                    'parameters' => $params,
                ];
            }
        }

        return $macros;
    }

    /**
     * Synchronize macros to the Eloquent Builder.
     *
     * @param string $name The name of the macro.
     * @return void
     */
    private function syncMacros($name)
    {
        $models = $this->macros[$name] ?? [];

        Builder::macro($name, function (...$args) use ($name, $models) {
            $class = get_class($this->getModel());

            if (!isset($models[$class])) {
                throw new \BadMethodCallException(sprintf('Call to undefined method %s::%s()', $class, $name));
            }

            $closure = \Closure::bind($models[$class], $this->getModel());
            return call_user_func($closure, ...$args);
        });
    }

    /**
     * Ensure the provided model is a subclass of Illuminate\Database\Eloquent\Model.
     *
     * @param string $model The model class name.
     * @return void
     * @throws \InvalidArgumentException If the model is not a subclass of Model.
     */
    private function checkModelSubclass(String $model)
    {
        if (!is_subclass_of($model, Model::class)) {
            throw new \InvalidArgumentException('$model must be a subclass of Illuminate\\Database\\Eloquent\\Model');
        }
    }
}
