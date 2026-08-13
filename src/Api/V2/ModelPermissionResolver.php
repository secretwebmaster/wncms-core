<?php

namespace Wncms\Api\V2;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use ReflectionClass;

final class ModelPermissionResolver
{
    private const ALLOWED_SUFFIXES = [
        'edit',
        'bulk_delete',
    ];

    /**
     * Resolve a trusted model key and concrete permission from request input.
     *
     * Only models explicitly present in the backend API resource catalog are eligible.
     *
     * @param  mixed  $selector
     * @param  string  $suffix
     * @return array{model_key: string, model_class: class-string<\Illuminate\Database\Eloquent\Model>, permission: string}|null
     */
    public function resolve(mixed $selector, string $suffix): ?array
    {
        if (! is_string($selector) || ! in_array($suffix, self::ALLOWED_SUFFIXES, true)) {
            return null;
        }

        $selector = trim($selector);
        if ($selector === '' || str_contains($selector, '\\')) {
            return null;
        }

        $modelKey = Str::snake(Str::singular($selector));
        $allowed = $this->allowedModelKeys();
        if (! in_array($modelKey, $allowed, true)) {
            return null;
        }

        try {
            $modelClass = wncms()->getModelClass($modelKey);
            if (! $this->validModelClass($modelKey, $modelClass)) {
                return null;
            }
        } catch (\Throwable) {
            return null;
        }

        return [
            'model_key' => $modelKey,
            'model_class' => $modelClass,
            'permission' => $modelKey.'_'.$suffix,
        ];
    }

    /**
     * Determine whether an actor owns any concrete permission represented by a template.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $actor
     * @param  string  $template
     * @return bool
     */
    public function actorCanAny(Authenticatable $actor, string $template): bool
    {
        $suffix = match ($template) {
            '{model}_edit' => 'edit',
            '{model}_bulk_delete' => 'bulk_delete',
            default => null,
        };
        if ($suffix === null || ! method_exists($actor, 'checkPermissionTo')) {
            return false;
        }

        foreach ($this->allowedModelKeys() as $modelKey) {
            $resolution = $this->resolve($modelKey, $suffix);
            if ($resolution !== null && $actor->checkPermissionTo($resolution['permission'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return canonical model keys explicitly supported by backend resource contracts.
     *
     * @return array<int, string>
     */
    private function allowedModelKeys(): array
    {
        $keys = [];
        foreach ((array) config('wncms-backend-api-v2.resources', []) as $resourceConfig) {
            $key = Str::snake(Str::singular((string) ($resourceConfig['model_key'] ?? '')));
            if ($key !== '') {
                $keys[] = $key;
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * Validate one resolved class and its public static canonical model key.
     *
     * @param  string  $modelKey
     * @param  string  $modelClass
     * @return bool
     */
    public function validModelClass(string $modelKey, string $modelClass): bool
    {
        try {
            if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
                return false;
            }

            $reflection = new ReflectionClass($modelClass);
            if (! $reflection->isInstantiable() || ! $reflection->hasProperty('modelKey')) {
                return false;
            }

            $property = $reflection->getProperty('modelKey');
            if (! $property->isPublic() || ! $property->isStatic()) {
                return false;
            }

            return $property->getValue() === $modelKey;
        } catch (\Throwable) {
            return false;
        }
    }
}
