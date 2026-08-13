<?php

namespace Wncms\Api\V2;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Str;

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
     * @return array{model_key: string, permission: string}|null
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
        } catch (\Throwable) {
            return null;
        }

        $declaredKey = property_exists($modelClass, 'modelKey')
            ? Str::snake(Str::singular((string) $modelClass::$modelKey))
            : '';
        if ($declaredKey !== $modelKey) {
            return null;
        }

        return [
            'model_key' => $modelKey,
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
            if ($actor->checkPermissionTo($modelKey.'_'.$suffix)) {
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
}
