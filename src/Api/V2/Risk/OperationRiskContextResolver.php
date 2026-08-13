<?php

namespace Wncms\Api\V2\Risk;

use Illuminate\Http\Request;
use Wncms\Api\V2\Data\ApiOperationContract;
use Wncms\Auth\Api\V2\AuthSecurityConfig;

final class OperationRiskContextResolver
{
    /** @var array<string, callable(array<string, mixed>, array<string, mixed>): array<string, mixed>> */
    private array $resolvers = [];

    /**
     * Register one operation-specific server context resolver.
     *
     * @param  string  $operationId
     * @param  callable(array<string, mixed>, array<string, mixed>): array<string, mixed>  $resolver
     * @return void
     */
    public function register(string $operationId, callable $resolver): void
    {
        $this->resolvers[$operationId] = $resolver;
    }

    /**
     * Resolve canonical input and server-owned target/environment state.
     *
     * @param  \Wncms\Api\V2\Data\ApiOperationContract  $operation
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $parameters
     * @return \Wncms\Api\V2\Risk\RiskContext
     */
    public function resolve(ApiOperationContract $operation, array $input, array $parameters = []): RiskContext
    {
        $normalized = $this->normalizeInput($operation, $input);
        $resolved = isset($this->resolvers[$operation->id])
            ? ($this->resolvers[$operation->id])($normalized, $parameters)
            : $this->resolveLegacy($operation, $normalized, $parameters);

        return new RiskContext(
            $normalized,
            $this->normalize((array) ($resolved['target_state'] ?? [])),
            $this->normalize((array) ($resolved['environment'] ?? $this->environment())),
            array_values(array_unique(array_filter((array) ($resolved['model_keys'] ?? []), 'is_string'))),
        );
    }

    /**
     * Resolve a real execution request while ignoring client target/environment claims.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Wncms\Api\V2\Data\ApiOperationContract  $operation
     * @param  array<string, mixed>  $parameters
     * @return \Wncms\Api\V2\Risk\RiskContext
     */
    public function resolveRequest(Request $request, ApiOperationContract $operation, array $parameters = []): RiskContext
    {
        $input = $request->has('input') ? (array) $request->input('input') : $request->except(['target_state', 'environment']);

        return $this->resolve($operation, $input, $parameters);
    }

    /**
     * Resolve legacy backend target state from the configured model boundary.
     *
     * @param  \Wncms\Api\V2\Data\ApiOperationContract  $operation
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    private function resolveLegacy(ApiOperationContract $operation, array $input, array $parameters): array
    {
        $parts = explode('.', $operation->id);
        $resource = $parts[1] ?? '';
        $action = $parts[2] ?? '';
        $config = config("wncms-backend-api-v2.resources.{$resource}");
        if (! is_array($config)) {
            return [
                'target_state' => ['operation' => $operation->id],
                'environment' => $this->environment(),
            ];
        }

        $modelKey = (string) ($config['model_key'] ?? '');
        $modelClass = $modelKey !== '' ? wncms()->getModelClass($modelKey) : null;
        $target = ['resource' => $resource, 'action' => $action];
        if (is_string($modelClass) && isset($parameters['id'])) {
            $model = $modelClass::query()->find($parameters['id']);
            $target['record'] = $model?->getAttributes();
        } elseif (is_string($modelClass) && $action === 'bulk_delete') {
            $ids = array_values(array_unique(array_map('intval', (array) ($input['model_ids'] ?? []))));
            sort($ids);
            $target['records'] = $modelClass::query()->whereIn('id', $ids)->orderBy('id')->get()->map->getAttributes()->all();
        } else {
            $target['record'] = null;
        }

        return [
            'target_state' => $target,
            'environment' => $this->environment(),
            'model_keys' => $modelKey === '' ? [] : [$modelKey],
        ];
    }

    /**
     * Apply operation schema defaults and primitive type normalization.
     *
     * @param  \Wncms\Api\V2\Data\ApiOperationContract  $operation
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function normalizeInput(ApiOperationContract $operation, array $input): array
    {
        $schema = $operation->request->toArray();
        if (! is_array($schema)) {
            return $this->normalize($input);
        }
        $properties = (array) ($schema['properties'] ?? []);
        foreach ((array) ($schema['required'] ?? []) as $required) {
            if (! array_key_exists($required, $input)) {
                throw new RiskContextException('validation.failed', 422);
            }
        }
        foreach ($properties as $name => $property) {
            if (! array_key_exists($name, $input) && is_array($property) && array_key_exists('default', $property)) {
                $input[$name] = $property['default'];
            }
            if (! array_key_exists($name, $input) || ! is_array($property)) {
                continue;
            }
            $input[$name] = $this->normalizeType($input[$name], (string) ($property['type'] ?? ''));
        }

        return $this->normalize($input);
    }

    /**
     * Normalize one value according to its declared API schema type.
     *
     * @param  mixed  $value
     * @param  string  $type
     * @return mixed
     */
    private function normalizeType(mixed $value, string $type): mixed
    {
        return match ($type) {
            'integer' => filter_var($value, FILTER_VALIDATE_INT) !== false ? (int) $value : throw new RiskContextException('validation.failed', 422),
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? throw new RiskContextException('validation.failed', 422),
            'string' => is_scalar($value) ? (string) $value : throw new RiskContextException('validation.failed', 422),
            'array' => is_array($value) ? $value : throw new RiskContextException('validation.failed', 422),
            default => $value,
        };
    }

    /**
     * Resolve the server-owned security environment used by plan binding.
     *
     * @return array<string, mixed>
     */
    private function environment(): array
    {
        return [
            'security_risk' => (string) config('wncms-api-v2.risk.environment_security_risk', 'normal'),
            'high_risk_action_mode' => AuthSecurityConfig::fromRuntime()->highRiskMode(),
        ];
    }

    /**
     * Recursively sort associative arrays for deterministic hashing.
     *
     * @param  array<mixed>  $value
     * @return array<mixed>
     */
    private function normalize(array $value): array
    {
        if (! array_is_list($value)) {
            ksort($value);
        }
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->normalize($item);
            }
        }

        return $value;
    }
}
