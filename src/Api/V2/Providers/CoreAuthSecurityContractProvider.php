<?php

namespace Wncms\Api\V2\Providers;

use Wncms\Api\V2\ApiContractRegistry;
use Wncms\Api\V2\Contracts\ApiContractProvider;
use Wncms\Api\V2\Data\ApiDomainContract;
use Wncms\Api\V2\Data\ApiOperationContract;
use Wncms\Api\V2\Data\ApiSchema;
use Wncms\Auth\Api\V2\ApiCredential;

final class CoreAuthSecurityContractProvider implements ApiContractProvider
{
    public function register(ApiContractRegistry $registry): void
    {
        if (! isset($registry->domains()['authentication'])) $registry->registerDomain(new ApiDomainContract('authentication', 'Authentication'));
        if (! isset($registry->domains()['system'])) $registry->registerDomain(new ApiDomainContract('system', 'System'));

        $public = [
            ['login', '/api/v2/backend/auth/login', ['json', 'cookie']],
            ['refresh', '/api/v2/backend/auth/refresh', ['json', 'cookie']],
            ['logout', '/api/v2/backend/auth/logout', ['json', 'cookie']],
        ];
        foreach ($public as [$action, $path, $transports]) {
            $request = $action === 'login'
                ? ApiSchema::object(['email' => ['type' => 'string'], 'password' => ['type' => 'string', 'writeOnly' => true]], ['email', 'password'])
                : ApiSchema::object(['refresh_token' => ['type' => 'string', 'writeOnly' => true]]);
            $registry->registerOperation(new ApiOperationContract(
                id: "backend.authentication.{$action}", domain: 'authentication', surface: 'backend', method: 'POST',
                path: $path, routeName: "api.v2.backend.auth.{$action}", permission: null, ability: null,
                websiteScoped: false, risk: 'write', implementation: 'domain', request: $request, response: ApiSchema::object(),
                acceptedCredentialTypes: [], sideEffectKind: 'database', refreshTransports: $transports,
            ));
        }

        foreach ([
            ['logout_all', 'POST', '/api/v2/backend/auth/logout-all', true, 'sensitive'],
            ['me', 'GET', '/api/v2/backend/auth/me', false, 'normal'],
            ['sessions.index', 'GET', '/api/v2/backend/auth/sessions', false, 'normal'],
            ['sessions.destroy', 'DELETE', '/api/v2/backend/auth/sessions/{session_id}', true, 'sensitive'],
            ['reauthenticate', 'POST', '/api/v2/backend/auth/reauthenticate', false, 'sensitive'],
        ] as [$action, $method, $path, $idempotent, $risk]) {
            $registry->registerOperation(new ApiOperationContract(
                id: 'backend.authentication.'.$action, domain: 'authentication', surface: 'backend', method: $method,
                path: $path, routeName: 'api.v2.backend.auth.'.$action, permission: null, ability: null,
                websiteScoped: false, risk: $method === 'GET' ? 'read' : 'write', implementation: 'domain',
                request: ApiSchema::object(), response: ApiSchema::object(), idempotent: $idempotent,
                securityRisk: $risk, acceptedCredentialTypes: [ApiCredential::TYPE_INTERACTIVE_ACCESS],
                domainModelKeys: $method === 'GET' ? [] : ['api_session', 'api_access_token', 'api_refresh_token'],
                sideEffectKind: $method === 'GET' ? 'read' : 'database', idempotencyRequired: $idempotent,
            ));
        }

        $registry->registerOperation(new ApiOperationContract(
            id: 'backend.authentication.action_plans.store', domain: 'authentication', surface: 'backend', method: 'POST',
            path: '/api/v2/backend/action-plans', routeName: 'api.v2.backend.action_plans.store', permission: null, ability: null,
            websiteScoped: false, risk: 'write', implementation: 'domain', request: ApiSchema::object(), response: ApiSchema::object(),
            securityRisk: 'high', actionPlanEligible: false, domainModelKeys: ['api_action_plan'], sideEffectKind: 'database',
        ));

        foreach ([['i18n.ui', '/api/v2/backend/i18n/ui'], ['translations', '/api/v2/backend/translations']] as [$action, $path]) {
            $registry->registerOperation(new ApiOperationContract(
                id: 'backend.system.'.$action, domain: 'system', surface: 'backend', method: 'GET', path: $path,
                routeName: 'api.v2.backend.'.$action, permission: null, ability: null, websiteScoped: false,
                risk: 'read', implementation: 'domain', request: ApiSchema::object(), response: ApiSchema::object(),
            ));
        }
    }
}
