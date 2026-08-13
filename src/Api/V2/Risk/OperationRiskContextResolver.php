<?php

namespace Wncms\Api\V2\Risk;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Request;
use Wncms\Api\V2\Data\ApiOperationContract;
use Wncms\Auth\Api\V2\AuthenticationContext;
use Wncms\Auth\Api\V2\WebsiteScopeGuard;
use Wncms\Http\Middleware\ApiV2TokenAuth;

final class OperationRiskContextResolver
{
    /** @var array<string, callable> */
    private array $resolvers = [];

    /**
     * Create the operation risk context resolver.
     *
     * @return void
     */
    public function __construct(
        private RiskEnvironmentProvider $environment,
        private WebsiteScopeGuard $websiteScope,
    ) {}

    /**
     * Register one operation-specific server context resolver.
     *
     * @param  callable(array<string, mixed>, array<string, mixed>): array<string, mixed>  $resolver
     */
    public function register(string $operationId, callable $resolver): void
    {
        $this->resolvers[$operationId] = $resolver;
    }

    /**
     * Determine whether an operation has an application-supplied resolver.
     */
    public function hasResolver(string $operationId): bool
    {
        return isset($this->resolvers[$operationId]);
    }

    /**
     * Resolve canonical input and server-owned target/environment state.
     *
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $parameters
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
            array_values(array_unique(array_filter((array) ($resolved['connection_names'] ?? []), 'is_string'))),
        );
    }

    /**
     * Resolve a real execution request while ignoring client target/environment claims.
     *
     * @param  array<string, mixed>  $parameters
     */
    public function resolveRequest(Request $request, ApiOperationContract $operation, array $parameters = []): RiskContext
    {
        $input = $this->requestInput($request);

        if (isset($this->resolvers[$operation->id])) {
            $resolved = ($this->resolvers[$operation->id])($this->normalizeInput($operation, $input), $parameters);
            $resolved['environment'] = $resolved['environment'] ?? $this->environment->resolve($request);

            return $this->scopedContext($request, $operation, $input, $resolved);
        }

        return $this->scopedContext($request, $operation, $input, $this->resolveLegacy($operation, $this->normalizeInput($operation, $input), $parameters, false, $request));
    }

    /**
     * Resolve and lock fresh execution state inside the selected transaction.
     *
     * @param  array<string, mixed>  $parameters
     */
    public function resolveExecution(Request $request, ApiOperationContract $operation, array $parameters = []): RiskContext
    {
        $input = $this->requestInput($request);
        $normalized = $this->normalizeInput($operation, $input);
        if (isset($this->resolvers[$operation->id])) {
            $resolved = ($this->resolvers[$operation->id])($normalized, $parameters, true);
            $resolved['environment'] = $resolved['environment'] ?? $this->environment->resolve($request);

            return $this->scopedContext($request, $operation, $input, $resolved);
        }

        return $this->scopedContext($request, $operation, $input, $this->resolveLegacy($operation, $normalized, $parameters, true, $request));
    }

    /**
     * Build one normalized context value.
     *
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $resolved
     */
    private function context(ApiOperationContract $operation, array $input, array $resolved): RiskContext
    {
        $normalized = $this->normalizeInput($operation, $input);

        return new RiskContext(
            $normalized,
            $this->normalize((array) ($resolved['target_state'] ?? [])),
            $this->normalize((array) ($resolved['environment'] ?? $this->environment->resolve())),
            array_values(array_unique(array_filter((array) ($resolved['model_keys'] ?? []), 'is_string'))),
            array_values(array_unique(array_filter((array) ($resolved['connection_names'] ?? []), 'is_string'))),
        );
    }

    /**
     * Build and authorize one request-bound risk context.
     *
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $resolved
     */
    private function scopedContext(Request $request, ApiOperationContract $operation, array $input, array $resolved): RiskContext
    {
        $context = $this->context($operation, $input, $resolved);
        $authentication = $request->attributes->get(ApiV2TokenAuth::AUTH_CONTEXT_ATTRIBUTE);
        if ($authentication instanceof AuthenticationContext) {
            $this->websiteScope->assertResolvedScope($authentication, $context);
        }

        return $context;
    }

    /**
     * Resolve legacy backend target state from the configured model boundary.
     *
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
        $models = new Collection;
        if (is_string($modelClass) && isset($parameters['id'])) {
            $query = $modelClass::query()->whereKey($parameters['id']);
            $model = ($lock ? $query->lockForUpdate() : $query)->first();
            $target['record'] = $model?->getAttributes();
            $models = $model === null ? new Collection : new Collection([$model]);
        } elseif (is_string($modelClass) && $action === 'bulk_delete') {
            $ids = $this->ids($input['model_ids'] ?? []);
            sort($ids);
            $query = $modelClass::query()->whereIn('id', $ids)->orderBy('id');
            $target['requested_ids'] = $ids;
            $models = ($lock ? $query->lockForUpdate() : $query)->get();
            $target['records'] = $models->map->getAttributes()->all();
        } else {
            $target['record'] = null;
        }
        $websiteState = $this->websiteState($operation, $modelClass, $models, $input, $lock);
        if ($websiteState !== null) {
            $target['website_scope'] = $websiteState['state'];
        }

        return [
            'target_state' => $target,
            'environment' => $this->environment->resolve($request),
            'model_keys' => $operation->domainModelKeys,
            'connection_names' => $websiteState['connection_names'] ?? [],
        ];
    }

    /**
     * Resolve requested website rows and actual target pivot membership.
     *
     * @param  class-string|null  $modelClass
     * @param  \Illuminate\Database\Eloquent\Collection<int, \Illuminate\Database\Eloquent\Model>  $models
     * @param  array<string, mixed>  $input
     * @return array{state: array<string, mixed>, connection_names: array<int, string>}|null
     */
    private function websiteState(ApiOperationContract $operation, ?string $modelClass, Collection $models, array $input, bool $lock): ?array
    {
        if (! in_array('websites', $operation->relationshipBoundaries, true) || $modelClass === null) {
            return null;
        }
        $requestedIds = $this->ids(array_merge(
            isset($input['website_id']) ? [$input['website_id']] : [],
            (array) ($input['website_ids'] ?? []),
        ));
        $websiteClass = wncms()->getModelClass('website');
        $websiteQuery = $websiteClass::query()->whereIn((new $websiteClass)->getKeyName(), $requestedIds)->orderBy((new $websiteClass)->getKeyName());
        $websiteRows = ($lock ? $websiteQuery->lockForUpdate() : $websiteQuery)->get()->map->getAttributes()->all();
        $connectionNames = [(new $modelClass)->getConnection()->getName(), (new $websiteClass)->getConnection()->getName()];
        $scoped = method_exists($modelClass, 'isWebsiteScopedModel') && $modelClass::isWebsiteScopedModel();
        $pivots = [];
        $targetIds = [];
        if ($scoped) {
            if (! method_exists($modelClass, 'websites')) {
                throw new \RuntimeException("Scoped model [{$modelClass}] does not declare a websites relation.");
            }
            $relationModels = $models->isEmpty() ? new Collection([new $modelClass]) : $models;
            foreach ($relationModels as $model) {
                $relation = $model->websites();
                if (! $relation instanceof BelongsToMany) {
                    throw new \RuntimeException("Scoped model [{$modelClass}] has an unsupported websites relation.");
                }
                $connectionNames[] = $relation->getRelated()->getConnection()->getName();
                $connectionNames[] = $relation->newPivotStatement()->getConnection()->getName();
                if (! $model->exists) {
                    continue;
                }
                $relatedPivotKey = $relation->getRelatedPivotKeyName();
                $pivot = $relation->newPivotStatement()->where($relation->getForeignPivotKeyName(), $model->getKey());
                if (method_exists($relation, 'getMorphType') && method_exists($relation, 'getMorphClass')) {
                    $pivot->where($relation->getMorphType(), $relation->getMorphClass());
                }
                $pivot->orderBy($relatedPivotKey);
                $rows = ($lock ? $pivot->lockForUpdate() : $pivot)->get()->map(static fn ($row): array => (array) $row)->all();
                array_push($pivots, ...$rows);
                foreach ($rows as $row) {
                    $targetIds[] = (int) ($row[$relatedPivotKey] ?? 0);
                }
            }
        }
        $targetIds = array_values(array_unique(array_filter($targetIds)));
        sort($targetIds);

        return [
            'state' => [
                'requested_ids' => $requestedIds,
                'requested_rows' => $websiteRows,
                'target_ids' => $targetIds,
                'target_count' => $models->count(),
                'scoped_model' => $scoped,
                'pivot_rows' => $pivots,
            ],
            'connection_names' => array_values(array_unique($connectionNames)),
        ];
    }

    /**
     * Apply operation schema defaults and primitive type normalization.
     *
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
