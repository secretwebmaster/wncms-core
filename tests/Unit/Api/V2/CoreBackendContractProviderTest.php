<?php

namespace Wncms\Tests\Unit\Api\V2;

use Wncms\Api\V2\ApiContractRegistry;
use Wncms\Api\V2\Providers\CoreBackendContractProvider;
use Wncms\Database\Seeders\RolesSeeder;
use Wncms\Tests\TestCase;

class CoreBackendContractProviderTest extends TestCase
{
    /**
     * Verify operation inspection and cancellation are formal backend contracts.
     *
     * @return void
     */
    public function test_it_registers_operation_resource_contracts_with_runtime_authorization_metadata(): void
    {
        $registry = new ApiContractRegistry();

        (new CoreBackendContractProvider())->register($registry);

        $show = $registry->operation('backend.operations.show');
        $cancel = $registry->operation('backend.operations.cancel');

        $this->assertSame('Operations', $registry->domains()['operations']->label);
        $this->assertNotNull($show);
        $this->assertSame('GET', $show->method);
        $this->assertSame('/api/v2/backend/operations/{id}', $show->path);
        $this->assertSame('api.v2.backend.operations.show', $show->routeName);
        $this->assertNull($show->permission);
        $this->assertFalse($show->websiteScoped);
        $this->assertSame('read', $show->risk);
        $this->assertSame('domain', $show->implementation);

        $this->assertNotNull($cancel);
        $this->assertSame('POST', $cancel->method);
        $this->assertSame('/api/v2/backend/operations/{id}/cancel', $cancel->path);
        $this->assertSame('api.v2.backend.operations.cancel', $cancel->routeName);
        $this->assertSame('operation_cancel', $cancel->permission);
        $this->assertFalse($cancel->websiteScoped);
        $this->assertSame('destructive', $cancel->risk);
        $this->assertSame('domain', $cancel->implementation);
        $this->assertTrue($cancel->idempotent);
        $this->assertContains('operation_cancel', (new RolesSeeder())->special_permissions());

        $response = $cancel->response->toArray();
        $this->assertSame('object', $response['type']);
        $this->assertSame('string', $response['properties']['id']['type']);
        $this->assertSame(
            ['queued', 'running', 'succeeded', 'failed', 'cancelled'],
            $response['properties']['status']['enum']
        );
        $this->assertSame(
            ['object', 'array', 'string', 'number', 'boolean', 'null'],
            array_column($response['properties']['result']['oneOf'], 'type')
        );
        $this->assertSame(
            ['object', 'array', 'null'],
            array_column($response['properties']['error']['oneOf'], 'type')
        );
    }
}
