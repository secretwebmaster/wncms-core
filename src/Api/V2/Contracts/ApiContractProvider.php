<?php

namespace Wncms\Api\V2\Contracts;

use Wncms\Api\V2\ApiContractRegistry;

interface ApiContractProvider
{
    /**
     * Register this provider's API contract declarations.
     *
     * @param  \Wncms\Api\V2\ApiContractRegistry  $registry
     * @return void
     */
    public function register(ApiContractRegistry $registry): void;
}
