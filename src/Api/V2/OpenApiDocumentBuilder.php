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
            'security' => $this->securityRequirements($operation),
            'x-wncms-permission' => $operation->permission,
            'x-wncms-permission-mode' => $operation->permissionMode,
            'x-wncms-ability' => $operation->ability,
            'x-wncms-website-scoped' => $operation->websiteScoped,
            'x-wncms-risk' => $operation->risk,
            'x-wncms-implementation' => $operation->implementation,
            'x-wncms-security-risk' => $operation->securityRisk,
            'x-wncms-accepted-credential-types' => $operation->acceptedCredentialTypes,
            'x-wncms-requires-step-up' => $operation->requiresStepUp,
            'x-wncms-step-up-purposes' => $operation->stepUpPurposes,
            'x-wncms-action-plan-eligible' => $operation->actionPlanEligible,
            'x-wncms-legacy-token-allowed' => $operation->legacyTokenAllowed,
            'x-wncms-website-scope-mode' => $operation->websiteScopeMode,
            'x-wncms-idempotency-required' => $operation->idempotencyRequired,
            'x-wncms-refresh-transports' => $operation->refreshTransports,
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

    /** @return array<int, array<string, array<int, string>>> */
    private function securityRequirements(ApiOperationContract $operation): array
    {
        if ($operation->surface === 'frontend' || $operation->acceptedCredentialTypes === []) {
            if ($operation->refreshTransports !== [] && ! str_ends_with($operation->id, '.login')) {
                return [
                    ['refreshTokenBody' => []],
                    ['refreshCookie' => [], 'csrfCookie' => [], 'csrfHeader' => []],
                ];
            }

            return [];
        }

        return [['bearerAuth' => []]];
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
                'refreshTokenBody' => ['type' => 'apiKey', 'in' => 'query', 'name' => 'refresh_token', 'description' => 'Conceptual JSON request-body refresh credential.'],
                'refreshCookie' => ['type' => 'apiKey', 'in' => 'cookie', 'name' => 'wncms_refresh_token'],
                'csrfCookie' => ['type' => 'apiKey', 'in' => 'cookie', 'name' => 'wncms_refresh_csrf'],
                'csrfHeader' => ['type' => 'apiKey', 'in' => 'header', 'name' => 'X-WNCMS-CSRF'],
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
