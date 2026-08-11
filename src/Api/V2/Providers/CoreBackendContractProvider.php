<?php

namespace Wncms\Api\V2\Providers;

use Wncms\Api\V2\ApiContractRegistry;
use Wncms\Api\V2\Contracts\ApiContractProvider;
use Wncms\Api\V2\Data\ApiDomainContract;
use Wncms\Api\V2\Data\ApiOperationContract;
use Wncms\Api\V2\Data\ApiSchema;
use Wncms\Api\V2\Enums\AsyncOperationStatus;
use Wncms\Http\Controllers\Api\V2\Backend\OperationController;

class CoreBackendContractProvider implements ApiContractProvider
{
    /**
     * Register formal core backend operation-resource contracts.
     *
     * @param  \Wncms\Api\V2\ApiContractRegistry  $registry
     * @return void
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
        ));
    }

    /**
     * Build the stable asynchronous operation response schema.
     *
     * @return \Wncms\Api\V2\Data\ApiSchema
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
