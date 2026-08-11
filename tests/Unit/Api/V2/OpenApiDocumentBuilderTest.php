<?php

namespace Wncms\Tests\Unit\Api\V2;

use Wncms\Api\V2\ApiContractRegistry;
use Wncms\Api\V2\Data\ApiDomainContract;
use Wncms\Api\V2\Data\ApiOperationContract;
use Wncms\Api\V2\Data\ApiSchema;
use Wncms\Api\V2\OpenApiDocumentBuilder;
use Wncms\Tests\TestCase;

class OpenApiDocumentBuilderTest extends TestCase
{
    /**
     * Verify the builder exports the complete registry as a deterministic OpenAPI document.
     *
     * @return void
     */
    public function test_it_builds_a_deterministic_openapi_document_from_every_registry_operation(): void
    {
        config([
            'wncms-api-v2.openapi.title' => 'Contract Test API',
            'wncms-api-v2.openapi.version' => '2.7.0',
        ]);

        $registry = $this->registry();
        $document = (new OpenApiDocumentBuilder($registry))->build();

        $this->assertSame(
            ['openapi', 'jsonSchemaDialect', 'info', 'paths', 'components'],
            array_keys($document)
        );
        $this->assertSame('3.1.0', $document['openapi']);
        $this->assertSame('https://json-schema.org/draft/2020-12/schema', $document['jsonSchemaDialect']);
        $this->assertSame([
            'title' => 'Contract Test API',
            'version' => '2.7.0',
        ], $document['info']);
        $this->assertSame([
            '/api/v2/backend/posts/{id}',
            '/api/v2/frontend/posts',
        ], array_keys($document['paths']));
        $this->assertSame(['get', 'post'], array_keys($document['paths']['/api/v2/frontend/posts']));

        $operationIds = [];
        foreach ($document['paths'] as $pathItem) {
            foreach ($pathItem as $operation) {
                $operationIds[] = $operation['operationId'];
            }
        }
        sort($operationIds);

        $this->assertSame(array_keys($registry->operations()), $operationIds);
        $this->assertSame($operationIds, array_values(array_unique($operationIds)));
        $this->assertSame(
            json_encode($document, JSON_THROW_ON_ERROR),
            json_encode((new OpenApiDocumentBuilder($registry))->build(), JSON_THROW_ON_ERROR)
        );
    }

    /**
     * Verify operations expose schemas, metadata extensions, and surface-specific security.
     *
     * @return void
     */
    public function test_it_maps_operation_contracts_to_openapi_security_schemas_and_extensions(): void
    {
        $document = (new OpenApiDocumentBuilder($this->registry()))->build();
        $frontend = $document['paths']['/api/v2/frontend/posts']['post'];
        $backend = $document['paths']['/api/v2/backend/posts/{id}']['patch'];

        $this->assertSame('frontend.posts.store', $frontend['operationId']);
        $this->assertSame([], $frontend['security']);
        $this->assertSame('backend.posts.update', $backend['operationId']);
        $this->assertSame([['bearerAuth' => []]], $backend['security']);
        $this->assertSame('post_edit', $backend['x-wncms-permission']);
        $this->assertSame('posts:write', $backend['x-wncms-ability']);
        $this->assertTrue($backend['x-wncms-website-scoped']);
        $this->assertSame('write', $backend['x-wncms-risk']);
        $this->assertSame('domain', $backend['x-wncms-implementation']);
        $this->assertSame(
            ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']],
            $backend['parameters'][0]
        );
        $this->assertSame(
            ['type' => 'object', 'properties' => ['title' => ['type' => 'string']], 'required' => ['title']],
            json_decode(json_encode($backend['requestBody']['content']['application/json']['schema'], JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR)
        );
        $this->assertSame(
            '#/components/schemas/SuccessEnvelope',
            $backend['responses']['200']['content']['application/json']['schema']['allOf'][0]['$ref']
        );
        $this->assertSame(
            ['type' => 'object', 'properties' => ['id' => ['type' => 'integer']]],
            json_decode(json_encode($backend['responses']['200']['content']['application/json']['schema']['allOf'][1]['properties']['data'], JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR)
        );
        $this->assertSame(
            '#/components/schemas/ErrorEnvelope',
            $backend['responses']['default']['content']['application/json']['schema']['$ref']
        );
    }

    /**
     * Verify shared components describe bearer authentication and stable API envelopes.
     *
     * @return void
     */
    public function test_it_defines_bearer_authentication_and_standard_envelope_components(): void
    {
        $document = (new OpenApiDocumentBuilder($this->registry()))->build();

        $this->assertSame([
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'WNCMS personal access token',
        ], $document['components']['securitySchemes']['bearerAuth']);
        $this->assertSame(
            ['code', 'status', 'message', 'data', 'meta', 'errors'],
            $document['components']['schemas']['SuccessEnvelope']['required']
        );
        $this->assertSame(
            ['code', 'status', 'message', 'data', 'meta', 'errors'],
            $document['components']['schemas']['ErrorEnvelope']['required']
        );
        $this->assertSame(
            ['request_id'],
            $document['components']['schemas']['ResponseMeta']['required']
        );
        $this->assertSame(
            ['request_id', 'error_code'],
            $document['components']['schemas']['ErrorMeta']['required']
        );

        $wire = json_decode(json_encode($document, JSON_THROW_ON_ERROR));
        $this->assertIsObject(
            $wire->paths->{'/api/v2/frontend/posts'}->post->responses->{'200'}->content->{'application/json'}->schema->allOf[1]->properties->data->properties
        );
    }

    /**
     * Verify two operation IDs cannot overwrite the same OpenAPI path and method.
     *
     * @return void
     */
    public function test_it_rejects_duplicate_path_and_method_slots(): void
    {
        $registry = $this->registry();
        $registry->registerOperation(new ApiOperationContract(
            id: 'frontend.posts.duplicate',
            domain: 'posts',
            surface: 'frontend',
            method: 'GET',
            path: '/api/v2/frontend/posts',
            routeName: 'api.v2.frontend.posts.duplicate',
            permission: null,
            ability: null,
            websiteScoped: false,
            risk: 'read',
            implementation: 'domain',
            request: ApiSchema::object(),
            response: ApiSchema::object(),
        ));

        $this->expectException(\Wncms\Api\V2\Exceptions\ApiContractException::class);
        $this->expectExceptionMessage('OpenAPI path and method');

        (new OpenApiDocumentBuilder($registry))->build();
    }

    /**
     * Build a registry fixture with deliberately unordered paths and methods.
     *
     * @return \Wncms\Api\V2\ApiContractRegistry
     */
    protected function registry(): ApiContractRegistry
    {
        $registry = new ApiContractRegistry;
        $registry->registerDomain(new ApiDomainContract('posts', 'Posts'));

        $registry->registerOperation(new ApiOperationContract(
            id: 'frontend.posts.store',
            domain: 'posts',
            surface: 'frontend',
            method: 'POST',
            path: '/api/v2/frontend/posts',
            routeName: 'api.v2.frontend.posts.store',
            permission: null,
            ability: null,
            websiteScoped: false,
            risk: 'write',
            implementation: 'domain',
            request: ApiSchema::object(['title' => ['type' => 'string']], ['title']),
            response: ApiSchema::object(),
        ));
        $registry->registerOperation(new ApiOperationContract(
            id: 'backend.posts.update',
            domain: 'posts',
            surface: 'backend',
            method: 'PATCH',
            path: '/api/v2/backend/posts/{id}',
            routeName: 'api.v2.backend.posts.update',
            permission: 'post_edit',
            ability: 'posts:write',
            websiteScoped: true,
            risk: 'write',
            implementation: 'domain',
            request: ApiSchema::object(['title' => ['type' => 'string']], ['title']),
            response: ApiSchema::object(['id' => ['type' => 'integer']]),
        ));
        $registry->registerOperation(new ApiOperationContract(
            id: 'frontend.posts.index',
            domain: 'posts',
            surface: 'frontend',
            method: 'GET',
            path: '/api/v2/frontend/posts',
            routeName: 'api.v2.frontend.posts.index',
            permission: null,
            ability: null,
            websiteScoped: false,
            risk: 'read',
            implementation: 'domain',
            request: ApiSchema::object(),
            response: ApiSchema::arrayOf(ApiSchema::object(['id' => ['type' => 'integer']])),
        ));

        return $registry;
    }
}
