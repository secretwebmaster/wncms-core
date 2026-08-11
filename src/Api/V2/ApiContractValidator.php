<?php

namespace Wncms\Api\V2;

use Illuminate\Routing\RouteCollectionInterface;
use Wncms\Api\V2\Data\ApiOperationContract;

final class ApiContractValidator
{
    private const ALLOWED_IMPLEMENTATIONS = [
        'domain',
        'legacy_resource',
        'legacy_controller',
        'legacy_bridge',
    ];

    private const ALLOWED_RISKS = [
        'read',
        'write',
        'destructive',
    ];

    private const HTTP_METHODS = [
        'delete',
        'get',
        'head',
        'options',
        'patch',
        'post',
        'put',
        'trace',
    ];

    private const JSON_SCHEMA_TYPES = [
        'array',
        'boolean',
        'integer',
        'null',
        'number',
        'object',
        'string',
    ];

    private array $errors = [];

    private array $warnings = [];

    /**
     * Create a validator for one installed API contract snapshot.
     *
     * @param  \Wncms\Api\V2\ApiContractRegistry  $registry
     * @param  \Illuminate\Routing\RouteCollectionInterface  $routes
     * @param  array<string, mixed>  $openApi
     * @param  array<int, string>  $excludedRouteNames
     */
    public function __construct(
        private readonly ApiContractRegistry $registry,
        private readonly RouteCollectionInterface $routes,
        private readonly array $openApi,
        private readonly array $excludedRouteNames = [],
    ) {
    }

    /**
     * Validate registry metadata against runtime routes and OpenAPI operations.
     *
     * Results use deterministically sorted issue-code groups so CI and API
     * consumers can compare reports without depending on registration order.
     *
     * @return array{operation_count: int, v7_parity_eligible: bool, v7_parity_ineligible_operation_ids: array<int, string>, errors: array<string, array<int, array<string, mixed>>>, warnings: array<string, array<int, array<string, mixed>>>}
     */
    public function validate(): array
    {
        $this->errors = [];
        $this->warnings = [];

        $operations = $this->registry->operations();
        $this->validateRegistry($operations);
        $this->validateRoutes($operations);
        $this->validateOpenApi($operations);

        $errors = $this->normalizeIssueGroups($this->errors);
        $warnings = $this->normalizeIssueGroups($this->warnings);
        $ineligible = [];

        foreach ($operations as $operation) {
            if ($operation->implementation !== 'domain') {
                $ineligible[$operation->id] = $operation->id;
            }
        }

        ksort($ineligible);

        return [
            'operation_count' => count($operations),
            'v7_parity_eligible' => $errors === [] && $ineligible === [],
            'v7_parity_ineligible_operation_ids' => array_values($ineligible),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Validate operation identifiers, ownership, metadata, paths, and schemas.
     *
     * @param  array<string, \Wncms\Api\V2\Data\ApiOperationContract>  $operations
     * @return void
     */
    private function validateRegistry(array $operations): void
    {
        $domains = $this->registry->domains();
        $operationIds = [];
        $bindings = [];

        foreach ($operations as $registryKey => $operation) {
            $operationIds[$operation->id][] = (string) $registryKey;

            if ((string) $registryKey !== $operation->id) {
                $this->error('contract.registry_key_mismatch', [
                    'operation_id' => $operation->id,
                    'registry_key' => (string) $registryKey,
                ]);
            }

            if (! isset($domains[$operation->domain])) {
                $this->error('contract.domain_missing', [
                    'domain' => $operation->domain,
                    'operation_id' => $operation->id,
                ]);
            }

            $this->validateDomainOwnership($operation);
            $this->validateOperationMetadata($operation);
            $this->validateOperationPath($operation);
            $this->validateOperationSchemas($operation);

            $bindingKey = implode('|', [
                strtoupper($operation->method),
                $this->normalizePath($operation->path),
                $operation->routeName,
            ]);
            $bindings[$bindingKey]['method'] = strtoupper($operation->method);
            $bindings[$bindingKey]['path'] = $this->normalizePath($operation->path);
            $bindings[$bindingKey]['route_name'] = $operation->routeName;
            $bindings[$bindingKey]['operation_ids'][] = $operation->id;
        }

        foreach ($operationIds as $operationId => $registryKeys) {
            if (count($registryKeys) <= 1) {
                continue;
            }

            sort($registryKeys);
            $this->error('contract.operation_id_duplicate', [
                'operation_id' => $operationId,
                'registry_keys' => $registryKeys,
            ]);
        }

        foreach ($bindings as $binding) {
            $operationIds = array_values(array_unique($binding['operation_ids']));
            if (count($operationIds) <= 1) {
                continue;
            }

            sort($operationIds);
            $this->error('route.binding_duplicate', [
                'method' => $binding['method'],
                'operation_ids' => $operationIds,
                'path' => $binding['path'],
                'route_name' => $binding['route_name'],
            ]);
        }
    }

    /**
     * Validate that an operation identifier is owned by its declared surface and domain.
     *
     * @param  \Wncms\Api\V2\Data\ApiOperationContract  $operation
     * @return void
     */
    private function validateDomainOwnership(ApiOperationContract $operation): void
    {
        $surfacePrefix = $operation->surface.'.';
        if (! str_starts_with($operation->id, $surfacePrefix)) {
            $this->error('contract.surface_mismatch', [
                'expected_prefix' => $surfacePrefix,
                'operation_id' => $operation->id,
                'surface' => $operation->surface,
            ]);
        }

        if ($operation->surface !== 'backend') {
            return;
        }

        $domainRoot = "backend.{$operation->domain}";
        $domainPrefix = $domainRoot.'.';
        if ($operation->id !== $domainRoot && ! str_starts_with($operation->id, $domainPrefix)) {
            $this->error('contract.domain_mismatch', [
                'domain' => $operation->domain,
                'expected_prefix' => $domainPrefix,
                'operation_id' => $operation->id,
            ]);
        }
    }

    /**
     * Validate allowed metadata values and backend mutation permissions.
     *
     * @param  \Wncms\Api\V2\Data\ApiOperationContract  $operation
     * @return void
     */
    private function validateOperationMetadata(ApiOperationContract $operation): void
    {
        if (! in_array($operation->risk, self::ALLOWED_RISKS, true)) {
            $this->error('contract.risk_invalid', [
                'allowed' => self::ALLOWED_RISKS,
                'operation_id' => $operation->id,
                'value' => $operation->risk,
            ]);
        }

        if (! in_array($operation->implementation, self::ALLOWED_IMPLEMENTATIONS, true)) {
            $this->error('contract.implementation_invalid', [
                'allowed' => self::ALLOWED_IMPLEMENTATIONS,
                'operation_id' => $operation->id,
                'value' => $operation->implementation,
            ]);
        }

        $method = strtoupper($operation->method);
        if ($operation->method !== $method || ! in_array(strtolower($method), self::HTTP_METHODS, true)) {
            $this->error('contract.method_invalid', [
                'operation_id' => $operation->id,
                'value' => $operation->method,
            ]);
        }

        if ($operation->surface !== 'backend' || ! $this->isMutation($method)) {
            return;
        }

        if ($operation->permission !== null && trim($operation->permission) !== '') {
            return;
        }

        $details = [
            'implementation' => $operation->implementation,
            'operation_id' => $operation->id,
        ];

        if ($operation->implementation === 'domain') {
            $this->error('contract.permission_missing', $details);

            return;
        }

        if (in_array($operation->implementation, self::ALLOWED_IMPLEMENTATIONS, true)) {
            $this->warning('contract.legacy_permission_missing', $details);
        }
    }

    /**
     * Validate canonical route paths and path-parameter syntax.
     *
     * @param  \Wncms\Api\V2\Data\ApiOperationContract  $operation
     * @return void
     */
    private function validateOperationPath(ApiOperationContract $operation): void
    {
        $normalized = $this->normalizePath($operation->path);
        if ($operation->path !== $normalized) {
            $this->error('contract.path_not_canonical', [
                'expected_path' => $normalized,
                'operation_id' => $operation->id,
                'path' => $operation->path,
            ]);
        }

        preg_match_all('/\{([^{}]+)\}/', $normalized, $matches);
        $withoutParameters = preg_replace('/\{[^{}]+\}/', '', $normalized) ?? $normalized;
        if (str_contains($withoutParameters, '{') || str_contains($withoutParameters, '}')) {
            $this->error('contract.path_parameter_invalid', [
                'operation_id' => $operation->id,
                'path' => $normalized,
                'reason' => 'Path parameter braces are unbalanced.',
            ]);
        }

        $seen = [];
        foreach ($matches[1] ?? [] as $parameter) {
            if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $parameter) !== 1) {
                $this->error('contract.path_parameter_invalid', [
                    'operation_id' => $operation->id,
                    'parameter' => $parameter,
                    'path' => $normalized,
                    'reason' => 'Path parameters must use non-optional identifier names.',
                ]);

                continue;
            }

            if (isset($seen[$parameter])) {
                $this->error('contract.path_parameter_invalid', [
                    'operation_id' => $operation->id,
                    'parameter' => $parameter,
                    'path' => $normalized,
                    'reason' => 'Path parameter names must be unique.',
                ]);
            }

            $seen[$parameter] = true;
        }
    }

    /**
     * Validate request and response JSON Schema structure recursively.
     *
     * @param  \Wncms\Api\V2\Data\ApiOperationContract  $operation
     * @return void
     */
    private function validateOperationSchemas(ApiOperationContract $operation): void
    {
        foreach (['request' => $operation->request, 'response' => $operation->response] as $direction => $schema) {
            $issues = [];
            $schemaValue = $schema->toArray();
            $this->validateSchemaJsonValues($schemaValue, '$', $issues);
            $this->validateSchemaNode($schemaValue, '$', $issues);

            foreach ($issues as $issue) {
                $this->error('contract.schema_invalid', [
                    'direction' => $direction,
                    'location' => $issue['location'],
                    'operation_id' => $operation->id,
                    'reason' => $issue['reason'],
                ]);
            }
        }
    }

    /**
     * Validate that every schema value can be represented in JSON.
     *
     * @param  mixed  $value
     * @param  string  $location
     * @param  array<int, array{location: string, reason: string}>  $issues
     * @return void
     */
    private function validateSchemaJsonValues(mixed $value, string $location, array &$issues): void
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $childLocation = is_int($key)
                    ? $location."[{$key}]"
                    : $location.'.'.$key;
                $this->validateSchemaJsonValues($item, $childLocation, $issues);
            }

            return;
        }

        if (is_string($value) && preg_match('//u', $value) === 1) {
            return;
        }

        if (is_float($value) && is_finite($value)) {
            return;
        }

        if (is_int($value) || is_bool($value) || $value === null) {
            return;
        }

        $issues[] = [
            'location' => $location,
            'reason' => 'JSON Schema values must be JSON serializable.',
        ];
    }

    /**
     * Validate one JSON Schema node and append all deterministic structural issues.
     *
     * The empty array represents the valid empty JSON Schema object in PHP.
     *
     * @param  mixed  $schema
     * @param  string  $location
     * @param  array<int, array{location: string, reason: string}>  $issues
     * @return void
     */
    private function validateSchemaNode(mixed $schema, string $location, array &$issues): void
    {
        if (! is_array($schema)) {
            $issues[] = [
                'location' => $location,
                'reason' => 'A JSON Schema node must be an object or boolean.',
            ];

            return;
        }

        if ($schema !== [] && array_is_list($schema)) {
            $issues[] = [
                'location' => $location,
                'reason' => 'A JSON Schema object cannot be a list.',
            ];

            return;
        }

        $this->validateSchemaType($schema['type'] ?? null, $location, $issues);

        if (array_key_exists('$ref', $schema) && (! is_string($schema['$ref']) || trim($schema['$ref']) === '')) {
            $issues[] = [
                'location' => $location.'.$ref',
                'reason' => 'JSON Schema $ref must be a non-empty string.',
            ];
        }

        $properties = $schema['properties'] ?? null;
        if ($properties !== null) {
            if (! is_array($properties) || ($properties !== [] && array_is_list($properties))) {
                $issues[] = [
                    'location' => $location.'.properties',
                    'reason' => 'JSON Schema properties must be an object map.',
                ];
            } else {
                foreach ($properties as $name => $propertySchema) {
                    if (! is_string($name) || $name === '') {
                        $issues[] = [
                            'location' => $location.'.properties',
                            'reason' => 'JSON Schema property names must be non-empty strings.',
                        ];

                        continue;
                    }

                    $this->validateSchemaNode($propertySchema, $location.'.properties.'.$name, $issues);
                }
            }
        }

        if (array_key_exists('items', $schema)) {
            $this->validateSchemaNode($schema['items'], $location.'.items', $issues);
        }

        foreach (['allOf', 'anyOf', 'oneOf'] as $keyword) {
            if (! array_key_exists($keyword, $schema)) {
                continue;
            }

            $children = $schema[$keyword];
            if (! is_array($children) || ! array_is_list($children) || $children === []) {
                $issues[] = [
                    'location' => $location.'.'.$keyword,
                    'reason' => "JSON Schema {$keyword} must be a non-empty schema list.",
                ];

                continue;
            }

            foreach ($children as $index => $child) {
                $this->validateSchemaNode($child, $location.'.'.$keyword."[{$index}]", $issues);
            }
        }

        if (array_key_exists('not', $schema)) {
            $this->validateSchemaNode($schema['not'], $location.'.not', $issues);
        }

        if (array_key_exists('additionalProperties', $schema)
            && ! is_bool($schema['additionalProperties'])
            && ! is_array($schema['additionalProperties'])) {
            $issues[] = [
                'location' => $location.'.additionalProperties',
                'reason' => 'JSON Schema additionalProperties must be a boolean or schema.',
            ];
        } elseif (isset($schema['additionalProperties']) && is_array($schema['additionalProperties'])) {
            $this->validateSchemaNode($schema['additionalProperties'], $location.'.additionalProperties', $issues);
        }

        $this->validateRequiredProperties($schema, $location, $issues);
        $this->validateSchemaEnum($schema, $location, $issues);
    }

    /**
     * Validate a JSON Schema type declaration.
     *
     * @param  mixed  $type
     * @param  string  $location
     * @param  array<int, array{location: string, reason: string}>  $issues
     * @return void
     */
    private function validateSchemaType(mixed $type, string $location, array &$issues): void
    {
        if ($type === null) {
            return;
        }

        if (is_string($type)) {
            if (! in_array($type, self::JSON_SCHEMA_TYPES, true)) {
                $issues[] = [
                    'location' => $location.'.type',
                    'reason' => "Unsupported JSON Schema type '{$type}'.",
                ];
            }

            return;
        }

        if (! is_array($type) || ! array_is_list($type) || $type === []) {
            $issues[] = [
                'location' => $location.'.type',
                'reason' => 'JSON Schema type must be a supported string or non-empty string list.',
            ];

            return;
        }

        $seen = [];
        foreach ($type as $item) {
            if (! is_string($item) || ! in_array($item, self::JSON_SCHEMA_TYPES, true)) {
                $issues[] = [
                    'location' => $location.'.type',
                    'reason' => 'JSON Schema type list contains an unsupported value.',
                ];

                return;
            }

            if (isset($seen[$item])) {
                $issues[] = [
                    'location' => $location.'.type',
                    'reason' => 'JSON Schema type list values must be unique.',
                ];

                return;
            }

            $seen[$item] = true;
        }
    }

    /**
     * Validate object required-property declarations.
     *
     * @param  array<string, mixed>  $schema
     * @param  string  $location
     * @param  array<int, array{location: string, reason: string}>  $issues
     * @return void
     */
    private function validateRequiredProperties(array $schema, string $location, array &$issues): void
    {
        if (! array_key_exists('required', $schema)) {
            return;
        }

        $required = $schema['required'];
        if (! is_array($required) || ! array_is_list($required)) {
            $issues[] = [
                'location' => $location.'.required',
                'reason' => 'JSON Schema required must be a string list.',
            ];

            return;
        }

        $properties = is_array($schema['properties'] ?? null) ? $schema['properties'] : [];
        $seen = [];

        foreach ($required as $index => $name) {
            $itemLocation = $location.'.required'."[{$index}]";
            if (! is_string($name) || $name === '') {
                $issues[] = [
                    'location' => $itemLocation,
                    'reason' => 'Required property names must be non-empty strings.',
                ];

                continue;
            }

            if (isset($seen[$name])) {
                $issues[] = [
                    'location' => $itemLocation,
                    'reason' => "Required property '{$name}' is duplicated.",
                ];
            }

            if (! array_key_exists($name, $properties)) {
                $issues[] = [
                    'location' => $itemLocation,
                    'reason' => "Required property '{$name}' is not declared in properties.",
                ];
            }

            $seen[$name] = true;
        }
    }

    /**
     * Validate JSON Schema enum structure and uniqueness.
     *
     * @param  array<string, mixed>  $schema
     * @param  string  $location
     * @param  array<int, array{location: string, reason: string}>  $issues
     * @return void
     */
    private function validateSchemaEnum(array $schema, string $location, array &$issues): void
    {
        if (! array_key_exists('enum', $schema)) {
            return;
        }

        $enum = $schema['enum'];
        if (! is_array($enum) || ! array_is_list($enum) || $enum === []) {
            $issues[] = [
                'location' => $location.'.enum',
                'reason' => 'JSON Schema enum must be a non-empty list.',
            ];

            return;
        }

        $encoded = [];
        foreach ($enum as $value) {
            try {
                $encoded[] = json_encode($value, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                continue;
            }
        }

        if (count($encoded) !== count(array_unique($encoded))) {
            $issues[] = [
                'location' => $location.'.enum',
                'reason' => 'JSON Schema enum values must be unique.',
            ];
        }
    }

    /**
     * Validate both registry-to-route and business-route-to-registry coverage.
     *
     * @param  array<string, \Wncms\Api\V2\Data\ApiOperationContract>  $operations
     * @return void
     */
    private function validateRoutes(array $operations): void
    {
        $routeEntries = $this->routeEntries();
        $routesByName = [];
        $contractBindings = [];

        foreach ($routeEntries as $entry) {
            if ($entry['route_name'] !== null) {
                $routesByName[$entry['route_name']][] = $entry;
            }
        }

        foreach ($operations as $operation) {
            $expectedMethod = strtoupper($operation->method);
            $expectedPath = $this->normalizePath($operation->path);
            $bindingKey = $this->bindingKey($expectedMethod, $expectedPath, $operation->routeName);
            $contractBindings[$bindingKey] = true;
            $namedRoutes = $routesByName[$operation->routeName] ?? [];

            if ($namedRoutes === []) {
                $matchingBindings = array_values(array_filter(
                    $routeEntries,
                    static fn (array $entry): bool => $entry['method'] === $expectedMethod
                        && $entry['path'] === $expectedPath
                ));

                if ($matchingBindings !== []) {
                    $actualRouteNames = array_values(array_unique(array_filter(
                        array_column($matchingBindings, 'route_name'),
                        static fn (?string $name): bool => $name !== null
                    )));
                    sort($actualRouteNames);
                    $this->error('route.name_mismatch', [
                        'actual_route_names' => $actualRouteNames,
                        'expected_route_name' => $operation->routeName,
                        'method' => $expectedMethod,
                        'operation_id' => $operation->id,
                        'path' => $expectedPath,
                    ]);

                    continue;
                }

                $this->error('route.missing', [
                    'operation_id' => $operation->id,
                    'route_name' => $operation->routeName,
                ]);

                continue;
            }

            $actualMethods = array_values(array_unique(array_column($namedRoutes, 'method')));
            sort($actualMethods);
            if (! in_array($expectedMethod, $actualMethods, true)) {
                $this->error('route.method_mismatch', [
                    'actual_methods' => $actualMethods,
                    'expected_method' => $expectedMethod,
                    'operation_id' => $operation->id,
                    'route_name' => $operation->routeName,
                ]);
            }

            $actualPaths = array_values(array_unique(array_column($namedRoutes, 'path')));
            sort($actualPaths);
            if (! in_array($expectedPath, $actualPaths, true)) {
                $this->error('route.path_mismatch', [
                    'actual_paths' => $actualPaths,
                    'expected_path' => $expectedPath,
                    'operation_id' => $operation->id,
                    'route_name' => $operation->routeName,
                ]);
            }
        }

        foreach ($routeEntries as $entry) {
            if (! str_starts_with($entry['path'], '/api/v2/')) {
                continue;
            }

            if ($entry['route_name'] !== null && in_array($entry['route_name'], $this->excludedRouteNames, true)) {
                continue;
            }

            if ($entry['route_name'] === null) {
                $this->error('route.name_missing', [
                    'method' => $entry['method'],
                    'path' => $entry['path'],
                ]);

                continue;
            }

            $bindingKey = $this->bindingKey($entry['method'], $entry['path'], $entry['route_name']);
            if (! isset($contractBindings[$bindingKey])) {
                $this->error('route.unregistered', [
                    'method' => $entry['method'],
                    'path' => $entry['path'],
                    'route_name' => $entry['route_name'],
                ]);
            }
        }
    }

    /**
     * Export normalized runtime route entries without Laravel's implicit GET HEAD alias.
     *
     * @return array<int, array{method: string, path: string, route_name: string|null}>
     */
    private function routeEntries(): array
    {
        $entries = [];

        foreach ($this->routes->getRoutes() as $route) {
            $methods = array_values(array_unique(array_map('strtoupper', $route->methods())));
            if (in_array('GET', $methods, true)) {
                $methods = array_values(array_diff($methods, ['HEAD']));
            }
            sort($methods);

            foreach ($methods as $method) {
                $entries[] = [
                    'method' => $method,
                    'path' => $this->normalizePath($route->uri()),
                    'route_name' => $route->getName(),
                ];
            }
        }

        usort($entries, static function (array $left, array $right): int {
            return [$left['path'], $left['method'], $left['route_name'] ?? '']
                <=> [$right['path'], $right['method'], $right['route_name'] ?? ''];
        });

        return $entries;
    }

    /**
     * Validate exact one-to-one operation coverage in the OpenAPI document.
     *
     * @param  array<string, \Wncms\Api\V2\Data\ApiOperationContract>  $operations
     * @return void
     */
    private function validateOpenApi(array $operations): void
    {
        $openApiOperations = $this->openApiOperations();
        $registryById = [];

        foreach ($operations as $operation) {
            $registryById[$operation->id][] = $operation;
        }

        foreach ($registryById as $operationId => $registered) {
            $occurrences = $openApiOperations[$operationId] ?? [];

            if ($occurrences === []) {
                $this->error('openapi.operation_missing', [
                    'operation_id' => $operationId,
                ]);

                continue;
            }

            if (count($occurrences) > 1) {
                $this->error('openapi.operation_duplicate', [
                    'occurrences' => $occurrences,
                    'operation_id' => $operationId,
                ]);

                continue;
            }

            if (count($registered) > 1) {
                continue;
            }

            $operation = $registered[0];
            $actual = $occurrences[0];
            $expected = [
                'method' => strtoupper($operation->method),
                'path' => $this->normalizePath($operation->path),
            ];

            if ($actual !== $expected) {
                $this->error('openapi.operation_binding_mismatch', [
                    'actual' => $actual,
                    'expected' => $expected,
                    'operation_id' => $operationId,
                ]);
            }
        }

        foreach ($openApiOperations as $operationId => $occurrences) {
            if (isset($registryById[$operationId])) {
                continue;
            }

            $this->error('openapi.operation_extra', [
                'operation_id' => $operationId,
            ]);
        }
    }

    /**
     * Flatten OpenAPI path operations by operation identifier.
     *
     * @return array<string, array<int, array{method: string, path: string}>>
     */
    private function openApiOperations(): array
    {
        $operations = [];
        $paths = $this->openApi['paths'] ?? null;

        if (! is_array($paths)) {
            $this->error('openapi.paths_invalid', [
                'reason' => 'OpenAPI paths must be an object map.',
            ]);

            return [];
        }

        foreach ($paths as $path => $pathItem) {
            if (! is_string($path) || ! is_array($pathItem)) {
                $this->error('openapi.path_item_invalid', [
                    'path' => is_string($path) ? $path : '',
                ]);

                continue;
            }

            foreach ($pathItem as $method => $operation) {
                if (! is_string($method) || ! in_array(strtolower($method), self::HTTP_METHODS, true)) {
                    continue;
                }

                if (! is_array($operation)) {
                    $this->error('openapi.operation_invalid', [
                        'method' => strtoupper($method),
                        'path' => $this->normalizePath($path),
                    ]);

                    continue;
                }

                $operationId = $operation['operationId'] ?? null;
                if (! is_string($operationId) || trim($operationId) === '') {
                    $this->error('openapi.operation_id_missing', [
                        'method' => strtoupper($method),
                        'path' => $this->normalizePath($path),
                    ]);

                    continue;
                }

                $operations[$operationId][] = [
                    'method' => strtoupper($method),
                    'path' => $this->normalizePath($path),
                ];
            }
        }

        foreach ($operations as &$occurrences) {
            usort($occurrences, static function (array $left, array $right): int {
                return [$left['path'], $left['method']] <=> [$right['path'], $right['method']];
            });
        }
        unset($occurrences);
        ksort($operations);

        return $operations;
    }

    /**
     * Determine whether an HTTP method changes server state.
     *
     * @param  string  $method
     * @return bool
     */
    private function isMutation(string $method): bool
    {
        return ! in_array($method, ['GET', 'HEAD', 'OPTIONS'], true);
    }

    /**
     * Normalize a route or contract path for exact binding comparisons.
     *
     * @param  string  $path
     * @return string
     */
    private function normalizePath(string $path): string
    {
        $path = preg_replace('#/+#', '/', trim($path)) ?? trim($path);
        $path = '/'.ltrim($path, '/');

        return $path === '/' ? $path : rtrim($path, '/');
    }

    /**
     * Build a stable route binding key.
     *
     * @param  string  $method
     * @param  string  $path
     * @param  string  $routeName
     * @return string
     */
    private function bindingKey(string $method, string $path, string $routeName): string
    {
        return strtoupper($method).'|'.$this->normalizePath($path).'|'.$routeName;
    }

    /**
     * Append one validation error.
     *
     * @param  string  $code
     * @param  array<string, mixed>  $details
     * @return void
     */
    private function error(string $code, array $details): void
    {
        $this->errors[$code][] = $details;
    }

    /**
     * Append one non-fatal validation warning.
     *
     * @param  string  $code
     * @param  array<string, mixed>  $details
     * @return void
     */
    private function warning(string $code, array $details): void
    {
        $this->warnings[$code][] = $details;
    }

    /**
     * Sort issue groups, detail maps, and duplicate details deterministically.
     *
     * @param  array<string, array<int, array<string, mixed>>>  $groups
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function normalizeIssueGroups(array $groups): array
    {
        ksort($groups);

        foreach ($groups as &$details) {
            $details = array_map(fn (array $item): array => $this->normalizeMap($item), $details);
            usort($details, static function (array $left, array $right): int {
                return json_encode($left, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
                    <=> json_encode($right, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            });
            $details = array_values(array_unique($details, SORT_REGULAR));
        }
        unset($details);

        return $groups;
    }

    /**
     * Sort associative detail maps recursively while preserving list order.
     *
     * @param  array<int|string, mixed>  $value
     * @return array<int|string, mixed>
     */
    private function normalizeMap(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->normalizeMap($item);
            }
        }

        if ($value !== [] && ! array_is_list($value)) {
            ksort($value);
        }

        return $value;
    }
}
