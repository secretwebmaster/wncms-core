<?php

namespace Wncms\Api\V2;

use Illuminate\Routing\RouteCollectionInterface;
use Wncms\Api\V2\Data\ApiOperationContract;

final class ApiContractValidator
{
    private const ALLOWED_SURFACES = [
        'frontend',
        'backend',
    ];

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

    private const ALLOWED_SECURITY_RISKS = ['normal', 'sensitive', 'high', 'critical'];

    private const ALLOWED_CREDENTIAL_TYPES = ['interactive_access', 'refresh', 'service_token', 'legacy_personal_access_token'];

    private const ALLOWED_WEBSITE_SCOPE_MODES = ['none', 'optional', 'required'];

    private const ALLOWED_REFRESH_TRANSPORTS = ['json', 'cookie'];

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
     * @param  array<string, mixed>  $openApi
     * @param  array<int, string>  $excludedRouteNames
     */
    public function __construct(
        private readonly ApiContractRegistry $registry,
        private readonly RouteCollectionInterface $routes,
        private readonly array $openApi,
        private readonly array $excludedRouteNames = [],
    ) {}

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

        return $this->normalizeMap([
            'operation_count' => count($operations),
            'v7_parity_eligible' => $errors === [] && $ineligible === [],
            'v7_parity_ineligible_operation_ids' => array_values($ineligible),
            'errors' => $errors,
            'warnings' => $warnings,
        ]);
    }

    /**
     * Validate operation identifiers, ownership, metadata, paths, and schemas.
     *
     * @param  array<string, \Wncms\Api\V2\Data\ApiOperationContract>  $operations
     */
    private function validateRegistry(array $operations): void
    {
        $domains = $this->registry->domains();
        $operationIds = [];
        $methodPathBindings = [];
        $routeNameBindings = [];

        $this->validateDomainIdentities($domains);

        foreach ($operations as $registryKey => $operation) {
            $this->validateOperationIdentities($registryKey, $operation);
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

            $method = strtoupper($operation->method);
            $path = $this->normalizePath($operation->path);
            $methodPathKey = $method.'|'.$path;
            $methodPathBindings[$methodPathKey]['method'] = $method;
            $methodPathBindings[$methodPathKey]['path'] = $path;
            $methodPathBindings[$methodPathKey]['route_names'][] = $operation->routeName;
            $methodPathBindings[$methodPathKey]['operation_ids'][] = $operation->id;

            $routeNameBindings[$operation->routeName]['route_name'] = $operation->routeName;
            $routeNameBindings[$operation->routeName]['bindings'][$methodPathKey] = [
                'method' => $method,
                'path' => $path,
            ];
            $routeNameBindings[$operation->routeName]['operation_ids'][] = $operation->id;
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

        foreach ($methodPathBindings as $binding) {
            $operationIds = array_values(array_unique($binding['operation_ids']));
            if (count($operationIds) <= 1) {
                continue;
            }

            sort($operationIds);
            $routeNames = array_values(array_unique($binding['route_names']));
            sort($routeNames);
            $details = [
                'method' => $binding['method'],
                'operation_ids' => $operationIds,
                'path' => $binding['path'],
            ];
            if (count($routeNames) === 1) {
                $details['route_name'] = $routeNames[0];
            } else {
                $details['route_names'] = $routeNames;
            }

            $this->error('route.binding_duplicate', $details);
        }

        foreach ($routeNameBindings as $binding) {
            $operationIds = array_values(array_unique($binding['operation_ids']));
            if (count($operationIds) <= 1) {
                continue;
            }

            sort($operationIds);
            ksort($binding['bindings']);
            $this->error('route.name_duplicate', [
                'bindings' => array_values($binding['bindings']),
                'operation_ids' => $operationIds,
                'route_name' => $binding['route_name'],
            ]);
        }
    }

    /**
     * Validate every domain identity that may appear in a contract report.
     *
     * @param  array<string, \Wncms\Api\V2\Data\ApiDomainContract>  $domains
     */
    private function validateDomainIdentities(array $domains): void
    {
        foreach ($domains as $registryKey => $domain) {
            $identities = [
                'domain.registry_key' => (string) $registryKey,
                'domain.key' => $domain->key,
                'domain.label' => $domain->label,
            ];
            $safeDomainKey = $this->safeString($domain->key);

            foreach ($identities as $field => $value) {
                if ($this->isValidUtf8($value)) {
                    continue;
                }

                $this->error('contract.identity_invalid', [
                    'domain_key' => $safeDomainKey,
                    'field' => $field,
                    'value' => $this->safeString($value),
                ]);
            }
        }
    }

    /**
     * Validate every operation identity that may appear in a contract report.
     *
     * Invalid byte sequences are replaced with deterministic markers before
     * being added to an issue so sorting never receives unsafe report values.
     */
    private function validateOperationIdentities(int|string $registryKey, ApiOperationContract $operation): void
    {
        $identities = [
            'operation.registry_key' => (string) $registryKey,
            'operation.id' => $operation->id,
            'operation.domain' => $operation->domain,
            'operation.surface' => $operation->surface,
            'operation.method' => $operation->method,
            'operation.path' => $operation->path,
            'operation.route_name' => $operation->routeName,
            'operation.risk' => $operation->risk,
            'operation.implementation' => $operation->implementation,
        ];

        if ($operation->permission !== null) {
            $identities['operation.permission'] = $operation->permission;
        }

        if ($operation->ability !== null) {
            $identities['operation.ability'] = $operation->ability;
        }

        foreach (['filters', 'sorts', 'includes', 'fields'] as $collection) {
            foreach ($operation->{$collection} as $index => $value) {
                if (is_string($value)) {
                    $identities["operation.{$collection}[{$index}]"] = $value;
                }
            }
        }

        $safeOperationId = $this->safeString($operation->id);
        foreach ($identities as $field => $value) {
            if ($this->isValidUtf8($value)) {
                continue;
            }

            $this->error('contract.identity_invalid', [
                'field' => $field,
                'operation_id' => $safeOperationId,
                'value' => $this->safeString($value),
            ]);
        }
    }

    /**
     * Validate backend operation identifiers against their declared domain.
     *
     * Surface identifies the transport and authentication boundary, not an
     * operation-ID namespace. This permits stable domain IDs such as
     * system.translations and plugin-provided IDs on the frontend surface.
     */
    private function validateDomainOwnership(ApiOperationContract $operation): void
    {
        if ($operation->surface !== 'backend') {
            return;
        }

        $domainRoot = "backend.{$operation->domain}";
        $domainPrefix = $domainRoot.'.';
        $aliases = $operation->domain === 'authentication' ? ['backend.auth.'] : [];
        $matchesAlias = collect($aliases)->contains(fn (string $prefix): bool => str_starts_with($operation->id, $prefix));
        if ($operation->id !== $domainRoot && ! str_starts_with($operation->id, $domainPrefix) && ! $matchesAlias) {
            $this->error('contract.domain_mismatch', [
                'domain' => $operation->domain,
                'expected_prefix' => $domainPrefix,
                'operation_id' => $operation->id,
            ]);
        }
    }

    /**
     * Validate allowed metadata values and backend mutation permissions.
     */
    private function validateOperationMetadata(ApiOperationContract $operation): void
    {
        if (! in_array($operation->surface, self::ALLOWED_SURFACES, true)) {
            $this->error('contract.surface_invalid', [
                'allowed' => self::ALLOWED_SURFACES,
                'operation_id' => $this->safeString($operation->id),
                'value' => $this->safeString($operation->surface),
            ]);
        }

        $this->validateMetadataLists($operation);

        if (! in_array($operation->securityRisk, self::ALLOWED_SECURITY_RISKS, true)) {
            $this->error('contract.security_risk_invalid', ['operation_id' => $operation->id, 'value' => $operation->securityRisk]);
        }
        foreach ($operation->acceptedCredentialTypes as $type) {
            if (! in_array($type, self::ALLOWED_CREDENTIAL_TYPES, true)) {
                $this->error('contract.credential_type_invalid', ['operation_id' => $operation->id, 'value' => $type]);
            }
        }
        if ($operation->requiresStepUp && $operation->stepUpPurposes === []) {
            $this->error('contract.step_up_purpose_missing', ['operation_id' => $operation->id]);
        }
        if (! $operation->requiresStepUp && $operation->stepUpPurposes !== []) {
            $this->error('contract.step_up_purpose_unexpected', ['operation_id' => $operation->id]);
        }
        $acceptsLegacy = in_array('legacy_personal_access_token', $operation->acceptedCredentialTypes, true);
        if ($operation->securityRisk === 'critical' && ($acceptsLegacy || $operation->legacyTokenAllowed)) {
            $this->error('contract.critical_legacy_forbidden', ['operation_id' => $operation->id]);
        }
        if ($operation->legacyTokenAllowed !== $acceptsLegacy) {
            $this->error('contract.legacy_token_metadata_mismatch', ['operation_id' => $operation->id]);
        }
        if (! in_array($operation->websiteScopeMode, self::ALLOWED_WEBSITE_SCOPE_MODES, true)) {
            $this->error('contract.website_scope_mode_invalid', ['operation_id' => $operation->id, 'value' => $operation->websiteScopeMode]);
        }
        if ($operation->idempotencyRequired && ! $operation->idempotent) {
            $this->error('contract.idempotency_metadata_mismatch', ['operation_id' => $operation->id]);
        }
        foreach ($operation->refreshTransports as $transport) {
            if (! in_array($transport, self::ALLOWED_REFRESH_TRANSPORTS, true)) {
                $this->error('contract.refresh_transport_invalid', ['operation_id' => $operation->id, 'value' => $transport]);
            }
        }

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

        if ($operation->surface !== 'backend' || ! $this->isMutation($method) || $operation->domain === 'authentication') {
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
     * Validate query metadata as unique lists of dotted ASCII identifiers.
     */
    private function validateMetadataLists(ApiOperationContract $operation): void
    {
        foreach (['filters', 'sorts', 'includes', 'fields'] as $collection) {
            $values = $operation->{$collection};
            if (! array_is_list($values)) {
                $this->metadataError($operation, $collection, 'Metadata must be a list.');

                continue;
            }

            $seen = [];
            foreach ($values as $index => $value) {
                if (! is_string($value)) {
                    $this->metadataError($operation, $collection, 'Metadata values must be strings.', $index);

                    continue;
                }

                if (trim($value) === '') {
                    $this->metadataError($operation, $collection, 'Metadata values must not be empty.', $index);

                    continue;
                }

                if (! $this->isValidUtf8($value)) {
                    $this->metadataError($operation, $collection, 'Metadata values must be valid UTF-8.', $index);

                    continue;
                }

                if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*(?:\.[A-Za-z_][A-Za-z0-9_]*)*$/D', $value) !== 1) {
                    $this->metadataError($operation, $collection, 'Metadata values must use dotted identifier syntax.', $index);

                    continue;
                }

                if (isset($seen[$value])) {
                    $this->metadataError($operation, $collection, 'Metadata values must be unique.', $index);

                    continue;
                }

                $seen[$value] = true;
            }
        }
    }

    /**
     * Append a JSON-safe metadata validation error.
     */
    private function metadataError(
        ApiOperationContract $operation,
        string $collection,
        string $reason,
        ?int $index = null
    ): void {
        $details = [
            'collection' => $collection,
            'operation_id' => $this->safeString($operation->id),
            'reason' => $reason,
        ];
        if ($index !== null) {
            $details['index'] = $index;
        }

        $this->error('contract.metadata_invalid', $details);
    }

    /**
     * Validate canonical route paths and path-parameter syntax.
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
     * @param  array<int, array{location: string, reason: string}>  $issues
     */
    private function validateSchemaJsonValues(mixed $value, string $location, array &$issues): void
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $childLocation = $this->schemaChildLocation($location, $key);
                if (is_string($key) && ! $this->isValidUtf8($key)) {
                    $issues[] = [
                        'location' => $childLocation,
                        'reason' => 'JSON Schema map keys must be valid UTF-8.',
                    ];
                }

                $this->validateSchemaJsonValues($item, $childLocation, $issues);
            }

            return;
        }

        if (is_string($value) && $this->isValidUtf8($value)) {
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
     * @param  array<int, array{location: string, reason: string}>  $issues
     */
    private function validateSchemaNode(mixed $schema, string $location, array &$issues): void
    {
        if (is_bool($schema)) {
            return;
        }

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

        foreach (['$defs', 'properties', 'patternProperties', 'dependentSchemas'] as $keyword) {
            if (! array_key_exists($keyword, $schema)) {
                continue;
            }

            $children = $schema[$keyword];
            if (! is_array($children) || ($children !== [] && array_is_list($children))) {
                $issues[] = [
                    'location' => $location.'.'.$keyword,
                    'reason' => "JSON Schema {$keyword} must be an object map.",
                ];

                continue;
            }

            foreach ($children as $name => $child) {
                if ($keyword === 'properties' && (! is_string($name) || $name === '')) {
                    $issues[] = [
                        'location' => $location.'.properties',
                        'reason' => 'JSON Schema property names must be non-empty strings.',
                    ];

                    continue;
                }

                $this->validateSchemaNode(
                    $child,
                    $this->schemaChildLocation($location.'.'.$keyword, $name),
                    $issues,
                );
            }
        }

        foreach (['allOf', 'anyOf', 'oneOf', 'prefixItems'] as $keyword) {
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

        foreach ([
            'not',
            'items',
            'contains',
            'contentSchema',
            'if',
            'then',
            'else',
            'propertyNames',
            'additionalProperties',
            'unevaluatedProperties',
            'unevaluatedItems',
        ] as $keyword) {
            if (array_key_exists($keyword, $schema)) {
                $this->validateSchemaNode($schema[$keyword], $location.'.'.$keyword, $issues);
            }
        }

        $this->validateRequiredProperties($schema, $location, $issues);
        $this->validateSchemaEnum($schema, $location, $issues);
    }

    /**
     * Validate a JSON Schema type declaration.
     *
     * @param  array<int, array{location: string, reason: string}>  $issues
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
                    'reason' => "Unsupported JSON Schema type '".$this->safeString($type)."'.",
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
     * @param  array<int, array{location: string, reason: string}>  $issues
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
                    'reason' => "Required property '".$this->safeString($name)."' is duplicated.",
                ];
            }

            if (! array_key_exists($name, $properties)) {
                $issues[] = [
                    'location' => $itemLocation,
                    'reason' => "Required property '".$this->safeString($name)."' is not declared in properties.",
                ];
            }

            $seen[$name] = true;
        }
    }

    /**
     * Validate JSON Schema enum structure and uniqueness.
     *
     * @param  array<string, mixed>  $schema
     * @param  array<int, array{location: string, reason: string}>  $issues
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

            foreach ($namedRoutes as $routeEntry) {
                if ($routeEntry['method'] === $expectedMethod && $routeEntry['path'] === $expectedPath) {
                    $this->validateRouteSecurityMetadata($operation, $routeEntry);
                }
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
     * Validate explicit runtime operation identifiers and required guard aliases.
     *
     * Routes without an explicit operation identifier retain compatibility with
     * legacy contract bindings and are validated by method/path/name only.
     *
     * @param  array<string, mixed>  $entry
     */
    private function validateRouteSecurityMetadata(ApiOperationContract $operation, array $entry): void
    {
        $actualOperationId = $entry['operation_id'];
        if (! is_string($actualOperationId) || $actualOperationId === '') {
            return;
        }

        if ($actualOperationId !== $operation->id) {
            $this->error('route.operation_id_mismatch', [
                'actual_operation_id' => $actualOperationId,
                'expected_operation_id' => $operation->id,
                'route_name' => $operation->routeName,
            ]);
        }

        $required = [];
        if ($operation->acceptedCredentialTypes !== []) {
            $required[] = 'api_v2_token_auth';
        }
        if ($operation->ability !== null) {
            $required[] = 'api_v2_ability:'.$operation->ability;
        }
        if ($operation->permissionMode === 'static' && $operation->permission !== null) {
            $required[] = 'api_v2_permission:'.$operation->permission;
        }
        if ($operation->idempotencyRequired) {
            $required[] = 'api_v2_idempotency';
        }
        if ($operation->requiresStepUp || $operation->actionPlanEligible) {
            $required[] = 'api_v2_risk_context';
            $required[] = 'api_v2_risk';
        }

        $missing = array_values(array_diff($required, $entry['middleware']));
        if ($missing !== []) {
            sort($missing);
            $this->error('route.middleware_mismatch', [
                'missing_middleware' => $missing,
                'operation_id' => $operation->id,
                'route_name' => $operation->routeName,
            ]);
        }
    }

    /**
     * Export normalized runtime route entries without Laravel's implicit GET HEAD alias.
     *
     * @return array<int, array{method: string, path: string, route_name: string|null, operation_id: mixed, middleware: array<int, string>}>
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

            $path = $this->normalizePath($route->uri());
            $routeName = $route->getName();
            $operationId = $route->defaults['api_operation_id'] ?? null;
            $middleware = array_values(array_unique(array_filter($route->gatherMiddleware(), 'is_string')));
            sort($middleware);
            $this->validateReportIdentity('route.path', $path);
            if ($routeName !== null) {
                $this->validateReportIdentity('route.name', $routeName);
            }

            foreach ($methods as $method) {
                $this->validateReportIdentity('route.method', $method);
                $entries[] = [
                    'method' => $method,
                    'path' => $path,
                    'route_name' => $routeName,
                    'operation_id' => $operationId,
                    'middleware' => $middleware,
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
            if (is_string($path)) {
                $this->validateReportIdentity('openapi.path', $path);
            }

            if (! is_string($path) || ! is_array($pathItem)) {
                $this->error('openapi.path_item_invalid', [
                    'path' => is_string($path) ? $path : '',
                ]);

                continue;
            }

            foreach ($pathItem as $method => $operation) {
                if (is_string($method)) {
                    $this->validateReportIdentity('openapi.method', $method);
                }

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

                $this->validateReportIdentity('openapi.operation_id', $operationId);

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
     */
    private function isMutation(string $method): bool
    {
        return ! in_array($method, ['GET', 'HEAD', 'OPTIONS'], true);
    }

    /**
     * Normalize a route or contract path for exact binding comparisons.
     */
    private function normalizePath(string $path): string
    {
        $path = preg_replace('#/+#', '/', trim($path)) ?? trim($path);
        $path = '/'.ltrim($path, '/');

        return $path === '/' ? $path : rtrim($path, '/');
    }

    /**
     * Build a JSON-safe child location for a schema map or list key.
     *
     * Invalid UTF-8 keys are represented by their deterministic hexadecimal
     * bytes so the error report never contains the invalid source bytes.
     */
    private function schemaChildLocation(string $location, int|string $key): string
    {
        if (is_int($key)) {
            return $location."[{$key}]";
        }

        if ($this->isValidUtf8($key)) {
            return $location.'.'.$key;
        }

        return $location.'.<key-hex:'.bin2hex($key).'>';
    }

    /**
     * Determine whether a string contains valid UTF-8.
     */
    private function isValidUtf8(string $value): bool
    {
        return preg_match('//u', $value) === 1;
    }

    /**
     * Return a stable JSON-safe representation of an arbitrary string.
     */
    private function safeString(string $value): string
    {
        return $this->isValidUtf8($value)
            ? $value
            : '<value-hex:'.bin2hex($value).'>';
    }

    /**
     * Add an identity validation error using only safe display values.
     */
    private function validateReportIdentity(string $field, string $value): void
    {
        if ($this->isValidUtf8($value)) {
            return;
        }

        $this->error('contract.identity_invalid', [
            'field' => $field,
            'value' => $this->safeString($value),
        ]);
    }

    /**
     * Build a stable route binding key.
     */
    private function bindingKey(string $method, string $path, string $routeName): string
    {
        return strtoupper($method).'|'.$this->normalizePath($path).'|'.$routeName;
    }

    /**
     * Append one validation error.
     *
     * @param  array<string, mixed>  $details
     */
    private function error(string $code, array $details): void
    {
        $this->errors[$code][] = $details;
    }

    /**
     * Append one non-fatal validation warning.
     *
     * @param  array<string, mixed>  $details
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
        $normalized = [];

        foreach ($value as $key => $item) {
            $safeKey = is_string($key) ? $this->safeString($key) : $key;

            if (is_array($item)) {
                $item = $this->normalizeMap($item);
            } elseif (is_string($item)) {
                $item = $this->safeString($item);
            }

            $normalized[$safeKey] = $item;
        }

        if ($normalized !== [] && ! array_is_list($normalized)) {
            ksort($normalized);
        }

        return $normalized;
    }
}
