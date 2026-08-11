<?php

namespace Wncms\Tests\Feature\Api\V2;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Wncms\Api\V2\OpenApiDocumentBuilder;
use Wncms\Models\Website;
use Wncms\Tests\TestCase;

class OpenApiEndpointTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Prepare public API access for each OpenAPI endpoint test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        auth()->forgetGuards();
        uss('enable_api_access', 1);
        uss('api_access_whitelist', '');
    }

    /**
     * Verify the public endpoint exposes the installed registry document without authentication or website context.
     *
     * @return void
     */
    public function test_it_exposes_the_openapi_document_without_a_token_or_website_context(): void
    {
        Website::query()->delete();
        wncms()->cache()->flush(['websites']);

        $response = $this->getJson('/api/v2/openapi.json');

        $response->assertOk();
        $this->assertSame(
            json_encode(
                app(OpenApiDocumentBuilder::class)->build(),
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            ),
            $response->getContent()
        );
        $this->assertTrue(Str::isUuid((string) $response->headers->get('X-Request-ID')));
        $this->assertTrue(Route::has('api.v2.openapi'));

        $middleware = Route::getRoutes()->getByName('api.v2.openapi')->gatherMiddleware();
        $this->assertContains('api_v2_request_id', $middleware);
        $this->assertContains('api_v2_whitelist', $middleware);
        $this->assertNotContains('api_v2_token_auth', $middleware);
        $this->assertNotContains('api_v2_has_website', $middleware);
    }

    /**
     * Verify the public endpoint remains protected by the API whitelist feature gate.
     *
     * @return void
     */
    public function test_it_rejects_access_when_the_api_feature_is_disabled(): void
    {
        uss('enable_api_access', 0);

        $response = $this->getJson('/api/v2/openapi.json');

        $response
            ->assertForbidden()
            ->assertJsonPath('meta.error_code', 'authorization.denied');
        $this->assertSame(
            $response->headers->get('X-Request-ID'),
            $response->json('meta.request_id')
        );
    }
}
