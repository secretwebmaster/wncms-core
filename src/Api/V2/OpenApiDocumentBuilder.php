<?php

namespace Wncms\Api\V2;

use Wncms\Api\V2\Data\ApiOperationContract;
use Wncms\Api\V2\Exceptions\ApiContractException;

final class OpenApiDocumentBuilder
{
    /**
     * Create the OpenAPI document builder.
     *
     * @param  \Wncms\Api\V2\ApiContractRegistry  $registry
     */
    public function __construct(private readonly ApiContractRegistry $registry) {}

    /**
     * Build the complete deterministic OpenAPI 3.1 document.
     *
     * @return array<string, mixed>
     *
     * @throws \Wncms\Api\V2\Exceptions\ApiContractException
     */
    public function build(): array
    {
        $paths = [];

        foreach ($this->registry->operations() as $operation) {
            $method = strtolower($operation->method);

            if (isset($paths[$operation->path][$method])) {
                throw new ApiContractException(
                    "OpenAPI path and method '{$operation->method} {$operation->path}' are already registered."
                );
            }

            $paths[$operation->path][$method] = $this->operation($operation);
        }

        ksort($paths);
        foreach ($paths as &$pathItem) {
            ksort($pathItem);
        }
        unset($pathItem);

        return [
            'openapi' => '3.1.0',
            'jsonSchemaDialect' => 'https://json-schema.org/draft/2020-12/schema',
            'info' => [
                'title' => (string) config('wncms-api-v2.openapi.title'),
                'version' => (string) config('wncms-api-v2.openapi.version'),
            ],
            'paths' => $paths,
            'components' => $this->components(),
        ];
    }

    /**
     * Build one OpenAPI operation from its registry contract.
     *
     * @param  \Wncms\Api\V2\Data\ApiOperationContract  $operation
     *
     * @return array<string, mixed>
     */
    private function operation(ApiOperationContract $operation): array
    {
        $data = [
            'operationId' => $operation->id,
            'security' => $operation->surface === 'frontend'
                ? []
                : [['bearerAuth' => []]],
            'x-wncms-permission' => $operation->permission,
            'x-wncms-ability' => $operation->ability,
            'x-wncms-website-scoped' => $operation->websiteScoped,
            'x-wncms-risk' => $operation->risk,
            'x-wncms-implementation' => $operation->implementation,
        ];

        $parameters = $this->pathParameters($operation->path);
        if ($parameters !== []) {
            $data['parameters'] = $parameters;
        }

        if (! in_array(strtoupper($operation->method), ['GET', 'HEAD'], true)) {
            $data['requestBody'] = [
                'content' => [
                    'application/json' => [
                        'schema' => $operation->request->jsonSerialize(),
                    ],
                ],
            ];
        }

        $data['responses'] = [
            '2XX' => [
                'description' => 'Successful response',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'allOf' => [
                                ['$ref' => '#/components/schemas/SuccessEnvelope'],
                                [
                                    'type' => 'object',
                                    'properties' => [
                                        'data' => $operation->response->jsonSerialize(),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'default' => [
                'description' => 'Error response',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/ErrorEnvelope'],
                    ],
                ],
            ],
        ];

        return $data;
    }

    /**
     * Build path parameters in their URL appearance order.
     *
     * @param  string  $path
     *
     * @return array<int, array<string, mixed>>
     */
    private function pathParameters(string $path): array
    {
        preg_match_all('/\{([^}]+)\}/', $path, $matches);

        return array_map(static fn (string $name): array => [
            'name' => $name,
            'in' => 'path',
            'required' => true,
            'schema' => ['type' => 'string'],
        ], $matches[1] ?? []);
    }

    /**
     * Build shared OpenAPI authentication and envelope components.
     *
     * @return array<string, mixed>
     */
    private function components(): array
    {
        return [
            'securitySchemes' => [
                'bearerAuth' => [
                    'type' => 'http',
                    'scheme' => 'bearer',
                    'bearerFormat' => 'WNCMS personal access token',
                ],
            ],
            'schemas' => [
                'ResponseMeta' => [
                    'type' => 'object',
                    'properties' => [
                        'request_id' => [
                            'type' => 'string',
                            'format' => 'uuid',
                        ],
                    ],
                    'required' => ['request_id'],
                ],
                'ErrorMeta' => [
                    'type' => 'object',
                    'properties' => [
                        'request_id' => [
                            'type' => 'string',
                            'format' => 'uuid',
                        ],
                        'error_code' => ['type' => 'string'],
                    ],
                    'required' => ['request_id', 'error_code'],
                ],
                'SuccessEnvelope' => [
                    'type' => 'object',
                    'properties' => [
                        'code' => ['type' => 'integer'],
                        'status' => ['type' => 'string', 'const' => 'success'],
                        'message' => ['type' => 'string'],
                        'data' => (object) [],
                        'meta' => ['$ref' => '#/components/schemas/ResponseMeta'],
                        'errors' => [
                            'type' => 'array',
                            'maxItems' => 0,
                        ],
                    ],
                    'required' => ['code', 'status', 'message', 'data', 'meta', 'errors'],
                ],
                'ErrorEnvelope' => [
                    'type' => 'object',
                    'properties' => [
                        'code' => ['type' => 'integer'],
                        'status' => ['type' => 'string', 'const' => 'fail'],
                        'message' => ['type' => 'string'],
                        'data' => (object) [],
                        'meta' => ['$ref' => '#/components/schemas/ErrorMeta'],
                        'errors' => [
                            'oneOf' => [
                                [
                                    'type' => 'object',
                                    'additionalProperties' => (object) [],
                                ],
                                [
                                    'type' => 'array',
                                    'items' => (object) [],
                                ],
                            ],
                        ],
                    ],
                    'required' => ['code', 'status', 'message', 'data', 'meta', 'errors'],
                ],
            ],
        ];
    }
}
