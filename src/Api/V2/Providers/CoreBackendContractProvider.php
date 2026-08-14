<?php

namespace Wncms\Api\V2\Providers;

use Wncms\Api\V2\ApiContractRegistry;
use Wncms\Api\V2\Contracts\ApiContractProvider;
use Wncms\Api\V2\Data\ApiDomainContract;
use Wncms\Api\V2\Data\ApiOperationContract;
use Wncms\Api\V2\Data\ApiSchema;
use Wncms\Api\V2\Enums\AsyncOperationStatus;
use Wncms\Auth\Api\V2\ApiCredential;
use Wncms\Http\Controllers\Api\V2\Backend\OperationController;

class CoreBackendContractProvider implements ApiContractProvider
{
    /**
     * Register formal core backend operation-resource contracts.
     */
    public function register(ApiContractRegistry $registry): void
    {
        $registry->registerDomain(new ApiDomainContract('operations', 'Operations'));
        $response = $this->operationResponseSchema();

        $registry->registerOperation(new ApiOperationContract(
            id: 'backend.operations.show',
            domain: 'operations',
            surface: 'backend',
            method: 'GET',
            path: '/api/v2/backend/operations/{id}',
            routeName: 'api.v2.backend.operations.show',
            permission: null,
            ability: null,
            websiteScoped: false,
            risk: 'read',
            implementation: 'domain',
            request: ApiSchema::object(),
            response: $response,
        ));

        $registry->registerOperation(new ApiOperationContract(
            id: 'backend.operations.cancel',
            domain: 'operations',
            surface: 'backend',
            method: 'POST',
            path: '/api/v2/backend/operations/{id}/cancel',
            routeName: 'api.v2.backend.operations.cancel',
            permission: OperationController::CANCEL_PERMISSION,
            ability: null,
            websiteScoped: false,
            risk: 'destructive',
            implementation: 'domain',
            request: ApiSchema::object(),
            response: $response,
            idempotent: true,
            idempotencyRequired: true,
        ));

        $this->registerServiceTokenOperations($registry);
        $this->registerUserSecurityOperations($registry);
        $this->registerBladeSecurityOperations($registry);
        $this->registerSecurityEventOperations($registry);
    }

    private function registerSecurityEventOperations(ApiContractRegistry $registry): void
    {
        foreach ([
            ['index', '/api/v2/backend/security/events', 'security_event_index'],
            ['show', '/api/v2/backend/security/events/{event_id}', 'security_event_show'],
        ] as [$action, $path, $permission]) {
            $registry->registerOperation(new ApiOperationContract(
                id: "backend.security.events.{$action}", domain: 'security', surface: 'backend', method: 'GET',
                path: $path, routeName: "api.v2.backend.security.events.{$action}", permission: $permission,
                ability: 'security.events', websiteScoped: false, risk: 'read', implementation: 'domain',
                request: ApiSchema::object(), response: ApiSchema::object(),
                filters: $action === 'index' ? ['type', 'severity', 'outcome', 'surface', 'actor_type', 'actor_id', 'target_type', 'target_id', 'credential_type', 'credential_id', 'website_id', 'request_id', 'run_id', 'from', 'to'] : [],
                acceptedCredentialTypes: [ApiCredential::TYPE_INTERACTIVE_ACCESS], sideEffectKind: 'read',
            ));
        }
    }

    private function registerBladeSecurityOperations(ApiContractRegistry $registry): void
    {
        $registry->registerDomain(new ApiDomainContract('security', 'Security'));
        $registry->registerOperation(new ApiOperationContract(
            id: 'backend.security.blade.show', domain: 'security', surface: 'backend', method: 'GET',
            path: '/api/v2/backend/security/blade', routeName: 'api.v2.backend.security.blade.show',
            permission: 'blade_mode_manage', ability: 'security.blade', websiteScoped: false, risk: 'read',
            implementation: 'domain', request: ApiSchema::object(), response: ApiSchema::object(),
            acceptedCredentialTypes: [ApiCredential::TYPE_INTERACTIVE_ACCESS], sideEffectKind: 'read',
        ));
        $registry->registerOperation(new ApiOperationContract(
            id: 'backend.security.blade.update', domain: 'security', surface: 'backend', method: 'PATCH',
            path: '/api/v2/backend/security/blade', routeName: 'api.v2.backend.security.blade.update',
            permission: 'blade_mode_manage', ability: 'security.blade', websiteScoped: false, risk: 'write',
            implementation: 'domain', request: ApiSchema::object(['enabled' => ['type' => 'boolean']], ['enabled']), response: ApiSchema::object(),
            idempotent: true, securityRisk: 'high', acceptedCredentialTypes: [ApiCredential::TYPE_INTERACTIVE_ACCESS],
            requiresStepUp: true, stepUpPurposes: ['blade.mode'], actionPlanEligible: true,
            domainModelKeys: ['setting'], sideEffectKind: 'database',
            idempotencyRequired: true,
        ));
    }

    /** Register stable self-service profile and credential-security operations. */
    private function registerUserSecurityOperations(ApiContractRegistry $registry): void
    {
        foreach ([
            ['password.forgot', 'POST', '/api/v2/backend/auth/password/forgot'],
            ['password.reset', 'POST', '/api/v2/backend/auth/password/reset'],
            ['email_verification.verify', 'POST', '/api/v2/backend/auth/email-verification/verify'],
            ['email.change.confirm', 'POST', '/api/v2/backend/auth/email/change/confirm'],
        ] as [$action, $method, $path]) {
            $registry->registerOperation(new ApiOperationContract(
                id: "backend.auth.{$action}", domain: 'authentication', surface: 'backend', method: $method,
                path: $path, routeName: "api.v2.backend.auth.{$action}", permission: null, ability: null,
                websiteScoped: false, risk: 'write', implementation: 'domain', request: ApiSchema::object(), response: ApiSchema::object(),
                acceptedCredentialTypes: [], sideEffectKind: 'database',
            ));
        }

        $definitions = [
            ['profile.update', 'PATCH', '/api/v2/backend/auth/profile', 'account.profile', false, null, false],
            ['password.update', 'PATCH', '/api/v2/backend/auth/password', 'account.password', true, 'password.change', true],
            ['email.change', 'POST', '/api/v2/backend/auth/email/change', 'account.email', true, 'email.change', true],
            ['email_verification.send', 'POST', '/api/v2/backend/auth/email-verification/send', 'account.email', false, null, false],
        ];
        foreach ($definitions as [$action, $method, $path, $ability, $stepUp, $purpose, $idempotent]) {
            $registry->registerOperation(new ApiOperationContract(
                id: "backend.auth.{$action}", domain: 'authentication', surface: 'backend', method: $method,
                path: $path, routeName: "api.v2.backend.auth.{$action}", permission: null, ability: $ability,
                websiteScoped: false, risk: 'write', implementation: 'domain', request: ApiSchema::object(), response: ApiSchema::object(),
                idempotent: $idempotent, securityRisk: $stepUp ? 'sensitive' : 'normal',
                acceptedCredentialTypes: [ApiCredential::TYPE_INTERACTIVE_ACCESS], requiresStepUp: $stepUp,
                stepUpPurposes: $purpose === null ? [] : [$purpose], domainModelKeys: $stepUp ? ['user', 'api_session', 'api_access_token', 'api_refresh_token', 'api_service_token'] : [],
                sideEffectKind: 'database',
                idempotencyRequired: $idempotent,
            ));
        }
    }

    /** Register the interactive-only scoped service-token management contract. */
    private function registerServiceTokenOperations(ApiContractRegistry $registry): void
    {
        if (! isset($registry->domains()['authentication'])) {
            $registry->registerDomain(new ApiDomainContract('authentication', 'Authentication'));
        }
        $definitions = [
            ['options', 'GET', '/api/v2/backend/auth/service-token-options', 'api_token_create', 'tokens.create', false, false, null],
            ['index', 'GET', '/api/v2/backend/auth/service-tokens', 'api_token_index', 'tokens.read', false, false, null],
            ['store', 'POST', '/api/v2/backend/auth/service-tokens', 'api_token_create', 'tokens.create', true, true, 'service_token.create'],
            ['show', 'GET', '/api/v2/backend/auth/service-tokens/{token_id}', 'api_token_show', 'tokens.read', false, false, null],
            ['rotate', 'POST', '/api/v2/backend/auth/service-tokens/{token_id}/rotate', 'api_token_rotate', 'tokens.rotate', true, true, 'service_token.rotate'],
            ['destroy', 'DELETE', '/api/v2/backend/auth/service-tokens/{token_id}', 'api_token_revoke', 'tokens.revoke', true, true, 'service_token.revoke'],
        ];

        foreach ($definitions as [$action, $method, $path, $permission, $ability, $mutation, $stepUp, $purpose]) {
            $registry->registerOperation(new ApiOperationContract(
                id: "backend.auth.service_tokens.{$action}",
                domain: 'authentication',
                surface: 'backend',
                method: $method,
                path: $path,
                routeName: "api.v2.backend.auth.service_tokens.{$action}",
                permission: $permission,
                ability: $ability,
                websiteScoped: false,
                risk: $mutation ? 'write' : 'read',
                implementation: 'domain',
                request: ApiSchema::object(),
                response: ApiSchema::object(),
                idempotent: $mutation,
                securityRisk: $mutation ? 'sensitive' : 'normal',
                acceptedCredentialTypes: [ApiCredential::TYPE_INTERACTIVE_ACCESS],
                requiresStepUp: $stepUp,
                stepUpPurposes: $purpose === null ? [] : [$purpose],
                actionPlanEligible: $mutation,
                domainModelKeys: $mutation ? ['api_service_token'] : [],
                sideEffectKind: $mutation ? 'database' : 'read',
                idempotencyRequired: $mutation,
            ));
        }
    }

    /**
     * Build the stable asynchronous operation response schema.
     */
    private function operationResponseSchema(): ApiSchema
    {
        return ApiSchema::object([
            'id' => ['type' => 'string', 'format' => 'uuid'],
            'type' => ['type' => 'string'],
            'status' => [
                'type' => 'string',
                'enum' => array_map(
                    static fn (AsyncOperationStatus $status): string => $status->value,
                    AsyncOperationStatus::cases()
                ),
            ],
            'progress' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 100],
            'cancellable' => ['type' => 'boolean'],
            'actor_id' => ['type' => 'integer'],
            'website_ids' => [
                'type' => 'array',
                'items' => [
                    'oneOf' => [
                        ['type' => 'integer'],
                        ['type' => 'string'],
                    ],
                ],
            ],
            'result' => [
                'oneOf' => [
                    ['type' => 'object'],
                    ['type' => 'array'],
                    ['type' => 'string'],
                    ['type' => 'number'],
                    ['type' => 'boolean'],
                    ['type' => 'null'],
                ],
            ],
            'error' => [
                'oneOf' => [
                    ['type' => 'object'],
                    ['type' => 'array'],
                    ['type' => 'null'],
                ],
            ],
            'created_at' => ['type' => 'string', 'format' => 'date-time'],
            'updated_at' => ['type' => 'string', 'format' => 'date-time'],
            'expires_at' => ['type' => 'string', 'format' => 'date-time'],
        ], [
            'id',
            'type',
            'status',
            'progress',
            'cancellable',
            'actor_id',
            'website_ids',
            'result',
            'error',
            'created_at',
            'updated_at',
            'expires_at',
        ]);
    }
}
