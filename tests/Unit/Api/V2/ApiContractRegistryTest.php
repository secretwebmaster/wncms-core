<?php

namespace Wncms\Tests\Unit\Api\V2;

use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use Wncms\Api\V2\ApiContractRegistry;
use Wncms\Api\V2\CapabilityResolver;
use Wncms\Api\V2\Data\ApiDomainContract;
use Wncms\Api\V2\Data\ApiOperationContract;
use Wncms\Api\V2\Data\ApiSchema;
use Wncms\Api\V2\Exceptions\ApiContractException;
use Wncms\Api\V2\ModelPermissionResolver;
use Wncms\Api\V2\OpenApiDocumentBuilder;
use Wncms\Models\User;
use Wncms\Tests\TestCase;

class ApiContractRegistryTest extends TestCase
{
    public function test_it_returns_domains_and_operations_in_stable_identifier_order(): void
    {
        $registry = new ApiContractRegistry();
        $registry->registerDomain(new ApiDomainContract('posts', 'Posts'));
        $registry->registerDomain(new ApiDomainContract('links', 'Links'));
        $registry->registerOperation($this->operation('backend.posts.index', 'posts'));
        $registry->registerOperation($this->operation('backend.links.index', 'links'));

        $this->assertSame(['links', 'posts'], array_keys($registry->domains()));
        $this->assertSame(['backend.links.index', 'backend.posts.index'], array_keys($registry->operations()));
        $this->assertSame('backend.links.index', $registry->operation('backend.links.index')?->id);
        $this->assertNull($registry->operation('backend.missing.index'));
    }

    public function test_it_rejects_duplicate_domain_identifiers(): void
    {
        $registry = new ApiContractRegistry();
        $registry->registerDomain(new ApiDomainContract('links', 'Links'));

        $this->expectException(ApiContractException::class);
        $registry->registerDomain(new ApiDomainContract('links', 'Other Links'));
    }

    public function test_it_rejects_operations_for_unregistered_domains(): void
    {
        $registry = new ApiContractRegistry();

        $this->expectException(ApiContractException::class);
        $registry->registerOperation($this->operation('backend.links.index', 'links'));
    }

    public function test_it_rejects_duplicate_operation_identifiers(): void
    {
        $registry = new ApiContractRegistry();
        $registry->registerDomain(new ApiDomainContract('links', 'Links'));
        $registry->registerOperation($this->operation('backend.links.index', 'links'));

        $this->expectException(ApiContractException::class);
        $registry->registerOperation($this->operation('backend.links.index', 'links'));
    }

    /**
     * Verify arbitrary providers cannot publish unsupported permission contracts.
     *
     * @param  string  $id
     * @param  string|null  $permission
     * @param  string  $permissionMode
     * @return void
     */
    #[DataProvider('invalidPermissionContracts')]
    public function test_it_rejects_invalid_permission_contracts_before_capability_or_openapi_publication(
        string $id,
        ?string $permission,
        string $permissionMode,
    ): void {
        $registry = new ApiContractRegistry();
        $registry->registerDomain(new ApiDomainContract('models', 'Models'));

        try {
            $registry->registerOperation($this->permissionOperation($id, $permission, $permissionMode));
            $this->fail('Invalid permission contracts must be rejected during registry registration.');
        } catch (ApiContractException) {
            $this->assertNull($registry->operation($id));
        }

        $capabilities = (new CapabilityResolver($registry, new ModelPermissionResolver()))->resolve(new User());
        $this->assertSame([], get_object_vars($capabilities['domains']['models']['operations']));
        $this->assertSame([], (new OpenApiDocumentBuilder($registry))->build()['paths']);
    }

    /**
     * Provide invalid provider-supplied permission contracts.
     *
     * @return array<string, array{string, string|null, string}>
     */
    public static function invalidPermissionContracts(): array
    {
        return [
            'unapproved model-template operation' => ['backend.models.inspect', '{model}_edit', 'model_template'],
            'update with bulk-delete template' => ['backend.models.update', '{model}_bulk_delete', 'model_template'],
            'bulk delete with edit template' => ['backend.models.bulk_delete', '{model}_edit', 'model_template'],
            'bulk force delete with edit template' => ['backend.models.bulk_force_delete', '{model}_edit', 'model_template'],
            'model template without a permission' => ['backend.models.update', null, 'model_template'],
            'model update with a static permission' => ['backend.models.update', 'setting_edit', 'static'],
            'static permission containing a model placeholder' => ['backend.models.inspect', '{model}_inspect', 'static'],
            'unknown permission mode' => ['backend.models.inspect', 'model_inspect', 'dynamic'],
        ];
    }

    /**
     * Verify the prescribed templates and public static contracts remain supported.
     *
     * @param  string  $id
     * @param  string|null  $permission
     * @param  string  $permissionMode
     * @return void
     */
    #[DataProvider('validPermissionContracts')]
    public function test_it_registers_supported_permission_contracts(
        string $id,
        ?string $permission,
        string $permissionMode,
    ): void {
        $registry = new ApiContractRegistry();
        $registry->registerDomain(new ApiDomainContract('models', 'Models'));
        $registry->registerOperation($this->permissionOperation($id, $permission, $permissionMode));

        $this->assertSame($permission, $registry->operation($id)?->permission);
        $this->assertSame($permissionMode, $registry->operation($id)?->permissionMode);
    }

    /**
     * Provide valid permission contracts.
     *
     * @return array<string, array{string, string|null, string}>
     */
    public static function validPermissionContracts(): array
    {
        return [
            'model update template' => ['backend.models.update', '{model}_edit', 'model_template'],
            'model bulk-delete template' => ['backend.models.bulk_delete', '{model}_bulk_delete', 'model_template'],
            'model bulk-force-delete template' => ['backend.models.bulk_force_delete', '{model}_bulk_delete', 'model_template'],
            'static permission' => ['backend.models.inspect', 'model_inspect', 'static'],
            'public operation without permission' => ['backend.models.public', null, 'static'],
        ];
    }

    public function test_it_exports_an_immutable_array_snapshot(): void
    {
        $registry = new ApiContractRegistry();
        $registry->registerDomain(new ApiDomainContract('links', 'Links'));
        $registry->registerOperation($this->operation('backend.links.index', 'links'));

        $export = $registry->toArray();
        $export['domains']['links']['label'] = 'Changed';
        $export['operations']['backend.links.index']['filters'][] = 'changed';
        $export['operations']['backend.links.index']['request_schema']['properties']->name['type'] = 'integer';

        $freshExport = $registry->toArray();

        $this->assertSame('Links', $freshExport['domains']['links']['label']);
        $this->assertSame(['status'], $freshExport['operations']['backend.links.index']['filters']);
        $this->assertSame('string', $freshExport['operations']['backend.links.index']['request_schema']['properties']->name['type']);
    }

    public function test_it_exports_schema_factories_as_json_schema_arrays(): void
    {
        $this->assertSame([
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
            ],
            'required' => ['name'],
        ], ApiSchema::object(['name' => ApiSchema::string()->toArray()], ['name'])->toArray());
        $this->assertSame([
            'type' => 'array',
            'items' => ['type' => 'integer'],
        ], ApiSchema::arrayOf(ApiSchema::integer())->toArray());
        $this->assertSame([
            'type' => 'string',
            'enum' => ['draft', 'published'],
        ], ApiSchema::string(['draft', 'published'])->toArray());
        $this->assertSame(['type' => 'boolean'], ApiSchema::boolean()->toArray());
    }

    /**
     * Verify empty property maps retain object semantics during JSON serialization.
     *
     * @return void
     */
    public function test_empty_object_schema_has_an_object_property_map_when_json_encoded(): void
    {
        $this->assertSame(
            '{"type":"object","properties":{}}',
            json_encode(ApiSchema::object(), JSON_THROW_ON_ERROR)
        );
        $this->assertSame(
            ['type' => 'object', 'properties' => []],
            ApiSchema::object()->toArray()
        );
    }

    /**
     * Verify nested empty object schemas retain object property maps.
     *
     * @return void
     */
    public function test_nested_empty_object_schema_has_an_object_property_map_when_json_encoded(): void
    {
        $this->assertSame(
            '{"type":"array","items":{"type":"object","properties":{}}}',
            json_encode(ApiSchema::arrayOf(ApiSchema::object()), JSON_THROW_ON_ERROR)
        );
    }

    /**
     * Verify schema-valued maps and schemas use objects while schema lists remain arrays.
     */
    public function test_json_schema_wire_preserves_empty_schema_maps_and_list_keywords_recursively(): void
    {
        $schema = $this->schema([
            '$defs' => ['empty' => []],
            'properties' => [
                'free' => [],
                'nested' => ['properties' => []],
            ],
            'patternProperties' => [],
            'dependentSchemas' => [],
            'items' => [],
            'not' => [],
            'contains' => [],
            'if' => [],
            'then' => [],
            'else' => [],
            'propertyNames' => [],
            'additionalProperties' => [],
            'unevaluatedProperties' => [],
            'unevaluatedItems' => [],
            'contentSchema' => [],
            'allOf' => [[], true],
            'anyOf' => [],
            'oneOf' => [false, []],
            'prefixItems' => [],
            'required' => [],
            'enum' => [],
            'default' => [],
            'examples' => [],
        ]);
        $wire = json_decode(json_encode($schema, JSON_THROW_ON_ERROR));

        $this->assertIsObject($wire->{'$defs'});
        $this->assertIsObject($wire->{'$defs'}->empty);
        $this->assertIsObject($wire->properties);
        $this->assertIsObject($wire->properties->free);
        $this->assertIsObject($wire->properties->nested->properties);
        $this->assertIsObject($wire->patternProperties);
        $this->assertIsObject($wire->dependentSchemas);

        foreach ([
            'items',
            'not',
            'contains',
            'if',
            'then',
            'else',
            'propertyNames',
            'additionalProperties',
            'unevaluatedProperties',
            'unevaluatedItems',
            'contentSchema',
        ] as $keyword) {
            $this->assertIsObject($wire->{$keyword}, $keyword);
        }

        foreach (['allOf', 'anyOf', 'oneOf', 'prefixItems'] as $keyword) {
            $this->assertIsArray($wire->{$keyword}, $keyword);
        }
        $this->assertIsObject($wire->allOf[0]);
        $this->assertTrue($wire->allOf[1]);
        $this->assertFalse($wire->oneOf[0]);
        $this->assertIsObject($wire->oneOf[1]);

        foreach (['required', 'enum', 'default', 'examples'] as $keyword) {
            $this->assertIsArray($wire->{$keyword}, $keyword);
        }
        $this->assertSame($schema->toArray(), $this->schema($schema->toArray())->toArray());
        $this->assertSame('{}', json_encode($this->schema([]), JSON_THROW_ON_ERROR));
    }

    /**
     * Verify root boolean schemas and boolean array items retain wire semantics.
     *
     * @return void
     */
    public function test_it_exports_root_boolean_schema_factories_without_changing_boolean_type_schema(): void
    {
        $this->assertTrue(ApiSchema::allowAll()->toArray());
        $this->assertFalse(ApiSchema::denyAll()->toArray());
        $this->assertSame('true', json_encode(ApiSchema::allowAll(), JSON_THROW_ON_ERROR));
        $this->assertSame('false', json_encode(ApiSchema::denyAll(), JSON_THROW_ON_ERROR));
        $this->assertSame([
            'type' => 'array',
            'items' => false,
        ], ApiSchema::arrayOf(ApiSchema::denyAll())->toArray());
        $this->assertSame(['type' => 'boolean'], ApiSchema::boolean()->toArray());
    }

    /**
     * Verify registry JSON preserves root boolean request and response schemas.
     *
     * @return void
     */
    public function test_registry_json_wire_preserves_root_boolean_schemas(): void
    {
        $registry = new ApiContractRegistry();
        $registry->registerDomain(new ApiDomainContract('links', 'Links'));
        $registry->registerOperation(new ApiOperationContract(
            id: 'backend.links.boolean_schema',
            domain: 'links',
            surface: 'backend',
            method: 'POST',
            path: '/api/v2/backend/links/boolean-schema',
            routeName: 'api.v2.backend.links.boolean_schema',
            permission: 'link_create',
            ability: null,
            websiteScoped: true,
            risk: 'write',
            implementation: 'domain',
            request: ApiSchema::allowAll(),
            response: ApiSchema::denyAll(),
        ));

        $wire = json_decode(json_encode($registry->toArray(), JSON_THROW_ON_ERROR));
        $operation = $wire->operations->{'backend.links.boolean_schema'};

        $this->assertTrue($operation->request_schema);
        $this->assertFalse($operation->response_schema);
    }

    /**
     * Verify registry array exports use schema wire values before JSON encoding.
     *
     * @return void
     */
    public function test_registry_array_export_uses_recursive_schema_wire_values(): void
    {
        $registry = new ApiContractRegistry();
        $registry->registerDomain(new ApiDomainContract('schemas', 'Schemas'));
        $registry->registerOperation(new ApiOperationContract(
            id: 'frontend.schemas.inspect',
            domain: 'schemas',
            surface: 'frontend',
            method: 'GET',
            path: '/api/v2/schemas',
            routeName: 'api.v2.schemas.inspect',
            permission: null,
            ability: null,
            websiteScoped: false,
            risk: 'read',
            implementation: 'domain',
            request: $this->schema([
                'properties' => [
                    'free' => [],
                    'choices' => ['oneOf' => [[], true]],
                ],
            ]),
            response: $this->schema([]),
        ));

        $operation = $registry->toArray()['operations']['frontend.schemas.inspect'];

        $this->assertIsObject($operation['request_schema']['properties']);
        $this->assertIsObject($operation['request_schema']['properties']->free);
        $this->assertIsArray($operation['request_schema']['properties']->choices['oneOf']);
        $this->assertIsObject($operation['request_schema']['properties']->choices['oneOf'][0]);
        $this->assertTrue($operation['request_schema']['properties']->choices['oneOf'][1]);
        $this->assertIsObject($operation['response_schema']);
        $this->assertSame(
            '{"properties":{"free":{},"choices":{"oneOf":[{},true]}}}',
            json_encode($operation['request_schema'], JSON_THROW_ON_ERROR)
        );
        $this->assertSame('{}', json_encode($operation['response_schema'], JSON_THROW_ON_ERROR));
    }

    public function test_it_exports_empty_optional_operation_values_by_default(): void
    {
        $operation = new ApiOperationContract(
            id: 'backend.links.index',
            domain: 'links',
            surface: 'backend',
            method: 'GET',
            path: '/api/v2/backend/links',
            routeName: 'api.v2.backend.links.index',
            permission: 'link_index',
            ability: 'links:read',
            websiteScoped: true,
            risk: 'read',
            implementation: 'domain',
            request: ApiSchema::object(),
            response: ApiSchema::object(),
        );

        $this->assertSame([], $operation->filters);
        $this->assertSame([], $operation->sorts);
        $this->assertSame([], $operation->includes);
        $this->assertSame([], $operation->fields);
        $this->assertFalse($operation->idempotent);
    }

    #[DataProvider('validOperationValues')]
    public function test_it_registers_operations_for_supported_contract_values(
        string $surface,
        string $method,
        string $risk,
        string $implementation,
    ): void {
        $registry = new ApiContractRegistry();
        $registry->registerDomain(new ApiDomainContract('links', 'Links'));
        $registry->registerOperation($this->operation(
            id: "{$surface}.links.{$implementation}",
            domain: 'links',
            surface: $surface,
            method: $method,
            risk: $risk,
            implementation: $implementation,
        ));

        $this->assertSame($surface, $registry->operation("{$surface}.links.{$implementation}")?->surface);
        $this->assertSame($method, $registry->operation("{$surface}.links.{$implementation}")?->method);
        $this->assertSame($risk, $registry->operation("{$surface}.links.{$implementation}")?->risk);
        $this->assertSame($implementation, $registry->operation("{$surface}.links.{$implementation}")?->implementation);
    }

    /**
     * Provide the supported contract value combinations.
     *
     * @return array<string, array{string, string, string, string}>
     */
    public static function validOperationValues(): array
    {
        return [
            'frontend read domain' => ['frontend', 'GET', 'read', 'domain'],
            'backend write legacy resource' => ['backend', 'POST', 'write', 'legacy_resource'],
            'frontend destructive legacy controller' => ['frontend', 'DELETE', 'destructive', 'legacy_controller'],
            'backend write legacy bridge' => ['backend', 'PATCH', 'write', 'legacy_bridge'],
        ];
    }

    /**
     * Create an operation fixture for the registry contract.
     *
     * @param  string  $id
     * @param  string  $domain
     * @param  string  $surface
     * @param  string  $method
     * @param  string  $risk
     * @param  string  $implementation
     * @return \Wncms\Api\V2\Data\ApiOperationContract
     */
    private function operation(
        string $id,
        string $domain,
        string $surface = 'backend',
        string $method = 'GET',
        string $risk = 'read',
        string $implementation = 'domain',
    ): ApiOperationContract {
        return new ApiOperationContract(
            id: $id,
            domain: $domain,
            surface: $surface,
            method: $method,
            path: '/api/v2/backend/links',
            routeName: 'api.v2.backend.links.index',
            permission: 'link_index',
            ability: 'links:read',
            websiteScoped: true,
            risk: $risk,
            implementation: $implementation,
            request: ApiSchema::object(['name' => ApiSchema::string()->toArray()]),
            response: ApiSchema::object(),
            filters: ['status'],
            sorts: ['id', 'created_at'],
            includes: ['tags', 'websites'],
            fields: ['id', 'name', 'url', 'status'],
        );
    }

    /**
     * Create an operation with an explicit permission contract.
     *
     * @param  string  $id
     * @param  string|null  $permission
     * @param  string  $permissionMode
     * @return \Wncms\Api\V2\Data\ApiOperationContract
     */
    private function permissionOperation(
        string $id,
        ?string $permission,
        string $permissionMode,
    ): ApiOperationContract {
        $action = substr($id, strrpos($id, '.') + 1);

        return new ApiOperationContract(
            id: $id,
            domain: 'models',
            surface: 'backend',
            method: 'POST',
            path: '/api/v2/backend/models/'.$action,
            routeName: 'api.v2.backend.models.'.$action,
            permission: $permission,
            ability: 'models.write',
            websiteScoped: true,
            risk: 'write',
            implementation: 'legacy_bridge',
            request: ApiSchema::object(),
            response: ApiSchema::object(),
            permissionMode: $permissionMode,
        );
    }

    /**
     * Build a schema with an exact raw JSON Schema value.
     *
     * @param  array<string, mixed>  $schema
     */
    private function schema(array $schema): ApiSchema
    {
        $reflection = new ReflectionClass(ApiSchema::class);
        $instance = $reflection->newInstanceWithoutConstructor();
        $reflection->getProperty('schema')->setValue($instance, $schema);

        return $instance;
    }
}
