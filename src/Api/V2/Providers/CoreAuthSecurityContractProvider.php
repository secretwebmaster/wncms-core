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
        if (! isset($registry->domains()['authentication'])) {
            $registry->registerDomain(new ApiDomainContract('authentication', 'Authentication'));
        }
        if (! isset($registry->domains()['system'])) {
            $registry->registerDomain(new ApiDomainContract('system', 'System'));
        }

        $public = [
            ['login', '/api/v2/backend/auth/login', ['json', 'cookie']],
            ['refresh', '/api/v2/backend/auth/refresh', ['json', 'cookie']],
            ['logout', '/api/v2/backend/auth/logout', ['json', 'cookie']],
        ];
        foreach ($public as [$action, $path, $transports]) {
            $request = $action === 'login'
                ? ApiSchema::object(['email' => ['type' => 'string'], 'password' => ['type' => 'string', 'writeOnly' => true]], ['email', 'password'])
                : ApiSchema::object(['refresh_token' => ['type' => 'string', 'writeOnly' => true]]);
            $response = $action === 'logout'
                ? ApiSchema::allowAll()
                : $this->credentialResponseSchema();
            $registry->registerOperation(new ApiOperationContract(
                id: "backend.authentication.{$action}", domain: 'authentication', surface: 'backend', method: 'POST',
                path: $path, routeName: "api.v2.backend.auth.{$action}", permission: null, ability: null,
                websiteScoped: false, risk: 'write', implementation: 'domain', request: $request, response: $response,
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
            $response = match ($action) {
                'logout_all' => ApiSchema::object(['revoked_sessions' => ['type' => 'integer']], ['revoked_sessions']),
                'me' => $this->actorResponseSchema(true),
                'sessions.index' => ApiSchema::arrayOf($this->sessionResponseSchema(true)),
                'sessions.destroy' => ApiSchema::allowAll(),
                default => ApiSchema::object(),
            };
            $registry->registerOperation(new ApiOperationContract(
                id: 'backend.authentication.'.$action, domain: 'authentication', surface: 'backend', method: $method,
                path: $path, routeName: 'api.v2.backend.auth.'.$action, permission: null, ability: null,
                websiteScoped: false, risk: $method === 'GET' ? 'read' : 'write', implementation: 'domain',
                request: ApiSchema::object(), response: $response, idempotent: $idempotent,
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

    /**
     * Describe the plaintext-once interactive credential response.
     */
    private function credentialResponseSchema(): ApiSchema
    {
        return ApiSchema::object([
            'access_token' => ['type' => 'string'],
            'token' => ['type' => 'string', 'description' => 'Transitional alias of access_token.'],
            'token_type' => ['type' => 'string', 'enum' => ['Bearer']],
            'access_expires_at' => $this->nullableDateTimeSchema(),
            'refresh_expires_at' => $this->nullableDateTimeSchema(),
            'refresh_token' => ['type' => 'string', 'description' => 'Present only when JSON refresh transport is configured.'],
            'session' => $this->sessionResponseSchema()->toArray(),
            'user' => $this->actorResponseSchema()->toArray(),
        ], ['access_token', 'token', 'token_type', 'access_expires_at', 'refresh_expires_at', 'session', 'user']);
    }

    /**
     * Describe safe actor metadata, including the websites available for scoping.
     */
    private function actorResponseSchema(bool $includeSession = false): ApiSchema
    {
        $properties = [
            'id' => ['type' => 'integer'],
            'name' => ['type' => ['string', 'null']],
            'username' => ['type' => ['string', 'null']],
            'email' => ['type' => ['string', 'null']],
            'roles' => ApiSchema::arrayOf(ApiSchema::string())->toArray(),
            'websites' => ApiSchema::arrayOf(ApiSchema::object([
                'id' => ['type' => 'integer'],
                'key' => ['type' => 'string'],
                'domain' => ['type' => 'string'],
                'site_name' => ['type' => 'string'],
            ], ['id', 'key', 'domain', 'site_name']))->toArray(),
        ];
        $required = ['id', 'name', 'username', 'email', 'roles', 'websites'];

        if ($includeSession) {
            $properties['session'] = [
                'oneOf' => [
                    ApiSchema::object(['id' => ['type' => 'string']], ['id'])->toArray(),
                    ['type' => 'null'],
                ],
            ];
            $required[] = 'session';
        }

        return ApiSchema::object($properties, $required);
    }

    /**
     * Describe safe interactive-session metadata.
     */
    private function sessionResponseSchema(bool $includeLifecycleFields = false): ApiSchema
    {
        $properties = [
            'id' => ['type' => 'string'],
            'device_name' => ['type' => ['string', 'null']],
            'refresh_transport' => ['type' => 'string'],
            'remembered' => ['type' => 'boolean'],
            'expires_at' => $this->nullableDateTimeSchema(),
        ];
        $required = ['id', 'device_name', 'refresh_transport', 'remembered', 'expires_at'];

        if ($includeLifecycleFields) {
            $properties += [
                'current' => ['type' => 'boolean'],
                'last_activity_at' => $this->nullableDateTimeSchema(),
                'revoked_at' => $this->nullableDateTimeSchema(),
                'created_at' => $this->nullableDateTimeSchema(),
            ];
            array_push($required, 'current', 'last_activity_at', 'revoked_at', 'created_at');
        }

        return ApiSchema::object($properties, $required);
    }

    /**
     * Describe one nullable ISO 8601 timestamp.
     *
     * @return array<string, mixed>
     */
    private function nullableDateTimeSchema(): array
    {
        return ['type' => ['string', 'null'], 'format' => 'date-time'];
    }
}
