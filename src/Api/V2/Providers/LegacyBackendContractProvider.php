<?php

namespace Wncms\Api\V2\Providers;

use Illuminate\Support\Str;
use Wncms\Api\V2\ApiContractRegistry;
use Wncms\Api\V2\Contracts\ApiContractProvider;
use Wncms\Api\V2\Data\ApiDomainContract;
use Wncms\Api\V2\Data\ApiOperationContract;
use Wncms\Api\V2\Data\ApiSchema;
use Wncms\Api\V2\LegacyOperationSecurity;
use Wncms\Api\V2\Risk\LegacyOperationDescriptorRegistry;
use Wncms\Http\Controllers\Api\V2\Backend\ResourceController;

class LegacyBackendContractProvider implements ApiContractProvider
{
    /**
     * Register configured backend resource and bridge contracts.
     */
    public function register(ApiContractRegistry $registry): void
    {
        $actions = config('wncms-backend-api-v2.actions', []);
        $resources = config('wncms-backend-api-v2.resources', []);
        (new LegacyOperationDescriptorRegistry)->validateCollisions($resources, $actions);
        $bridgeOperationIds = array_map(
            fn (array $action): string => 'backend.'.($action['name'] ?? ''),
            $actions,
        );

        $this->registerResources($registry, $resources, $bridgeOperationIds);
        $this->registerActions($registry, $actions);
    }

    /**
     * Register configured backend resource contracts.
     *
     * @param  array<string, array<string, mixed>>  $resources
     * @param  array<int, string>  $bridgeOperationIds
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

                $security = LegacyOperationSecurity::resourceRequirements($resource, $action, $resourceConfig);

                $registry->registerOperation(new ApiOperationContract(
                    id: $operationId,
                    domain: $resource,
                    surface: 'backend',
                    method: $method,
                    path: sprintf($path, $resource),
                    routeName: "api.v2.backend.{$resource}.{$action}",
                    permission: $security['permission'],
                    ability: $security['ability'],
                    websiteScoped: true,
                    risk: $security['data_risk'],
                    implementation: $this->resourceImplementation($resource, $resourceConfig, $referenceDomain),
                    request: ApiSchema::object(),
                    response: ApiSchema::object(),
                    permissionMode: $security['permission_mode'],
                    securityRisk: $security['security_risk'],
                    acceptedCredentialTypes: $security['accepted_credential_types'],
                    requiresStepUp: $security['requires_step_up'],
                    stepUpPurposes: $security['step_up_purposes'],
                    actionPlanEligible: $security['action_plan_eligible'],
                    idempotent: $security['idempotent'],
                    domainModelKeys: $security['domain_model_keys'],
                    transactionalOutboxModelKeys: $security['transactional_outbox_model_keys'],
                    sideEffectKind: $security['side_effect_kind'],
                    canonicalizer: $security['canonicalizer'],
                    targetResolver: $security['target_resolver'],
                    relationshipBoundaries: $security['relationship_boundaries'],
                    legacyTokenAllowed: in_array('legacy_personal_access_token', $security['accepted_credential_types'], true),
                    websiteScopeMode: 'required',
                    idempotencyRequired: $security['idempotent'],
                ));
            }
        }
    }

    /**
     * Register configured backend bridge action contracts.
     *
     * @param  array<int, array<string, mixed>>  $actions
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
            $security = LegacyOperationSecurity::actionRequirements($action);

            $this->registerDomain($registry, $domain);
            $registry->registerOperation(new ApiOperationContract(
                id: "backend.{$name}",
                domain: $domain,
                surface: 'backend',
                method: $method,
                path: "/api/v2/backend/{$uri}",
                routeName: "api.v2.backend.{$name}",
                permission: $security['permission'],
                ability: $security['ability'],
                websiteScoped: true,
                risk: $security['data_risk'],
                implementation: 'legacy_bridge',
                request: ApiSchema::object(),
                response: ApiSchema::object(),
                permissionMode: $security['permission_mode'],
                securityRisk: $security['security_risk'],
                acceptedCredentialTypes: $security['accepted_credential_types'],
                requiresStepUp: $security['requires_step_up'],
                stepUpPurposes: $security['step_up_purposes'],
                actionPlanEligible: $security['action_plan_eligible'],
                idempotent: $security['idempotent'],
                domainModelKeys: $security['domain_model_keys'],
                transactionalOutboxModelKeys: $security['transactional_outbox_model_keys'],
                sideEffectKind: $security['side_effect_kind'],
                canonicalizer: $security['canonicalizer'],
                targetResolver: $security['target_resolver'],
                relationshipBoundaries: $security['relationship_boundaries'],
                legacyTokenAllowed: in_array('legacy_personal_access_token', $security['accepted_credential_types'], true),
                websiteScopeMode: 'required',
                idempotencyRequired: $security['idempotent'],
            ));
        }
    }

    /**
     * Register a domain only when it has not already been declared.
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
     * @param  array<string, mixed>  $resourceConfig
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
