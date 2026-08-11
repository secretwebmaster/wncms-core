<?php

namespace Wncms\Tests\Feature\Api\V2;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Wncms\Api\V2\Contracts\AtomicOperationRepository;
use Wncms\Api\V2\Contracts\OperationRepository;
use Wncms\Api\V2\Enums\AsyncOperationStatus;
use Wncms\Api\V2\OperationService;
use Wncms\Models\User;
use Wncms\Models\Website;
use Wncms\Tests\TestCase;

class OperationEndpointTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Prepare isolated API access, cache state, and deterministic operation time.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        auth()->forgetGuards();
        Cache::flush();
        Cache::flushLocks();
        CarbonImmutable::setTestNow('2026-08-12 08:00:00 UTC');
        config([
            'wncms-api-v2.idempotency.store' => 'array',
            'wncms-api-v2.operations.store' => 'array',
            'wncms-api-v2.operations.ttl_seconds' => 86400,
        ]);
        app()->forgetInstance(AtomicOperationRepository::class);
        app()->forgetInstance(OperationRepository::class);
        app()->forgetInstance(OperationService::class);
        uss('enable_api_access', 1);
        uss('api_access_whitelist', '');
    }

    /**
     * Restore global time state after each endpoint test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    /**
     * Verify operation routes use the token-only middleware group and cancel opts into idempotency.
     *
     * @return void
     */
    public function test_operation_routes_have_explicit_middleware_order_without_website_context(): void
    {
        $show = Route::getRoutes()->getByName('api.v2.backend.operations.show');
        $cancel = Route::getRoutes()->getByName('api.v2.backend.operations.cancel');

        $this->assertNotNull($show);
        $this->assertNotNull($cancel);
        $this->assertSame('api/v2/backend/operations/{id}', $show->uri());
        $this->assertSame(['GET', 'HEAD'], $show->methods());
        $this->assertSame(['POST'], $cancel->methods());
        $this->assertSame('backend.operations.cancel', $cancel->defaults['api_operation_id'] ?? null);

        $showMiddleware = $show->gatherMiddleware();
        $cancelMiddleware = $cancel->gatherMiddleware();
        $this->assertMiddlewareOrder($showMiddleware, [
            'api_v2_request_id',
            'api_v2_whitelist',
            'api_v2_token_auth',
        ]);
        $this->assertMiddlewareOrder($cancelMiddleware, [
            'api_v2_request_id',
            'api_v2_whitelist',
            'api_v2_token_auth',
            'api_v2_idempotency',
        ]);
        $this->assertNotContains('api_v2_has_website', $showMiddleware);
        $this->assertNotContains('api_v2_has_website', $cancelMiddleware);
    }

    /**
     * Verify no public operation creation endpoint is exposed.
     *
     * @return void
     */
    public function test_operation_creation_endpoint_is_not_registered(): void
    {
        $this->assertNull(Route::getRoutes()->getByName('api.v2.backend.operations.store'));
        $this->postJson('/api/v2/backend/operations')->assertMethodNotAllowed();
    }

    /**
     * Verify operation endpoints require an authenticated actor.
     *
     * @return void
     */
    public function test_operation_lookup_requires_authentication(): void
    {
        $response = $this->getJson('/api/v2/backend/operations/missing');

        $response
            ->assertUnauthorized()
            ->assertJsonPath('meta.error_code', 'authentication.missing_token');
        $this->assertEnvelope($response);
    }

    /**
     * Verify the owning actor can inspect an operation without any website context.
     *
     * @return void
     */
    public function test_owner_can_view_operation_without_website_context(): void
    {
        $user = $this->newUser('owner');
        $service = app(OperationService::class);
        $operation = $service->queue('plugins.upgrade', (int) $user->getAuthIdentifier(), [4, 8], true);
        Website::query()->delete();
        wncms()->cache()->flush(['websites']);

        $requestId = '123e4567-e89b-42d3-a456-426614174081';
        $response = $this->actingAs($user)
            ->withHeader('X-Request-ID', $requestId)
            ->getJson("/api/v2/backend/operations/{$operation->id}");

        $response
            ->assertOk()
            ->assertHeader('X-Request-ID', $requestId)
            ->assertJsonPath('data.id', $operation->id)
            ->assertJsonPath('data.type', 'plugins.upgrade')
            ->assertJsonPath('data.status', 'queued')
            ->assertJsonPath('data.progress', 0)
            ->assertJsonPath('data.cancellable', true)
            ->assertJsonPath('data.actor_id', (int) $user->getAuthIdentifier())
            ->assertJsonPath('data.website_ids', [4, 8])
            ->assertJsonPath('data.created_at', '2026-08-12T08:00:00Z')
            ->assertJsonPath('data.expires_at', '2026-08-13T08:00:00Z');
        $this->assertEnvelope($response);
    }

    /**
     * Verify cross-actor lookup and cancellation both return indistinguishable not-found responses.
     *
     * @return void
     */
    public function test_other_actor_cannot_view_or_cancel_an_operation(): void
    {
        $owner = $this->newUser('owner');
        $other = $this->newUser('other');
        $service = app(OperationService::class);
        $operation = $service->queue('plugins.upgrade', (int) $owner->getAuthIdentifier(), [], true);

        $show = $this->actingAs($other)
            ->getJson("/api/v2/backend/operations/{$operation->id}");
        $show
            ->assertNotFound()
            ->assertJsonPath('meta.error_code', 'resource.not_found');
        $this->assertEnvelope($show);

        $cancel = $this->actingAs($other)
            ->withHeader('Idempotency-Key', 'cross-actor-cancel-0001')
            ->postJson("/api/v2/backend/operations/{$operation->id}/cancel");
        $cancel
            ->assertNotFound()
            ->assertJsonPath('meta.error_code', 'resource.not_found');
        $this->assertEnvelope($cancel);

        $this->assertSame(
            AsyncOperationStatus::Queued,
            app(OperationRepository::class)->find($operation->id)?->status
        );
    }

    /**
     * Verify expired operation identifiers return not found and disclose no cached details.
     *
     * @return void
     */
    public function test_expired_operation_returns_not_found(): void
    {
        $user = $this->newUser('expired');
        $operation = app(OperationService::class)->queue(
            'themes.install',
            (int) $user->getAuthIdentifier(),
            [],
            true
        );
        CarbonImmutable::setTestNow('2026-08-13 08:00:01 UTC');

        $response = $this->actingAs($user)
            ->getJson("/api/v2/backend/operations/{$operation->id}");

        $response
            ->assertNotFound()
            ->assertJsonPath('meta.error_code', 'resource.not_found');
        $this->assertEnvelope($response);
    }

    /**
     * Verify cancellation is replay-safe, stateful, and independent of website context.
     *
     * @return void
     */
    public function test_cancel_endpoint_is_idempotent_and_does_not_require_a_website(): void
    {
        $user = $this->newUser('cancel');
        $service = app(OperationService::class);
        $operation = $service->queue('packages.activate', (int) $user->getAuthIdentifier(), [], true);
        $service->start($operation->id);
        Website::query()->delete();
        wncms()->cache()->flush(['websites']);
        $key = 'operation-cancel-key-0001';
        $firstRequestId = '123e4567-e89b-42d3-a456-426614174082';
        $retryRequestId = '123e4567-e89b-42d3-a456-426614174083';

        $first = $this->actingAs($user)
            ->withHeaders([
                'Idempotency-Key' => $key,
                'X-Request-ID' => $firstRequestId,
            ])
            ->postJson("/api/v2/backend/operations/{$operation->id}/cancel");
        $replayed = $this->actingAs($user)
            ->withHeaders([
                'Idempotency-Key' => $key,
                'X-Request-ID' => $retryRequestId,
            ])
            ->postJson("/api/v2/backend/operations/{$operation->id}/cancel");

        $first
            ->assertOk()
            ->assertHeader('X-Request-ID', $firstRequestId)
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.cancellable', false);
        $this->assertEnvelope($first);
        $replayed
            ->assertOk()
            ->assertHeader('Idempotency-Replayed', 'true')
            ->assertHeader('X-Request-ID', $firstRequestId)
            ->assertJsonPath('data.status', 'cancelled');
        $this->assertSame($first->getContent(), $replayed->getContent());
        $this->assertSame(
            AsyncOperationStatus::Cancelled,
            app(OperationRepository::class)->find($operation->id)?->status
        );
    }

    /**
     * Verify cancel requires an idempotency key before mutating operation state.
     *
     * @return void
     */
    public function test_cancel_without_idempotency_key_does_not_mutate_operation(): void
    {
        $user = $this->newUser('missing-key');
        $operation = app(OperationService::class)->queue(
            'themes.activate',
            (int) $user->getAuthIdentifier(),
            [],
            true
        );

        $response = $this->actingAs($user)
            ->postJson("/api/v2/backend/operations/{$operation->id}/cancel");

        $response
            ->assertBadRequest()
            ->assertJsonPath('meta.error_code', 'idempotency.key_missing');
        $this->assertEnvelope($response);
        $this->assertSame(
            AsyncOperationStatus::Queued,
            app(OperationRepository::class)->find($operation->id)?->status
        );
    }

    /**
     * Verify illegal cancellation is returned through the standard conflict envelope.
     *
     * @return void
     */
    public function test_cancel_rejects_a_non_cancellable_operation_with_conflict_envelope(): void
    {
        $user = $this->newUser('non-cancellable');
        $operation = app(OperationService::class)->queue(
            'exports.posts',
            (int) $user->getAuthIdentifier()
        );

        $response = $this->actingAs($user)
            ->withHeader('Idempotency-Key', 'non-cancellable-key-0001')
            ->postJson("/api/v2/backend/operations/{$operation->id}/cancel");

        $response
            ->assertStatus(409)
            ->assertJsonPath('meta.error_code', 'request.conflict');
        $this->assertEnvelope($response);
        $this->assertSame(
            AsyncOperationStatus::Queued,
            app(OperationRepository::class)->find($operation->id)?->status
        );
    }

    /**
     * Create a distinct API actor for ownership tests.
     *
     * @param  string  $suffix
     *
     * @return \Wncms\Models\User
     */
    protected function newUser(string $suffix): User
    {
        $identity = $suffix.'-'.Str::lower(Str::random(12));

        return User::create([
            'username' => 'operation-'.$identity,
            'email' => 'operation-'.$identity.'@example.com',
            'password' => Hash::make('operation-password'),
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Assert middleware names appear in security-sensitive order.
     *
     * @param  array<int, string>  $middleware
     * @param  array<int, string>  $expected
     *
     * @return void
     */
    protected function assertMiddlewareOrder(array $middleware, array $expected): void
    {
        $positions = array_map(
            static fn (string $name): int|false => array_search($name, $middleware, true),
            $expected
        );

        foreach ($positions as $position) {
            $this->assertIsInt($position);
        }

        $sorted = $positions;
        sort($sorted);
        $this->assertSame($sorted, $positions);
    }

    /**
     * Assert the standard API v2 response envelope and request identity.
     *
     * @param  \Illuminate\Testing\TestResponse  $response
     *
     * @return void
     */
    protected function assertEnvelope($response): void
    {
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
