<?php

namespace Wncms\Tests\Unit\Api\V2;

use InvalidArgumentException;
use Wncms\Api\V2\ApiContractRegistry;
use Wncms\Api\V2\LegacyOperationSecurity;
use Wncms\Api\V2\Providers\LegacyBackendContractProvider;
use Wncms\Http\Controllers\Api\V2\Backend\ResourceController;
use Wncms\Tests\TestCase;

class LegacyBackendContractProviderTest extends TestCase
{
    /**
     * Register every enabled resource route and configured bridge action exactly once.
     *
     * @return void
     */
    public function test_it_adapts_every_enabled_backend_route_into_a_contract_operation(): void
    {
        $registry = new ApiContractRegistry;

        (new LegacyBackendContractProvider)->register($registry);

        $resourceMethods = [
            'index' => ['GET', '/api/v2/backend/%s'],
            'show' => ['GET', '/api/v2/backend/%s/{id}'],
            'store' => ['POST', '/api/v2/backend/%s'],
            'update' => ['PATCH', '/api/v2/backend/%s/{id}'],
            'destroy' => ['DELETE', '/api/v2/backend/%s/{id}'],
            'bulk_delete' => ['POST', '/api/v2/backend/%s/bulk_delete'],
        ];
        $expectedOperationIds = [];
        $bridgeOperationIds = array_map(
            fn (array $action): string => "backend.{$action['name']}",
            config('wncms-backend-api-v2.actions', []),
        );
        $referenceDomain = config('wncms-backend-api-v2.coverage.reference_domain');

        foreach (config('wncms-backend-api-v2.resources', []) as $resource => $resourceConfig) {
            $enabledActions = $resourceConfig['enabled_actions'] ?? array_keys($resourceMethods);

            foreach ($enabledActions as $action) {
                if ($action === 'bulk_delete' && ($resourceConfig['enable_bulk_delete'] ?? true) !== true) {
                    continue;
                }

                [$method, $path] = $resourceMethods[$action];
                $operationId = "backend.{$resource}.{$action}";
                $operation = $registry->operation($operationId);

                $this->assertNotNull($operation, "Missing resource operation {$operationId}.");
                $this->assertSame($method, $operation->method);
                $this->assertSame(sprintf($path, $resource), $operation->path);
                $this->assertSame("api.v2.backend.{$resource}.{$action}", $operation->routeName);
                $this->assertSame($resourceConfig['permissions'][$action] ?? null, $operation->permission);
                $this->assertSame('static', $operation->permissionMode);
                $this->assertSame(LegacyOperationSecurity::resourceAbility($resource, $action), $operation->ability);
                $this->assertTrue($operation->websiteScoped);

                if (! in_array($operationId, $bridgeOperationIds, true)) {
                    $expectedImplementation = $resource === $referenceDomain
                        ? 'domain'
                        : (($resourceConfig['controller'] ?? ResourceController::class) === ResourceController::class
                            ? 'legacy_resource'
                            : 'legacy_controller');

                    $this->assertSame($expectedImplementation, $operation->implementation);
                }

                $expectedOperationIds[] = $operationId;
            }
        }

        foreach (config('wncms-backend-api-v2.actions', []) as $action) {
            $operationId = "backend.{$action['name']}";
            $operation = $registry->operation($operationId);

            $this->assertNotNull($operation, "Missing bridge operation {$operationId}.");
            $this->assertSame(strtoupper($action['method']), $operation->method);
            $this->assertSame('/api/v2/backend/' . $action['uri'], $operation->path);
            $this->assertSame("api.v2.backend.{$action['name']}", $operation->routeName);
            $this->assertSame($action['permission_template'] ?? $action['permission'] ?? null, $operation->permission);
            $this->assertSame(isset($action['permission_template']) ? 'model_template' : 'static', $operation->permissionMode);
            $domain = explode('.', (string) $action['name'], 2)[0];
            $expectedAbility = $domain.'.'.(strtoupper((string) $action['method']) === 'GET' ? 'read' : 'write');
            $this->assertSame($expectedAbility, $operation->ability);
            $this->assertTrue($operation->websiteScoped);
            $this->assertSame('legacy_bridge', $operation->implementation);
            $expectedOperationIds[] = $operationId;
        }

        $expectedOperationIds = array_values(array_unique($expectedOperationIds));
        $operations = $registry->operations();

        $this->assertCount(count($expectedOperationIds), $operations);
        $this->assertSame($expectedOperationIds, array_values(array_intersect($expectedOperationIds, array_keys($operations))));
    }

    /**
     * Verify an enabled resource operation without a permission is never published.
     *
     * @return void
     */
    public function test_it_rejects_a_resource_operation_without_a_permission(): void
    {
        config(['wncms-backend-api-v2.resources.unsafe_widgets' => [
            'model_key' => 'widget',
            'enabled_actions' => ['index'],
            'permissions' => [],
        ]]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('unsafe_widgets.index');

        (new LegacyBackendContractProvider)->register(new ApiContractRegistry);
    }

    /**
     * Verify a bridge operation without static or validated dynamic permission is never published.
     *
     * @return void
     */
    public function test_it_rejects_a_bridge_operation_without_a_permission(): void
    {
        $actions = config('wncms-backend-api-v2.actions', []);
        $actions[] = [
            'name' => 'unsafe.run',
            'method' => 'post',
            'uri' => 'unsafe/run',
        ];
        config(['wncms-backend-api-v2.actions' => $actions]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('name and permission');

        (new LegacyBackendContractProvider)->register(new ApiContractRegistry);
    }
}
