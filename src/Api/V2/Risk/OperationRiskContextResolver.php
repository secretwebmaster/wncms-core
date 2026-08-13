<?php

namespace Wncms\Api\V2\Risk;

use Illuminate\Http\Request;
use Wncms\Api\V2\Data\ApiOperationContract;

final class OperationRiskContextResolver
{
    /** @var array<string, callable> */
    private array $resolvers = [];

    /**
     * Create the operation risk context resolver.
     *
     * @param  \Wncms\Api\V2\Risk\RiskEnvironmentProvider  $environment
     * @return void
     */
    public function __construct(private RiskEnvironmentProvider $environment) {}

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
     * Determine whether an operation has an application-supplied resolver.
     *
     * @param  string  $operationId
     * @return bool
     */
    public function hasResolver(string $operationId): bool
    {
        return isset($this->resolvers[$operationId]);
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
            : $this->resolveLegacy($operation, $normalized, $parameters, false);

        return new RiskContext(
            $normalized,
            $this->normalize((array) ($resolved['target_state'] ?? [])),
            $this->normalize((array) ($resolved['environment'] ?? $this->environment->resolve())),
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
        $input = $this->requestInput($request);

        if (isset($this->resolvers[$operation->id])) {
            $resolved = ($this->resolvers[$operation->id])($this->normalizeInput($operation, $input), $parameters);
            $resolved['environment'] = $resolved['environment'] ?? $this->environment->resolve($request);

            return $this->context($operation, $input, $resolved);
        }

        return $this->context($operation, $input, $this->resolveLegacy($operation, $this->normalizeInput($operation, $input), $parameters, false, $request));
    }

    /**
     * Resolve and lock fresh execution state inside the selected transaction.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Wncms\Api\V2\Data\ApiOperationContract  $operation
     * @param  array<string, mixed>  $parameters
     * @return \Wncms\Api\V2\Risk\RiskContext
     */
    public function resolveExecution(Request $request, ApiOperationContract $operation, array $parameters = []): RiskContext
    {
        $input = $this->requestInput($request);
        $normalized = $this->normalizeInput($operation, $input);
        if (isset($this->resolvers[$operation->id])) {
            $resolved = ($this->resolvers[$operation->id])($normalized, $parameters, true);
            $resolved['environment'] = $resolved['environment'] ?? $this->environment->resolve($request);

            return $this->context($operation, $input, $resolved);
        }

        return $this->context($operation, $input, $this->resolveLegacy($operation, $normalized, $parameters, true, $request));
    }

    /**
     * Build one normalized context value.
     *
     * @param  \Wncms\Api\V2\Data\ApiOperationContract  $operation
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $resolved
     * @return \Wncms\Api\V2\Risk\RiskContext
     */
    private function context(ApiOperationContract $operation, array $input, array $resolved): RiskContext
    {
        $normalized = $this->normalizeInput($operation, $input);

        return new RiskContext(
            $normalized,
            $this->normalize((array) ($resolved['target_state'] ?? [])),
            $this->normalize((array) ($resolved['environment'] ?? $this->environment->resolve())),
            array_values(array_unique(array_filter((array) ($resolved['model_keys'] ?? []), 'is_string'))),
        );
    }

    /**
     * Resolve legacy backend target state from the configured model boundary.
     *
     * @param  \Wncms\Api\V2\Data\ApiOperationContract  $operation
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    private function resolveLegacy(ApiOperationContract $operation, array $input, array $parameters, bool $lock, ?Request $request = null): array
    {
        $parts = explode('.', $operation->id);
        $resource = $parts[1] ?? '';
        $action = $parts[2] ?? '';
        $config = config("wncms-backend-api-v2.resources.{$resource}");
        if (! is_array($config)) {
            return [
                'target_state' => ['operation' => $operation->id],
                'environment' => $this->environment->resolve($request),
            ];
        }

        $modelKey = (string) ($operation->domainModelKeys[0] ?? '');
        $modelClass = $modelKey !== '' ? wncms()->getModelClass($modelKey) : null;
        $target = ['resource' => $resource, 'action' => $action];
        if (is_string($modelClass) && isset($parameters['id'])) {
            $query = $modelClass::query()->whereKey($parameters['id']);
            $model = ($lock ? $query->lockForUpdate() : $query)->first();
            $target['record'] = $model?->getAttributes();
        } elseif (is_string($modelClass) && $action === 'bulk_delete') {
            $ids = $this->ids($input['model_ids'] ?? []);
            sort($ids);
            $query = $modelClass::query()->whereIn('id', $ids)->orderBy('id');
            $target['requested_ids'] = $ids;
            $target['records'] = ($lock ? $query->lockForUpdate() : $query)->get()->map->getAttributes()->all();
        } else {
            $target['record'] = null;
        }

        return [
            'target_state' => $target,
            'environment' => $this->environment->resolve($request),
            'model_keys' => $operation->domainModelKeys,
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
        if ($operation->canonicalizer === 'resource') {
            return $this->normalizeLegacyResourceInput($operation, $input);
        }
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
     * Canonicalize the exact transport values consumed by generic resources.
     *
     * @param  \Wncms\Api\V2\Data\ApiOperationContract  $operation
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function normalizeLegacyResourceInput(ApiOperationContract $operation, array $input): array
    {
        $action = explode('.', $operation->id)[2] ?? '';
        if ($action === 'bulk_delete') {
            $input['model_ids'] = $this->ids($input['model_ids'] ?? []);
        }
        if (array_key_exists('website_id', $input) && $input['website_id'] !== null && $input['website_id'] !== '') {
            $input['website_id'] = (int) $input['website_id'];
        }
        if (array_key_exists('website_ids', $input)) {
            $input['website_ids'] = $this->ids($input['website_ids']);
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
     * Extract operation input while ignoring client-owned context claims.
     *
     * @return array<string, mixed>
     */
    private function requestInput(Request $request): array
    {
        return $request->has('input') ? (array) $request->input('input') : $request->except(['target_state', 'environment']);
    }

    /**
     * Normalize scalar or list identifier input into a stable sorted set.
     *
     * @param  mixed  $value
     * @return array<int, int>
     */
    private function ids(mixed $value): array
    {
        $items = is_array($value) ? $value : explode(',', (string) $value);
        $ids = array_values(array_unique(array_filter(array_map('intval', $items), static fn (int $id): bool => $id > 0)));
        sort($ids);

        return $ids;
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
