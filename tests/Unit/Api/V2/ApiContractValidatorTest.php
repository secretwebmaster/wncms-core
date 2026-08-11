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
