<?php

namespace Wncms\Tests\Feature\Api\V2;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Wncms\Http\Controllers\Api\V2\Backend\ApiV2Controller;
use Wncms\Models\User;
use Wncms\Models\Website;
use Wncms\Tests\TestCase;

class ApiEnvelopeContractTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Prepare API access and exception fixtures for each envelope contract test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        auth()->forgetGuards();
        uss('enable_api_access', 1);
        uss('api_access_whitelist', '');

        Route::get('/api/v2/_contract-test/{type}', [ApiEnvelopeContractTestController::class, 'respond']);
    }

    /**
     * Verify successful API responses preserve a valid caller request ID.
     *
     * @return void
     */
    public function test_success_envelope_preserves_a_valid_caller_request_id(): void
    {
        $requestId = '123e4567-e89b-42d3-a456-426614174000';
        $response = $this->withHeader('X-Request-ID', $requestId)
            ->getJson('/api/v2/frontend/health');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('meta.request_id', $requestId);
        $this->assertSame($requestId, $response->headers->get('X-Request-ID'));
        $this->assertEnvelope($response);
    }

    /**
     * Verify malformed caller request IDs are replaced with generated UUIDs.
     *
     * @return void
     */
    public function test_malformed_request_id_is_replaced_with_a_generated_uuid(): void
    {
        $response = $this->withHeader('X-Request-ID', 'not-a-uuid')
            ->getJson('/api/v2/frontend/health');

        $requestId = (string) $response->headers->get('X-Request-ID');

        $response->assertOk();
        $this->assertNotSame('not-a-uuid', $requestId);
        $this->assertTrue(Str::isUuid($requestId));
        $this->assertEnvelope($response);
    }

    /**
     * Verify the API feature gate returns the standard authorization failure.
     *
     * @return void
     */
    public function test_disabled_api_returns_an_authorization_failure_envelope(): void
    {
        uss('enable_api_access', 0);

        $response = $this->getJson('/api/v2/frontend/health');

        $response
            ->assertForbidden()
            ->assertJsonPath('meta.error_code', 'authorization.denied');
        $this->assertEnvelope($response);
    }

    /**
     * Verify token authentication distinguishes missing and invalid credentials.
     *
     * @return void
     */
    public function test_token_authentication_returns_stable_failure_codes(): void
    {
        $missing = $this->getJson('/api/v2/backend/auth/me');

        $missing
            ->assertUnauthorized()
            ->assertJsonPath('meta.error_code', 'authentication.missing_token');
        $this->assertEnvelope($missing);

        $invalid = $this->withToken('invalid-token')
            ->getJson('/api/v2/backend/auth/me');

        $invalid
            ->assertUnauthorized()
            ->assertJsonPath('meta.error_code', 'authentication.invalid_token');
        $this->assertEnvelope($invalid);
    }

    /**
     * Verify absent website context returns the standard conflict envelope.
     *
     * @return void
     */
    public function test_missing_website_returns_a_context_failure_envelope(): void
    {
        $this->actingAs(User::firstOrFail());
        Website::query()->delete();
        wncms()->cache()->flush(['websites']);

        $response = $this->getJson('/api/v2/backend/links');

        $response
            ->assertStatus(409)
            ->assertJsonPath('meta.error_code', 'website.context_missing');
        $this->assertEnvelope($response);
    }

    /**
     * Verify uncaught Laravel validation failures use the standard envelope.
     *
     * @return void
     */
    public function test_validation_exception_returns_a_validation_failure_envelope(): void
    {
        $response = $this->postJson('/api/v2/backend/auth/login', []);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('meta.error_code', 'validation.failed')
            ->assertJsonStructure(['errors' => ['email', 'password']]);
        $this->assertEnvelope($response);
    }

    /**
     * Verify authorization exceptions use a stable machine-readable code.
     *
     * @return void
     */
    public function test_authorization_exception_returns_a_denied_envelope(): void
    {
        $response = $this->getJson('/api/v2/_contract-test/authorization');

        $response
            ->assertForbidden()
            ->assertJsonPath('meta.error_code', 'authorization.denied');
        $this->assertEnvelope($response);
    }

    /**
     * Verify not-found HTTP exceptions use a stable machine-readable code.
     *
     * @return void
     */
    public function test_not_found_exception_returns_a_resource_failure_envelope(): void
    {
        $response = $this->getJson('/api/v2/_contract-test/not-found');

        $response
            ->assertNotFound()
            ->assertJsonPath('meta.error_code', 'resource.not_found');
        $this->assertEnvelope($response);
    }

    /**
     * Verify conflict HTTP exceptions use a stable machine-readable code.
     *
     * @return void
     */
    public function test_conflict_exception_returns_a_request_failure_envelope(): void
    {
        $response = $this->getJson('/api/v2/_contract-test/conflict');

        $response
            ->assertStatus(409)
            ->assertJsonPath('meta.error_code', 'request.conflict');
        $this->assertEnvelope($response);
    }

    /**
     * Verify production error responses do not expose unexpected exception details.
     *
     * @return void
     */
    public function test_unexpected_exception_message_is_hidden_when_debug_is_disabled(): void
    {
        config(['app.debug' => false]);

        $response = $this->getJson('/api/v2/_contract-test/unexpected');

        $response
            ->assertStatus(500)
            ->assertJsonPath('meta.error_code', 'server.unexpected_error');
        $this->assertStringNotContainsString('sensitive exception detail', (string) $response->getContent());
        $this->assertEnvelope($response);
    }

    /**
     * Verify production responses also hide unexpected HTTP exception details.
     *
     * @return void
     */
    public function test_unexpected_http_exception_message_is_hidden_when_debug_is_disabled(): void
    {
        config(['app.debug' => false]);

        $response = $this->getJson('/api/v2/_contract-test/http-unexpected');

        $response
            ->assertStatus(500)
            ->assertJsonPath('meta.error_code', 'server.unexpected_error');
        $this->assertStringNotContainsString('sensitive HTTP exception detail', (string) $response->getContent());
        $this->assertEnvelope($response);
    }

    /**
     * Assert exact envelope keys and matching response request IDs.
     *
     * @param  \Illuminate\Testing\TestResponse  $response
     *
     * @return void
     */
    protected function assertEnvelope($response): void
    {
        $response->assertJsonStructure([
            'code',
            'status',
            'message',
            'data',
            'meta' => ['request_id'],
            'errors',
        ]);
        $this->assertSame(
            ['code', 'status', 'message', 'data', 'meta', 'errors'],
            array_keys($response->json())
        );
        $this->assertSame(
            $response->headers->get('X-Request-ID'),
            $response->json('meta.request_id')
        );
        $this->assertTrue(Str::isUuid((string) $response->json('meta.request_id')));
    }
}

class ApiEnvelopeContractTestController extends ApiV2Controller
{
    /**
     * Return one exception through the API v2 controller compatibility adapter.
     *
     * @param  string  $type
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function respond(string $type): JsonResponse
    {
        $exception = match ($type) {
            'authorization' => new AuthorizationException('denied'),
            'not-found' => new NotFoundHttpException('missing'),
            'conflict' => new ConflictHttpException('stale request'),
            'http-unexpected' => new HttpException(500, 'sensitive HTTP exception detail'),
            default => new \RuntimeException('sensitive exception detail'),
        };

        return $this->fromThrowable($exception);
    }
}
