<?php

namespace Wncms\Api\V2\Providers;

use Illuminate\Support\Str;
use Wncms\Api\V2\ApiContractRegistry;
use Wncms\Api\V2\LegacyOperationSecurity;
use Wncms\Api\V2\Contracts\ApiContractProvider;
use Wncms\Api\V2\Data\ApiDomainContract;
use Wncms\Api\V2\Data\ApiOperationContract;
use Wncms\Api\V2\Data\ApiSchema;
use Wncms\Http\Controllers\Api\V2\Backend\ResourceController;

class LegacyBackendContractProvider implements ApiContractProvider
{
    /**
     * Register configured backend resource and bridge contracts.
     *
     * @param  \Wncms\Api\V2\ApiContractRegistry  $registry
     * @return void
     */
    public function register(ApiContractRegistry $registry): void
    {
        $actions = config('wncms-backend-api-v2.actions', []);
        $bridgeOperationIds = array_map(
            fn (array $action): string => 'backend.' . ($action['name'] ?? ''),
            $actions,
        );

        $this->registerResources($registry, config('wncms-backend-api-v2.resources', []), $bridgeOperationIds);
        $this->registerActions($registry, $actions);
    }

    /**
     * Register configured backend resource contracts.
     *
     * @param  \Wncms\Api\V2\ApiContractRegistry  $registry
     * @param  array<string, array<string, mixed>>  $resources
     * @param  array<int, string>  $bridgeOperationIds
     * @return void
     */
    protected function registerResources(ApiContractRegistry $registry, array $resources, array $bridgeOperationIds): void
    {
        $resourceMethods = [
            'index' => ['GET', '/api/v2/backend/%s'],
            'show' => ['GET', '/api/v2/backend/%s/{id}'],
            'store' => ['POST', '/api/v2/backend/%s'],
            'update' => ['PATCH', '/api/v2/backend/%s/{id}'],
            'destroy' => ['DELETE', '/api/v2/backend/%s/{id}'],
            'bulk_delete' => ['POST', '/api/v2/backend/%s/bulk_delete'],
        ];
        $referenceDomain = config('wncms-backend-api-v2.coverage.reference_domain');

        foreach ($resources as $resource => $resourceConfig) {
            $this->registerDomain($registry, $resource);
            $enabledActions = $resourceConfig['enabled_actions'] ?? array_keys($resourceMethods);

            foreach ($enabledActions as $action) {
                if ($action === 'bulk_delete' && ($resourceConfig['enable_bulk_delete'] ?? true) !== true) {
                    continue;
                }

                [$method, $path] = $resourceMethods[$action];
                $operationId = "backend.{$resource}.{$action}";

                if (in_array($operationId, $bridgeOperationIds, true)) {
                    continue;
                }

                $registry->registerOperation(new ApiOperationContract(
                    id: $operationId,
                    domain: $resource,
                    surface: 'backend',
                    method: $method,
                    path: sprintf($path, $resource),
                    routeName: "api.v2.backend.{$resource}.{$action}",
                    permission: $resourceConfig['permissions'][$action] ?? null,
                    ability: LegacyOperationSecurity::resourceAbility($resource, $action),
                    websiteScoped: true,
                    risk: $method === 'GET' ? 'read' : 'write',
                    implementation: $this->resourceImplementation($resource, $resourceConfig, $referenceDomain),
                    request: ApiSchema::object(),
                    response: ApiSchema::object(),
                ));
            }
        }
    }

    /**
     * Register configured backend bridge action contracts.
     *
     * @param  \Wncms\Api\V2\ApiContractRegistry  $registry
     * @param  array<int, array<string, mixed>>  $actions
     * @return void
     */
    protected function registerActions(ApiContractRegistry $registry, array $actions): void
    {
        foreach ($actions as $action) {
            $name = (string) ($action['name'] ?? '');
            $uri = (string) ($action['uri'] ?? '');

            if ($name === '' || $uri === '') {
                continue;
            }

            $domain = explode('.', $name)[0];
            $method = strtoupper((string) ($action['method'] ?? 'post'));

            $this->registerDomain($registry, $domain);
            $registry->registerOperation(new ApiOperationContract(
                id: "backend.{$name}",
                domain: $domain,
                surface: 'backend',
                method: $method,
                path: "/api/v2/backend/{$uri}",
                routeName: "api.v2.backend.{$name}",
                permission: $action['permission'] ?? null,
                ability: LegacyOperationSecurity::actionAbility($name, $method),
                websiteScoped: true,
                risk: $method === 'GET' ? 'read' : 'write',
                implementation: 'legacy_bridge',
                request: ApiSchema::object(),
                response: ApiSchema::object(),
            ));
        }
    }

    /**
     * Register a domain only when it has not already been declared.
     *
     * @param  \Wncms\Api\V2\ApiContractRegistry  $registry
     * @param  string  $domain
     * @return void
     */
    protected function registerDomain(ApiContractRegistry $registry, string $domain): void
    {
        if (isset($registry->domains()[$domain])) {
            return;
        }

        $registry->registerDomain(new ApiDomainContract($domain, Str::headline($domain)));
    }

    /**
     * Determine how a configured resource is currently implemented.
     *
     * @param  string  $resource
     * @param  array<string, mixed>  $resourceConfig
     * @param  string|null  $referenceDomain
     * @return string
     */
    protected function resourceImplementation(string $resource, array $resourceConfig, ?string $referenceDomain): string
    {
        if ($resource === $referenceDomain) {
            return 'domain';
        }

        if (($resourceConfig['controller'] ?? ResourceController::class) === ResourceController::class) {
            return 'legacy_resource';
        }

        return 'legacy_controller';
    }
}
