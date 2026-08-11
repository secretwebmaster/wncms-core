<?php

namespace Wncms\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Wncms\Api\V2\ApiResponseFactory;
use Wncms\Api\V2\CapabilityResolver;
use Wncms\Http\Controllers\Controller;

class ContractController extends Controller
{
    /**
     * Create the API v2 contract controller.
     *
     * @param  \Wncms\Api\V2\CapabilityResolver  $capabilities
     * @param  \Wncms\Api\V2\ApiResponseFactory  $responses
     */
    public function __construct(
        protected CapabilityResolver $capabilities,
        protected ApiResponseFactory $responses,
    ) {
    }

    /**
     * Return runtime API capabilities for the authenticated operator.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function capabilities(Request $request): JsonResponse
    {
        return $this->responses->success(
            $this->capabilities->resolve($request->user())
        );
    }
}
