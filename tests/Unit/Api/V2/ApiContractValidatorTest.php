<?php

namespace Wncms\Tests\Unit\Api\V2;

use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use ReflectionClass;
use Wncms\Api\V2\ApiContractRegistry;
use Wncms\Api\V2\ApiContractValidator;
use Wncms\Api\V2\Data\ApiDomainContract;
use Wncms\Api\V2\Data\ApiOperationContract;
use Wncms\Api\V2\Data\ApiSchema;
use Wncms\Tests\TestCase;

class ApiContractValidatorTest extends TestCase
{
    /**
     * Verify a consistent contract accepts canonical paths and empty JSON schemas.
     *
     * @return void
     */
    public function test_it_accepts_a_consistent_contract_and_empty_json_schema(): void
    {
        $registry = $this->registry([
            $this->operation(
                id: 'backend.posts.show',
                method: 'GET',
                path: '/api/v2/backend/posts/{id}',
                routeName: 'api.v2.backend.posts.show',
                request: $this->schema([]),
                response: $this->schema([]),
            ),
        ]);
        $routes = $this->routes([
            ['GET', 'api/v2/backend/posts/{id}', 'api.v2.backend.posts.show'],
        ]);
        $openApi = $this->openApi([
            ['backend.posts.show', 'GET', '/api/v2/backend/posts/{id}'],
        ]);

        $result = (new ApiContractValidator($registry, $routes, $openApi))->validate();

        $this->assertSame(1, $result['operation_count']);
        $this->assertTrue($result['v7_parity_eligible']);
        $this->assertSame([], $result['errors']);
        $this->assertSame([], $result['warnings']);
    }

    /**
     * Verify duplicate bindings use the normalized method, URI, and route-name tuple.
     *
     * @return void
     */
    public function test_it_rejects_duplicate_normalized_route_bindings(): void
    {
        $first = $this->operation(
            id: 'backend.posts.show',
            method: 'GET',
            path: '/api//v2/backend/posts/{id}/',
            routeName: 'api.v2.backend.posts.show',
        );
        $second = $this->operation(
            id: 'backend.posts.inspect',
            method: 'GET',
            path: '/api/v2/backend/posts/{id}',
            routeName: 'api.v2.backend.posts.show',
        );
        $registry = $this->registry([$first, $second]);

        $result = (new ApiContractValidator(
            $registry,
            $this->routes([['GET', 'api/v2/backend/posts/{id}', 'api.v2.backend.posts.show']]),
            $this->openApi([
                ['backend.posts.show', 'GET', '/api/v2/backend/posts/{id}'],
                ['backend.posts.inspect', 'GET', '/api/v2/backend/posts/{id}'],
            ]),
        ))->validate();

        $this->assertSame([
            [
                'method' => 'GET',
                'operation_ids' => ['backend.posts.inspect', 'backend.posts.show'],
                'path' => '/api/v2/backend/posts/{id}',
                'route_name' => 'api.v2.backend.posts.show',
            ],
        ], $result['errors']['route.binding_duplicate']);
    }

    /**
     * Verify method-path and route-name collisions are detected independently.
     *
     * @return void
     */
    public function test_it_rejects_method_path_and_route_name_collisions_independently(): void
    {
        $registry = $this->registry([
            $this->operation(
                'backend.posts.show',
                'GET',
                '/api/v2/backend/posts/{id}',
                'api.v2.backend.posts.show',
            ),
            $this->operation(
                'backend.posts.inspect',
                'GET',
                '/api//v2/backend/posts/{id}/',
                'api.v2.backend.posts.inspect',
            ),
            $this->operation(
                'backend.posts.archive',
                'POST',
                '/api/v2/backend/posts/{id}/archive',
                'api.v2.backend.posts.show',
            ),
        ]);

        $result = (new ApiContractValidator(
            $registry,
            $this->routes([
                ['GET', 'api/v2/backend/posts/{id}', 'api.v2.backend.posts.show'],
                ['GET', 'api/v2/backend/posts/{id}', 'api.v2.backend.posts.inspect'],
                ['POST', 'api/v2/backend/posts/{id}/archive', 'api.v2.backend.posts.show'],
            ]),
            $this->openApi([
                ['backend.posts.show', 'GET', '/api/v2/backend/posts/{id}'],
                ['backend.posts.archive', 'POST', '/api/v2/backend/posts/{id}/archive'],
            ]),
        ))->validate();

        $this->assertSame([
            [
                'method' => 'GET',
                'operation_ids' => ['backend.posts.inspect', 'backend.posts.show'],
                'path' => '/api/v2/backend/posts/{id}',
                'route_names' => [
                    'api.v2.backend.posts.inspect',
                    'api.v2.backend.posts.show',
                ],
            ],
        ], $result['errors']['route.binding_duplicate']);
        $this->assertSame([
            [
                'bindings' => [
                    [
                        'method' => 'GET',
                        'path' => '/api/v2/backend/posts/{id}',
                    ],
                    [
                        'method' => 'POST',
                        'path' => '/api/v2/backend/posts/{id}/archive',
                    ],
                ],
                'operation_ids' => ['backend.posts.archive', 'backend.posts.show'],
                'route_name' => 'api.v2.backend.posts.show',
            ],
        ], $result['errors']['route.name_duplicate']);
    }

    /**
     * Verify missing routes and exact method, path, and name mismatches are reported.
     *
     * @return void
     */
    public function test_it_reports_missing_routes_and_exact_binding_mismatches(): void
    {
        $registry = $this->registry([
            $this->operation('backend.posts.missing', 'GET', '/api/v2/backend/posts/missing', 'api.v2.backend.posts.missing'),
            $this->operation('backend.posts.index', 'GET', '/api/v2/backend/posts', 'api.v2.backend.posts.index'),
            $this->operation('backend.posts.update', 'PATCH', '/api/v2/backend/posts/{id}', 'api.v2.backend.posts.update'),
            $this->operation('backend.posts.destroy', 'DELETE', '/api/v2/backend/posts/{id}', 'api.v2.backend.posts.destroy'),
        ]);
        $routes = $this->routes([
            ['GET', 'api/v2/backend/posts', 'api.v2.backend.posts.renamed'],
            ['PUT', 'api/v2/backend/posts/{id}', 'api.v2.backend.posts.update'],
            ['DELETE', 'api/v2/backend/posts/{post}', 'api.v2.backend.posts.destroy'],
            ['GET', 'api/v2/backend/posts/renamed', 'api.v2.backend.posts.other'],
        ]);
        $openApi = $this->openApi([
            ['backend.posts.missing', 'GET', '/api/v2/backend/posts/missing'],
            ['backend.posts.index', 'GET', '/api/v2/backend/posts'],
            ['backend.posts.update', 'PATCH', '/api/v2/backend/posts/{id}'],
            ['backend.posts.destroy', 'DELETE', '/api/v2/backend/posts/{id}'],
        ]);

        $errors = (new ApiContractValidator($registry, $routes, $openApi))->validate()['errors'];

        $this->assertSame([
            ['operation_id' => 'backend.posts.missing', 'route_name' => 'api.v2.backend.posts.missing'],
        ], $errors['route.missing']);
        $this->assertSame([
            [
                'actual_route_names' => ['api.v2.backend.posts.renamed'],
                'expected_route_name' => 'api.v2.backend.posts.index',
                'method' => 'GET',
                'operation_id' => 'backend.posts.index',
                'path' => '/api/v2/backend/posts',
            ],
        ], $errors['route.name_mismatch']);
        $this->assertSame([
            [
                'actual_methods' => ['PUT'],
                'expected_method' => 'PATCH',
                'operation_id' => 'backend.posts.update',
                'route_name' => 'api.v2.backend.posts.update',
            ],
        ], $errors['route.method_mismatch']);
        $this->assertSame([
            [
                'actual_paths' => ['/api/v2/backend/posts/{post}'],
                'expected_path' => '/api/v2/backend/posts/{id}',
                'operation_id' => 'backend.posts.destroy',
                'route_name' => 'api.v2.backend.posts.destroy',
            ],
        ], $errors['route.path_mismatch']);
        $this->assertArrayHasKey('route.unregistered', $errors);
    }

    /**
     * Verify API v2 business routes require contracts while declared infrastructure is excluded.
     *
     * @return void
     */
    public function test_it_rejects_unregistered_business_routes_but_excludes_contract_infrastructure(): void
    {
        $registry = $this->registry([]);
        $routes = $this->routes([
            ['GET', 'api/v2/openapi.json', 'api.v2.openapi'],
            ['GET', 'api/v2/capabilities', 'api.v2.capabilities'],
            ['GET', 'api/v2/backend/operations/{id}', 'api.v2.backend.operations.show'],
        ]);

        $result = (new ApiContractValidator(
            $registry,
            $routes,
            $this->openApi([]),
            ['api.v2.capabilities', 'api.v2.openapi'],
        ))->validate();

        $this->assertSame([
            [
                'method' => 'GET',
                'path' => '/api/v2/backend/operations/{id}',
                'route_name' => 'api.v2.backend.operations.show',
            ],
        ], $result['errors']['route.unregistered']);
    }

    /**
     * Verify formal backend mutations require permission and legacy gaps remain warnings.
     *
     * @return void
     */
    public function test_it_enforces_formal_mutation_permissions_and_warns_for_legacy_mutations(): void
    {
        $formal = $this->operation(
            id: 'backend.posts.update',
            method: 'PATCH',
            path: '/api/v2/backend/posts/{id}',
            routeName: 'api.v2.backend.posts.update',
            permission: null,
        );
        $legacy = $this->operation(
            id: 'backend.posts.restore',
            method: 'POST',
            path: '/api/v2/backend/posts/{id}/restore',
            routeName: 'api.v2.backend.posts.restore',
            permission: null,
            implementation: 'legacy_bridge',
        );
        $registry = $this->registry([$formal, $legacy]);
        $routes = $this->routes([
            ['PATCH', 'api/v2/backend/posts/{id}', 'api.v2.backend.posts.update'],
            ['POST', 'api/v2/backend/posts/{id}/restore', 'api.v2.backend.posts.restore'],
        ]);
        $openApi = $this->openApi([
            ['backend.posts.update', 'PATCH', '/api/v2/backend/posts/{id}'],
            ['backend.posts.restore', 'POST', '/api/v2/backend/posts/{id}/restore'],
        ]);

        $result = (new ApiContractValidator($registry, $routes, $openApi))->validate();

        $this->assertSame([
            [
                'implementation' => 'domain',
                'operation_id' => 'backend.posts.update',
            ],
        ], $result['errors']['contract.permission_missing']);
        $this->assertSame([
            [
                'implementation' => 'legacy_bridge',
                'operation_id' => 'backend.posts.restore',
            ],
        ], $result['warnings']['contract.legacy_permission_missing']);
        $this->assertFalse($result['v7_parity_eligible']);
    }

    /**
     * Verify domain ownership, risk values, and implementation markers are validated.
     *
     * @return void
     */
    public function test_it_rejects_invalid_domain_risk_and_implementation_metadata(): void
    {
        $registry = new ApiContractRegistry();
        $registry->registerDomain(new ApiDomainContract('comments', 'Comments'));
        $operation = new ApiOperationContract(
            id: 'backend.posts.update',
            domain: 'comments',
            surface: 'backend',
            method: 'PATCH',
            path: '/api/v2/backend/posts/{id}',
            routeName: 'api.v2.backend.posts.update',
            permission: 'post_edit',
            ability: null,
            websiteScoped: true,
            risk: 'critical',
            implementation: 'bridge_magic',
            request: ApiSchema::object(),
            response: ApiSchema::object(),
        );
        $registry->registerOperation($operation);

        $result = (new ApiContractValidator(
            $registry,
            $this->routes([['PATCH', 'api/v2/backend/posts/{id}', 'api.v2.backend.posts.update']]),
            $this->openApi([['backend.posts.update', 'PATCH', '/api/v2/backend/posts/{id}']]),
        ))->validate();

        $this->assertSame([
            [
                'domain' => 'comments',
                'expected_prefix' => 'backend.comments.',
                'operation_id' => 'backend.posts.update',
            ],
        ], $result['errors']['contract.domain_mismatch']);
        $this->assertSame('critical', $result['errors']['contract.risk_invalid'][0]['value']);
        $this->assertSame('bridge_magic', $result['errors']['contract.implementation_invalid'][0]['value']);
    }

    /**
     * Verify malformed schemas and non-canonical path parameters fail recursively.
     *
     * @return void
     */
    public function test_it_rejects_malformed_nested_schemas_and_path_parameters(): void
    {
        $operation = $this->operation(
            id: 'backend.posts.update',
            method: 'PATCH',
            path: '/api/v2/backend/posts/{id?}/{id}',
            routeName: 'api.v2.backend.posts.update',
            request: ApiSchema::object([
                'items' => [
                    'type' => 'array',
                    'items' => ['type' => 'mystery'],
                ],
            ], ['missing']),
        );
        $registry = $this->registry([$operation]);
        $routes = $this->routes([
            ['PATCH', 'api/v2/backend/posts/{id?}/{id}', 'api.v2.backend.posts.update'],
        ]);

        $result = (new ApiContractValidator(
            $registry,
            $routes,
            $this->openApi([['backend.posts.update', 'PATCH', '/api/v2/backend/posts/{id?}/{id}']]),
        ))->validate();

        $this->assertSame([
            [
                'operation_id' => 'backend.posts.update',
                'parameter' => 'id?',
                'path' => '/api/v2/backend/posts/{id?}/{id}',
                'reason' => 'Path parameters must use non-optional identifier names.',
            ],
        ], $result['errors']['contract.path_parameter_invalid']);
        $this->assertSame([
            [
                'direction' => 'request',
                'location' => '$.properties.items.items.type',
                'operation_id' => 'backend.posts.update',
                'reason' => "Unsupported JSON Schema type 'mystery'.",
            ],
            [
                'direction' => 'request',
                'location' => '$.required[0]',
                'operation_id' => 'backend.posts.update',
                'reason' => "Required property 'missing' is not declared in properties.",
            ],
        ], $result['errors']['contract.schema_invalid']);
    }

    /**
     * Verify non-JSON-safe schema values become deterministic validation errors.
     *
     * @return void
     */
    public function test_it_reports_non_json_safe_schema_values_without_throwing(): void
    {
        $operation = $this->operation(
            id: 'backend.posts.show',
            method: 'GET',
            path: '/api/v2/backend/posts/{id}',
            routeName: 'api.v2.backend.posts.show',
            response: ApiSchema::object([
                'status' => ['enum' => ["\xB1\x31"]],
            ]),
        );

        $result = (new ApiContractValidator(
            $this->registry([$operation]),
            $this->routes([['GET', 'api/v2/backend/posts/{id}', 'api.v2.backend.posts.show']]),
            $this->openApi([['backend.posts.show', 'GET', '/api/v2/backend/posts/{id}']]),
        ))->validate();

        $this->assertSame([
            [
                'direction' => 'response',
                'location' => '$.properties.status.enum[0]',
                'operation_id' => 'backend.posts.show',
                'reason' => 'JSON Schema values must be JSON serializable.',
            ],
        ], $result['errors']['contract.schema_invalid']);
    }

    /**
     * Verify invalid UTF-8 map keys are reported with a safe stable identifier.
     *
     * @return void
     */
    public function test_it_reports_non_json_safe_schema_keys_without_leaking_invalid_bytes(): void
    {
        $operation = $this->operation(
            id: 'backend.posts.show',
            method: 'GET',
            path: '/api/v2/backend/posts/{id}',
            routeName: 'api.v2.backend.posts.show',
            response: $this->schema([
                'properties' => [
                    "\xB1\x31" => ['type' => 'string'],
                    "\xB2\x32" => ['type' => 'integer'],
                ],
                'x-numeric-values' => [0 => true, 1 => false],
            ]),
        );

        $result = (new ApiContractValidator(
            $this->registry([$operation]),
            $this->routes([['GET', 'api/v2/backend/posts/{id}', 'api.v2.backend.posts.show']]),
            $this->openApi([['backend.posts.show', 'GET', '/api/v2/backend/posts/{id}']]),
        ))->validate();

        $this->assertSame([
            [
                'direction' => 'response',
                'location' => '$.properties.<key-hex:b131>',
                'operation_id' => 'backend.posts.show',
                'reason' => 'JSON Schema map keys must be valid UTF-8.',
            ],
            [
                'direction' => 'response',
                'location' => '$.properties.<key-hex:b232>',
                'operation_id' => 'backend.posts.show',
                'reason' => 'JSON Schema map keys must be valid UTF-8.',
            ],
        ], $result['errors']['contract.schema_invalid']);
        $encoded = json_encode($result, JSON_THROW_ON_ERROR);
        $this->assertStringContainsString('<key-hex:b131>', $encoded);
        $this->assertStringNotContainsString("\xB1\x31", $encoded);
        $this->assertStringNotContainsString("\xB2\x32", $encoded);
    }

    /**
     * Verify an invalid legacy read identifier is an error and never leaks into parity output.
     *
     * @return void
     */
    public function test_it_rejects_and_sanitizes_an_invalid_legacy_read_operation_identifier(): void
    {
        $invalidId = "backend.posts.index.\xB1";
        $safeId = '<value-hex:6261636b656e642e706f7374732e696e6465782eb1>';
        $operation = $this->operation(
            id: $invalidId,
            method: 'GET',
            path: '/api/v2/backend/posts',
            routeName: 'api.v2.backend.posts.index',
            implementation: 'legacy_controller',
        );

        $result = (new ApiContractValidator(
            $this->registry([$operation]),
            $this->routes([['GET', 'api/v2/backend/posts', 'api.v2.backend.posts.index']]),
            $this->openApi([[$invalidId, 'GET', '/api/v2/backend/posts']]),
        ))->validate();

        $this->assertSame([
            [
                'field' => 'openapi.operation_id',
                'value' => $safeId,
            ],
            [
                'field' => 'operation.id',
                'operation_id' => $safeId,
                'value' => $safeId,
            ],
            [
                'field' => 'operation.registry_key',
                'operation_id' => $safeId,
                'value' => $safeId,
            ],
        ], $result['errors']['contract.identity_invalid']);
        $this->assertSame([$safeId], $result['v7_parity_ineligible_operation_ids']);
        $this->assertFalse($result['v7_parity_eligible']);

        $encoded = json_encode($result, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString($invalidId, $encoded);
    }

    /**
     * Verify domain, path, and route identities are validated before reporting.
     *
     * @return void
     */
    public function test_it_rejects_every_invalid_contract_identity_without_leaking_source_bytes(): void
    {
        $invalidDomain = "posts.\xB2";
        $invalidPath = "/api/v2/backend/posts/\xB3";
        $invalidRouteName = "api.v2.backend.posts.show.\xB4";
        $registry = new ApiContractRegistry();
        $registry->registerDomain(new ApiDomainContract($invalidDomain, 'Posts'));
        $registry->registerOperation(new ApiOperationContract(
            id: 'backend.posts.safe',
            domain: $invalidDomain,
            surface: 'backend',
            method: 'GET',
            path: $invalidPath,
            routeName: $invalidRouteName,
            permission: null,
            ability: null,
            websiteScoped: true,
            risk: 'read',
            implementation: 'legacy_controller',
            request: ApiSchema::object(),
            response: ApiSchema::object(),
        ));

        $result = (new ApiContractValidator(
            $registry,
            $this->routes([]),
            $this->openApi([]),
        ))->validate();

        $this->assertSame([
            'domain.key',
            'domain.registry_key',
            'operation.domain',
            'operation.path',
            'operation.route_name',
        ], array_column($result['errors']['contract.identity_invalid'], 'field'));

        $encoded = json_encode($result, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString($invalidDomain, $encoded);
        $this->assertStringNotContainsString($invalidPath, $encoded);
        $this->assertStringNotContainsString($invalidRouteName, $encoded);
    }

    /**
     * Verify mixed runtime identity errors remain JSON-safe and stably sorted.
     *
     * @return void
     */
    public function test_it_normalizes_and_sorts_mixed_runtime_identity_errors(): void
    {
        $invalidRoutePath = "/api/v2/runtime/\xB5";
        $invalidRouteName = "api.v2.runtime.\xB6";
        $invalidOpenApiPath = "/api/v2/openapi/\xB7";
        $invalidOperationId = "backend.runtime.\xB8";
        $registry = $this->registry([]);
        $definitions = [
            ['GET', ltrim($invalidRoutePath, '/'), $invalidRouteName],
            ['POST', 'api/v2/runtime/valid', 'api.v2.runtime.valid'],
        ];

        $first = (new ApiContractValidator(
            $registry,
            $this->routes($definitions),
            $this->openApi([[$invalidOperationId, 'GET', $invalidOpenApiPath]]),
        ))->validate();
        $second = (new ApiContractValidator(
            $registry,
            $this->routes(array_reverse($definitions)),
            $this->openApi([[$invalidOperationId, 'GET', $invalidOpenApiPath]]),
        ))->validate();

        $this->assertSame($first, $second);
        $this->assertSame([
            'openapi.operation_id',
            'openapi.path',
            'route.name',
            'route.path',
        ], array_column($first['errors']['contract.identity_invalid'], 'field'));

        $encoded = json_encode($first, JSON_THROW_ON_ERROR);
        foreach ([$invalidRoutePath, $invalidRouteName, $invalidOpenApiPath, $invalidOperationId] as $invalid) {
            $this->assertStringNotContainsString($invalid, $encoded);
        }
    }

    /**
     * Verify every 2020-12 schema-bearing keyword accepts nested boolean schemas.
     *
     * @return void
     */
    public function test_it_accepts_boolean_schemas_in_all_core_schema_bearing_keywords(): void
    {
        $schema = $this->schema([
            '$defs' => ['truthy' => true],
            'properties' => ['published' => false],
            'patternProperties' => ['^x-' => true],
            'dependentSchemas' => ['published' => false],
            'allOf' => [true],
            'anyOf' => [false],
            'oneOf' => [true, ['type' => 'object']],
            'prefixItems' => [true, false],
            'not' => false,
            'items' => true,
            'contains' => false,
            'contentSchema' => true,
            'if' => true,
            'then' => false,
            'else' => true,
            'propertyNames' => false,
            'additionalProperties' => true,
            'unevaluatedProperties' => false,
            'unevaluatedItems' => true,
            'x-safe-annotation' => [0 => 'first', 1 => 'second'],
        ]);
        $operation = $this->operation(
            id: 'backend.posts.show',
            method: 'GET',
            path: '/api/v2/backend/posts/{id}',
            routeName: 'api.v2.backend.posts.show',
            response: $schema,
        );

        $result = (new ApiContractValidator(
            $this->registry([$operation]),
            $this->routes([['GET', 'api/v2/backend/posts/{id}', 'api.v2.backend.posts.show']]),
            $this->openApi([['backend.posts.show', 'GET', '/api/v2/backend/posts/{id}']]),
        ))->validate();

        $this->assertSame([], $result['errors']);
    }

    /**
     * Verify root boolean schemas are valid through the public schema API.
     *
     * @return void
     */
    public function test_it_accepts_root_boolean_schemas_from_public_factories(): void
    {
        $operation = $this->operation(
            id: 'backend.posts.update',
            method: 'POST',
            path: '/api/v2/backend/posts',
            routeName: 'api.v2.backend.posts.update',
            request: ApiSchema::allowAll(),
            response: ApiSchema::denyAll(),
        );

        $result = (new ApiContractValidator(
            $this->registry([$operation]),
            $this->routes([['POST', 'api/v2/backend/posts', 'api.v2.backend.posts.update']]),
            $this->openApi([['backend.posts.update', 'POST', '/api/v2/backend/posts']]),
        ))->validate();

        $this->assertSame([], $result['errors']);
    }

    /**
     * Verify malformed schema-map keyword containers are rejected.
     *
     * @return void
     */
    public function test_it_rejects_malformed_schema_map_keywords(): void
    {
        $errors = $this->schemaErrors([
            '$defs' => 'invalid',
            'properties' => [true],
            'patternProperties' => false,
            'dependentSchemas' => 42,
        ]);

        $this->assertSame([
            '$.$defs',
            '$.dependentSchemas',
            '$.patternProperties',
            '$.properties',
        ], array_column($errors, 'location'));
        $this->assertSame([
            'JSON Schema $defs must be an object map.',
            'JSON Schema dependentSchemas must be an object map.',
            'JSON Schema patternProperties must be an object map.',
            'JSON Schema properties must be an object map.',
        ], array_column($errors, 'reason'));
    }

    /**
     * Verify every schema-map keyword recursively validates its child schemas.
     *
     * @return void
     */
    public function test_it_rejects_malformed_children_in_schema_map_keywords(): void
    {
        $errors = $this->schemaErrors([
            '$defs' => ['invalid' => 'value'],
            'properties' => ['invalid' => 1],
            'patternProperties' => ['^invalid$' => null],
            'dependentSchemas' => ['invalid' => 1.5],
        ]);

        $this->assertSame([
            '$.$defs.invalid',
            '$.dependentSchemas.invalid',
            '$.patternProperties.^invalid$',
            '$.properties.invalid',
        ], array_column($errors, 'location'));
        foreach ($errors as $error) {
            $this->assertSame('A JSON Schema node must be an object or boolean.', $error['reason']);
        }
    }

    /**
     * Verify malformed schema-list keyword containers are rejected.
     *
     * @return void
     */
    public function test_it_rejects_malformed_schema_list_keywords(): void
    {
        $errors = $this->schemaErrors([
            'allOf' => false,
            'anyOf' => ['type' => 'string'],
            'oneOf' => [],
            'prefixItems' => 'invalid',
        ]);

        $this->assertSame([
            '$.allOf',
            '$.anyOf',
            '$.oneOf',
            '$.prefixItems',
        ], array_column($errors, 'location'));
        $this->assertSame([
            'JSON Schema allOf must be a non-empty schema list.',
            'JSON Schema anyOf must be a non-empty schema list.',
            'JSON Schema oneOf must be a non-empty schema list.',
            'JSON Schema prefixItems must be a non-empty schema list.',
        ], array_column($errors, 'reason'));
    }

    /**
     * Verify every schema-list keyword recursively validates its child schemas.
     *
     * @return void
     */
    public function test_it_rejects_malformed_children_in_schema_list_keywords(): void
    {
        $errors = $this->schemaErrors([
            'allOf' => ['invalid'],
            'anyOf' => [1],
            'oneOf' => [null],
            'prefixItems' => [1.5],
        ]);

        $this->assertSame([
            '$.allOf[0]',
            '$.anyOf[0]',
            '$.oneOf[0]',
            '$.prefixItems[0]',
        ], array_column($errors, 'location'));
        foreach ($errors as $error) {
            $this->assertSame('A JSON Schema node must be an object or boolean.', $error['reason']);
        }
    }

    /**
     * Verify malformed single-schema keyword values are rejected recursively.
     *
     * @return void
     */
    public function test_it_rejects_malformed_single_schema_keywords(): void
    {
        $errors = $this->schemaErrors([
            'not' => 'invalid',
            'items' => 1,
            'contains' => null,
            'contentSchema' => 'invalid',
            'if' => 'invalid',
            'then' => 1.5,
            'else' => 'invalid',
            'propertyNames' => 2,
            'additionalProperties' => null,
            'unevaluatedProperties' => 'invalid',
            'unevaluatedItems' => 3,
        ]);

        $this->assertSame([
            '$.additionalProperties',
            '$.contains',
            '$.contentSchema',
            '$.else',
            '$.if',
            '$.items',
            '$.not',
            '$.propertyNames',
            '$.then',
            '$.unevaluatedItems',
            '$.unevaluatedProperties',
        ], array_column($errors, 'location'));
        foreach ($errors as $error) {
            $this->assertSame('A JSON Schema node must be an object or boolean.', $error['reason']);
        }
    }

    /**
     * Verify contentSchema recursively validates nested schema structure.
     *
     * @return void
     */
    public function test_it_recursively_rejects_a_malformed_content_schema(): void
    {
        $errors = $this->schemaErrors([
            'contentSchema' => [
                'properties' => [
                    'payload' => ['type' => 'mystery'],
                ],
            ],
        ]);

        $this->assertSame([
            [
                'direction' => 'response',
                'location' => '$.contentSchema.properties.payload.type',
                'operation_id' => 'backend.posts.show',
                'reason' => "Unsupported JSON Schema type 'mystery'.",
            ],
        ], $errors);
    }

    /**
     * Verify registry identifiers and OpenAPI operation coverage are exactly one-to-one.
     *
     * @return void
     */
    public function test_it_enforces_unique_registry_ids_and_exact_openapi_coverage(): void
    {
        $registry = $this->registry([
            $this->operation('backend.posts.show', 'GET', '/api/v2/backend/posts/{id}', 'api.v2.backend.posts.show'),
        ]);
        $reflection = new ReflectionClass($registry);
        $property = $reflection->getProperty('operations');
        $operations = $property->getValue($registry);
        $operations['duplicate.registry.key'] = $this->operation(
            'backend.posts.show',
            'GET',
            '/api/v2/backend/posts/{slug}',
            'api.v2.backend.posts.inspect',
        );
        $property->setValue($registry, $operations);

        $openApi = $this->openApi([
            ['backend.posts.show', 'GET', '/api/v2/backend/posts/{id}'],
            ['backend.posts.show', 'POST', '/api/v2/backend/posts/{id}/inspect'],
            ['backend.posts.extra', 'GET', '/api/v2/backend/posts/extra'],
        ]);

        $result = (new ApiContractValidator(
            $registry,
            $this->routes([
                ['GET', 'api/v2/backend/posts/{id}', 'api.v2.backend.posts.show'],
                ['GET', 'api/v2/backend/posts/{slug}', 'api.v2.backend.posts.inspect'],
            ]),
            $openApi,
        ))->validate();

        $this->assertSame([
            [
                'operation_id' => 'backend.posts.show',
                'registry_keys' => ['backend.posts.show', 'duplicate.registry.key'],
            ],
        ], $result['errors']['contract.operation_id_duplicate']);
        $this->assertSame([
            ['operation_id' => 'backend.posts.extra'],
        ], $result['errors']['openapi.operation_extra']);
        $this->assertSame([
            [
                'occurrences' => [
                    ['method' => 'GET', 'path' => '/api/v2/backend/posts/{id}'],
                    ['method' => 'POST', 'path' => '/api/v2/backend/posts/{id}/inspect'],
                ],
                'operation_id' => 'backend.posts.show',
            ],
        ], $result['errors']['openapi.operation_duplicate']);
    }

    /**
     * Verify error groups and details are stable regardless of registry insertion order.
     *
     * @return void
     */
    public function test_it_sorts_error_codes_and_details_deterministically(): void
    {
        $first = $this->invalidOrderingResult([
            $this->operation('backend.posts.zeta', 'POST', '/api/v2/backend/posts/zeta', 'api.v2.backend.posts.zeta', null),
            $this->operation('backend.posts.alpha', 'POST', '/api/v2/backend/posts/alpha', 'api.v2.backend.posts.alpha', null),
        ]);
        $second = $this->invalidOrderingResult([
            $this->operation('backend.posts.alpha', 'POST', '/api/v2/backend/posts/alpha', 'api.v2.backend.posts.alpha', null),
            $this->operation('backend.posts.zeta', 'POST', '/api/v2/backend/posts/zeta', 'api.v2.backend.posts.zeta', null),
        ]);

        $this->assertSame($first, $second);
        $this->assertSame(array_keys($first['errors']), array_values(array_unique(array_keys($first['errors']))));
        $sortedCodes = array_keys($first['errors']);
        sort($sortedCodes);
        $this->assertSame($sortedCodes, array_keys($first['errors']));
        $this->assertSame(
            ['backend.posts.alpha', 'backend.posts.zeta'],
            array_column($first['errors']['contract.permission_missing'], 'operation_id')
        );
    }

    /**
     * Build a validation result whose issue ordering must not depend on registration order.
     *
     * @param  array<int, \Wncms\Api\V2\Data\ApiOperationContract>  $operations
     * @return array<string, mixed>
     */
    private function invalidOrderingResult(array $operations): array
    {
        return (new ApiContractValidator(
            $this->registry($operations),
            $this->routes([]),
            $this->openApi([]),
        ))->validate();
    }

    /**
     * Return response-schema errors for one raw schema fixture.
     *
     * @param  array<string, mixed>  $schema
     * @return array<int, array<string, mixed>>
     */
    private function schemaErrors(array $schema): array
    {
        $operation = $this->operation(
            id: 'backend.posts.show',
            method: 'GET',
            path: '/api/v2/backend/posts/{id}',
            routeName: 'api.v2.backend.posts.show',
            response: $this->schema($schema),
        );

        $result = (new ApiContractValidator(
            $this->registry([$operation]),
            $this->routes([['GET', 'api/v2/backend/posts/{id}', 'api.v2.backend.posts.show']]),
            $this->openApi([['backend.posts.show', 'GET', '/api/v2/backend/posts/{id}']]),
        ))->validate();

        return $result['errors']['contract.schema_invalid'] ?? [];
    }

    /**
     * Build a registry for post-domain fixtures.
     *
     * @param  array<int, \Wncms\Api\V2\Data\ApiOperationContract>  $operations
     * @return \Wncms\Api\V2\ApiContractRegistry
     */
    private function registry(array $operations): ApiContractRegistry
    {
        $registry = new ApiContractRegistry();
        $registry->registerDomain(new ApiDomainContract('posts', 'Posts'));

        foreach ($operations as $operation) {
            $registry->registerOperation($operation);
        }

        return $registry;
    }

    /**
     * Build one operation fixture.
     *
     * @param  string  $id
     * @param  string  $method
     * @param  string  $path
     * @param  string  $routeName
     * @param  string|null  $permission
     * @param  string  $implementation
     * @param  \Wncms\Api\V2\Data\ApiSchema|null  $request
     * @param  \Wncms\Api\V2\Data\ApiSchema|null  $response
     * @return \Wncms\Api\V2\Data\ApiOperationContract
     */
    private function operation(
        string $id,
        string $method,
        string $path,
        string $routeName,
        ?string $permission = 'post_show',
        string $implementation = 'domain',
        ?ApiSchema $request = null,
        ?ApiSchema $response = null,
    ): ApiOperationContract {
        return new ApiOperationContract(
            id: $id,
            domain: 'posts',
            surface: 'backend',
            method: $method,
            path: $path,
            routeName: $routeName,
            permission: $permission,
            ability: null,
            websiteScoped: true,
            risk: $method === 'GET' ? 'read' : 'write',
            implementation: $implementation,
            request: $request ?? ApiSchema::object(),
            response: $response ?? ApiSchema::object(),
        );
    }

    /**
     * Build a schema with an exact raw JSON Schema map, including an empty schema.
     *
     * @param  array<string, mixed>  $schema
     * @return \Wncms\Api\V2\Data\ApiSchema
     */
    private function schema(array $schema): ApiSchema
    {
        $reflection = new ReflectionClass(ApiSchema::class);
        $instance = $reflection->newInstanceWithoutConstructor();
        $reflection->getProperty('schema')->setValue($instance, $schema);

        return $instance;
    }

    /**
     * Build a Laravel route collection from literal route tuples.
     *
     * @param  array<int, array{string, string, string}>  $definitions
     * @return \Illuminate\Routing\RouteCollection
     */
    private function routes(array $definitions): RouteCollection
    {
        $routes = new RouteCollection();

        foreach ($definitions as [$method, $uri, $name]) {
            $methods = $method === 'GET' ? ['GET', 'HEAD'] : [$method];
            $route = new Route($methods, $uri, static fn (): null => null);
            $route->name($name);
            $routes->add($route);
        }

        return $routes;
    }

    /**
     * Build a minimal literal OpenAPI document from operation tuples.
     *
     * @param  array<int, array{string, string, string}>  $definitions
     * @return array<string, mixed>
     */
    private function openApi(array $definitions): array
    {
        $paths = [];

        foreach ($definitions as [$operationId, $method, $path]) {
            $paths[$path][strtolower($method)] = ['operationId' => $operationId];
        }

        return [
            'openapi' => '3.1.0',
            'paths' => $paths,
        ];
    }
}
