<?php

namespace Wncms\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Wncms\Api\V2\ApiResponseFactory;
use Wncms\Api\V2\CapabilityResolver;
use Wncms\Api\V2\OpenApiDocumentBuilder;
use Wncms\Http\Controllers\Controller;

class ContractController extends Controller
{
    /**
     * Create the API v2 contract controller.
     *
     * @param  \Wncms\Api\V2\CapabilityResolver  $capabilities
     * @param  \Wncms\Api\V2\ApiResponseFactory  $responses
     * @param  \Wncms\Api\V2\OpenApiDocumentBuilder  $openApi
     */
    public function __construct(
        protected CapabilityResolver $capabilities,
        protected ApiResponseFactory $responses,
        protected OpenApiDocumentBuilder $openApi,
    ) {}

    /**
     * Return runtime API capabilities for the authenticated operator.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function capabilities(Request $request): JsonResponse
    {
        return $this->responses->success(
            $this->capabilities->resolve($request->user())
        );
    }

    /**
     * Return the installed-system OpenAPI 3.1 document.
     *
     * A plain JSON response preserves the document root while request-ID middleware adds the response header.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \JsonException
     */
    public function openApi(): Response
    {
        $contents = json_encode(
            $this->openApi->build(),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );

        return response($contents, Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }
}
