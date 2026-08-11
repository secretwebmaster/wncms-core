<?php

namespace Wncms\Tests\Unit\Api\V2;

use PHPUnit\Framework\Attributes\DataProvider;
use Wncms\Api\V2\ApiContractRegistry;
use Wncms\Api\V2\Data\ApiDomainContract;
use Wncms\Api\V2\Data\ApiOperationContract;
use Wncms\Api\V2\Data\ApiSchema;
use Wncms\Api\V2\Exceptions\ApiContractException;
use Wncms\Tests\TestCase;

class ApiContractRegistryTest extends TestCase
{
    public function test_it_returns_domains_and_operations_in_stable_identifier_order(): void
    {
        $registry = new ApiContractRegistry;
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
        $registry = new ApiContractRegistry;
        $registry->registerDomain(new ApiDomainContract('links', 'Links'));

        $this->expectException(ApiContractException::class);
        $registry->registerDomain(new ApiDomainContract('links', 'Other Links'));
    }

    public function test_it_rejects_operations_for_unregistered_domains(): void
    {
        $registry = new ApiContractRegistry;

        $this->expectException(ApiContractException::class);
        $registry->registerOperation($this->operation('backend.links.index', 'links'));
    }

    public function test_it_rejects_duplicate_operation_identifiers(): void
    {
        $registry = new ApiContractRegistry;
        $registry->registerDomain(new ApiDomainContract('links', 'Links'));
        $registry->registerOperation($this->operation('backend.links.index', 'links'));

        $this->expectException(ApiContractException::class);
        $registry->registerOperation($this->operation('backend.links.index', 'links'));
    }

    public function test_it_exports_an_immutable_array_snapshot(): void
    {
        $registry = new ApiContractRegistry;
        $registry->registerDomain(new ApiDomainContract('links', 'Links'));
        $registry->registerOperation($this->operation('backend.links.index', 'links'));

        $export = $registry->toArray();
        $export['domains']['links']['label'] = 'Changed';
        $export['operations']['backend.links.index']['filters'][] = 'changed';
        $export['operations']['backend.links.index']['request_schema']['properties']['name']['type'] = 'integer';

        $freshExport = $registry->toArray();

        $this->assertSame('Links', $freshExport['domains']['links']['label']);
        $this->assertSame(['status'], $freshExport['operations']['backend.links.index']['filters']);
        $this->assertSame('string', $freshExport['operations']['backend.links.index']['request_schema']['properties']['name']['type']);
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
        $registry = new ApiContractRegistry;
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
            'system destructive legacy controller' => ['system', 'DELETE', 'destructive', 'legacy_controller'],
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
}
