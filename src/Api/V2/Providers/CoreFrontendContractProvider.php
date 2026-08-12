<?php

namespace Wncms\Api\V2\Providers;

use Wncms\Api\V2\ApiContractRegistry;
use Wncms\Api\V2\Contracts\ApiContractProvider;
use Wncms\Api\V2\Data\ApiDomainContract;
use Wncms\Api\V2\Data\ApiOperationContract;
use Wncms\Api\V2\Data\ApiSchema;

class CoreFrontendContractProvider implements ApiContractProvider
{
    /**
     * Register the core frontend and system API contracts.
     *
     * @param  \Wncms\Api\V2\ApiContractRegistry  $registry
     * @return void
     */
    public function register(ApiContractRegistry $registry): void
    {
        $registry->registerDomain(new ApiDomainContract('frontend', 'Frontend'));
        $registry->registerDomain(new ApiDomainContract('system', 'System'));

        $registry->registerOperation(new ApiOperationContract(
            id: 'frontend.health',
            domain: 'frontend',
            surface: 'frontend',
            method: 'GET',
            path: '/api/v2/frontend/health',
            routeName: 'api.v2.frontend.health',
            permission: null,
            ability: null,
            websiteScoped: false,
            risk: 'read',
            implementation: 'domain',
            request: ApiSchema::object(),
            response: ApiSchema::object(),
        ));

        $registry->registerOperation(new ApiOperationContract(
            id: 'system.translations',
            domain: 'system',
            surface: 'frontend',
            method: 'GET',
            path: '/api/v2/translations',
            routeName: 'api.v2.translations',
            permission: null,
            ability: null,
            websiteScoped: false,
            risk: 'read',
            implementation: 'domain',
            request: ApiSchema::object(),
            response: ApiSchema::object(),
        ));
    }
}
