<?php

namespace Wncms\Tests\Feature\Api\V2;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Wncms\Api\V2\ApiResponseFactory;
use Wncms\Api\V2\Contracts\IdempotencyStore;
use Wncms\Models\User;
use Wncms\Tests\TestCase;

class IdempotencyMiddlewareTest extends TestCase
{
    use DatabaseTransactions;

    protected int $executions = 0;

    /**
     * Register isolated mutation routes for each idempotency test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        auth()->forgetGuards();
        Cache::flush();
        Cache::flushLocks();
        $this->actingAs(User::firstOrFail());

        Route::post('/api/v2/_test/idempotent/{subject}', function (Request $request, string $subject) {
            $this->executions++;

            if ($request->boolean('throw')) {
                throw new \RuntimeException('idempotency handler failure');
            }

            return app(ApiResponseFactory::class)->success([
                'execution' => $this->executions,
                'subject' => $subject,
            ], 'created', 201);
        })
            ->defaults('api_operation_id', 'backend.test.create')
            ->middleware(['api_v2_request_id', 'api_v2_idempotency']);

        Route::post('/api/v2/_test/idempotent-secondary/{subject}', function (Request $request, string $subject) {
            $this->executions++;

            return app(ApiResponseFactory::class)->success([
                'execution' => $this->executions,
                'subject' => $subject,
            ], 'created', 201);
        })
            ->defaults('api_operation_id', 'backend.test.secondary')
            ->middleware(['api_v2_request_id', 'api_v2_idempotency']);

        Route::post('/api/v2/_test/idempotent-token/{subject}', function (Request $request, string $subject) {
            $this->executions++;

            return app(ApiResponseFactory::class)->success([
                'execution' => $this->executions,
                'subject' => $subject,
            ], 'created', 201);
        })
            ->defaults('api_operation_id', 'backend.test.token')
            ->middleware(['api_v2_request_id', 'api_v2_token_auth', 'api_v2_idempotency']);
    }

    /**
     * Verify absent idempotency keys return the standard API failure envelope.
     *
     * @return void
     */
    public function test_missing_key_returns_a_stable_failure_envelope(): void
    {
        $response = $this->postJson('/api/v2/_test/idempotent/alpha', ['title' => 'One']);

        $response
            ->assertBadRequest()
            ->assertJsonPath('meta.error_code', 'idempotency.key_missing');
        $this->assertEnvelope($response);
        $this->assertSame(0, $this->executions);
    }

    /**
     * Verify keys outside the supported byte length are rejected before execution.
     *
     * @return void
     */
    public function test_key_length_is_enforced_before_execution(): void
    {
        $short = $this->postMutation(
            '/api/v2/_test/idempotent/alpha',
            ['title' => 'One'],
            'short'
        );
        $short
            ->assertBadRequest()
            ->assertJsonPath('meta.error_code', 'idempotency.key_invalid');
        $this->assertEnvelope($short);

        $long = $this->postMutation(
            '/api/v2/_test/idempotent/alpha',
            ['title' => 'One'],
            str_repeat('x', 256)
        );
        $long
            ->assertBadRequest()
            ->assertJsonPath('meta.error_code', 'idempotency.key_invalid');
        $this->assertEnvelope($long);
        $this->assertSame(0, $this->executions);
    }

    /**
     * Verify equivalent JSON and query key ordering replays the original response.
     *
     * Changing recursive sorting or fingerprinting api_token would make this execute twice.
     *
     * @return void
     */
    public function test_equivalent_normalized_input_replays_the_original_response_once(): void
    {
        $key = 'normalized-key-0001';
        $requestId = '123e4567-e89b-42d3-a456-426614174000';

        $first = $this->withHeader('X-Request-ID', $requestId)->postMutation(
            '/api/v2/_test/idempotent/alpha?beta=2&api_token=query-secret-one&alpha=1',
            [
                'settings' => ['locale' => 'en', 'enabled' => true],
                'items' => [['name' => 'first', 'rank' => 1]],
                'api_token' => 'first-secret',
            ],
            $key
        );

        $first
            ->assertCreated()
            ->assertJsonPath('data.execution', 1);
        $this->assertNull($first->headers->get('Idempotency-Replayed'));

        $replayed = $this->withHeader('X-Request-ID', $requestId)->postMutation(
            '/api/v2/_test/idempotent/alpha?alpha=1&api_token=query-secret-two&beta=2',
            [
                'api_token' => 'second-secret',
                'items' => [['rank' => 1, 'name' => 'first']],
                'settings' => ['enabled' => true, 'locale' => 'en'],
            ],
            $key
        );

        $replayed
            ->assertCreated()
            ->assertHeader('Idempotency-Replayed', 'true')
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('data.execution', 1);
        $this->assertSame($first->getContent(), $replayed->getContent());
        $this->assertSame(1, $this->executions);
    }

    /**
     * Verify equivalent form input is normalized and replayed once.
     *
     * @return void
     */
    public function test_equivalent_normalized_form_input_replays_once(): void
    {
        $key = 'normalized-form-key-01';
        $requestId = '123e4567-e89b-42d3-a456-426614174001';

        $first = $this->withHeader('X-Request-ID', $requestId)->postFormMutation(
            '/api/v2/_test/idempotent/alpha?beta=2&alpha=1',
            [
                'settings' => ['locale' => 'en', 'enabled' => '1'],
                'api_token' => 'first-form-secret',
            ],
            $key
        );

        $replayed = $this->withHeader('X-Request-ID', $requestId)->postFormMutation(
            '/api/v2/_test/idempotent/alpha?alpha=1&beta=2',
            [
                'api_token' => 'second-form-secret',
                'settings' => ['enabled' => '1', 'locale' => 'en'],
            ],
            $key
        );

        $first->assertCreated();
        $replayed
            ->assertCreated()
            ->assertHeader('Idempotency-Replayed', 'true');
        $this->assertSame($first->getContent(), $replayed->getContent());
        $this->assertSame(1, $this->executions);
    }

    /**
     * Verify a reused key with different input returns a stable conflict envelope.
     *
     * @return void
     */
    public function test_reused_key_with_different_input_returns_a_conflict(): void
    {
        $key = 'conflict-key-0001';

        $this->postMutation(
            '/api/v2/_test/idempotent/alpha?draft=0',
            ['title' => 'One'],
            $key
        )->assertCreated();

        $conflict = $this->postMutation(
            '/api/v2/_test/idempotent/alpha?draft=1',
            ['title' => 'Two'],
            $key
        );

        $conflict
            ->assertConflict()
            ->assertJsonPath('meta.error_code', 'idempotency.key_conflict');
        $this->assertEnvelope($conflict);
        $this->assertSame(1, $this->executions);
    }

    /**
     * Verify route parameters contribute to the normalized fingerprint.
     *
     * @return void
     */
    public function test_route_parameter_changes_conflict_with_an_existing_key(): void
    {
        $key = 'route-key-0000001';

        $this->postMutation(
            '/api/v2/_test/idempotent/alpha',
            ['title' => 'One'],
            $key
        )->assertCreated();

        $conflict = $this->postMutation(
            '/api/v2/_test/idempotent/beta',
            ['title' => 'One'],
            $key
        );

        $conflict
            ->assertConflict()
            ->assertJsonPath('meta.error_code', 'idempotency.key_conflict');
        $this->assertSame(1, $this->executions);
    }

    /**
     * Verify actor and operation identities isolate otherwise identical keys.
     *
     * Removing either identity from the scope would replay instead of executing each mutation.
     *
     * @return void
     */
    public function test_actor_and_operation_are_part_of_the_idempotency_scope(): void
    {
        $key = 'scoped-key-000001';
        $payload = ['title' => 'One'];

        $this->postMutation('/api/v2/_test/idempotent/alpha', $payload, $key)
            ->assertJsonPath('data.execution', 1);

        $this->postMutation('/api/v2/_test/idempotent-secondary/alpha', $payload, $key)
            ->assertJsonPath('data.execution', 2);

        $secondUser = User::create([
            'username' => 'idempotency-user-'.uniqid(),
            'email' => 'idempotency-user-'.uniqid().'@example.com',
            'password' => Hash::make('idempotency-password'),
            'email_verified_at' => now(),
        ]);
        $this->actingAs($secondUser);

        $this->postMutation('/api/v2/_test/idempotent/alpha', $payload, $key)
            ->assertJsonPath('data.execution', 3);

        $this->assertSame(3, $this->executions);
    }

    /**
     * Verify personal access token identity isolates keys for the same actor.
     *
     * Removing token identity from the scope would replay the first token's response.
     *
     * @return void
     */
    public function test_personal_access_token_identity_is_part_of_the_scope(): void
    {
        $user = User::firstOrFail();
        $firstToken = $this->createToken($user, 'first-token-secret');
        $secondToken = $this->createToken($user, 'second-token-secret');
        $key = 'token-scope-key-01';

        auth()->forgetGuards();
        $this->withToken($firstToken)
            ->postMutation('/api/v2/_test/idempotent-token/alpha', ['title' => 'One'], $key)
            ->assertJsonPath('data.execution', 1);

        auth()->forgetGuards();
        $this->withToken($secondToken)
            ->postMutation('/api/v2/_test/idempotent-token/alpha', ['title' => 'One'], $key)
            ->assertJsonPath('data.execution', 2);

        $this->assertSame(2, $this->executions);
    }

    /**
     * Verify an unavailable atomic lock returns the stable in-progress conflict.
     *
     * @return void
     */
    public function test_concurrent_lock_failure_returns_an_in_progress_conflict(): void
    {
        $store = new InspectingIdempotencyStore;
        $store->lockAvailable = false;
        app()->instance(IdempotencyStore::class, $store);

        $response = $this->postMutation(
            '/api/v2/_test/idempotent/alpha',
            ['title' => 'One'],
            'in-progress-key-01'
        );

        $response
            ->assertConflict()
            ->assertJsonPath('meta.error_code', 'idempotency.in_progress');
        $this->assertEnvelope($response);
        $this->assertSame(15, $store->lockSeconds);
        $this->assertSame(0, $this->executions);
    }

    /**
     * Verify the lock is released when the downstream handler throws.
     *
     * @return void
     */
    public function test_lock_is_released_when_the_handler_throws(): void
    {
        $store = new InspectingIdempotencyStore;
        app()->instance(IdempotencyStore::class, $store);

        $response = $this->postMutation(
            '/api/v2/_test/idempotent/alpha',
            ['throw' => true],
            'throwing-key-0001'
        );

        $response
            ->assertServerError()
            ->assertJsonPath('meta.error_code', 'server.unexpected_error');
        $this->assertTrue($store->released);
        $this->assertNull($store->record);
    }

    /**
     * Verify only the stable response record is stored with configured durations.
     *
     * @return void
     */
    public function test_completed_response_record_and_durations_match_the_contract(): void
    {
        $store = new InspectingIdempotencyStore;
        app()->instance(IdempotencyStore::class, $store);

        $response = $this->withHeader('X-Request-ID', '123e4567-e89b-42d3-a456-426614174000')
            ->postMutation(
                '/api/v2/_test/idempotent/alpha',
                ['title' => 'One', 'api_token' => 'must-not-be-stored'],
                'record-key-000001'
            );

        $response->assertCreated();
        $this->assertSame(15, $store->lockSeconds);
        $this->assertSame(86400, $store->ttlSeconds);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', (string) $store->scope);
        $this->assertSame(
            ['fingerprint', 'status', 'body', 'headers'],
            array_keys((array) $store->record)
        );
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', (string) $store->record['fingerprint']);
        $this->assertSame(201, $store->record['status']);
        $this->assertSame($response->json(), $store->record['body']);
        $this->assertSame(['Content-Type' => 'application/json'], $store->record['headers']);
        $this->assertStringNotContainsString('must-not-be-stored', json_encode($store->record, JSON_THROW_ON_ERROR));
    }

    /**
     * Verify the cache backend receives only a hashed scope key.
     *
     * @return void
     */
    public function test_cache_record_key_does_not_expose_the_raw_idempotency_key(): void
    {
        $key = 'raw-secret-idempotency-key';

        $this->postMutation(
            '/api/v2/_test/idempotent/alpha',
            ['title' => 'One'],
            $key
        )->assertCreated();

        $cacheKeys = array_keys(Cache::store()->getStore()->all(false));
        $recordKeys = array_values(array_filter(
            $cacheKeys,
            static fn (string $cacheKey): bool => str_contains($cacheKey, ':idempotency:record:')
        ));

        $this->assertCount(1, $recordKeys);
        $this->assertStringNotContainsString($key, $recordKeys[0]);
        $this->assertMatchesRegularExpression('/:[a-f0-9]{64}$/', $recordKeys[0]);
    }

    /**
     * Verify existing legacy mutations remain outside idempotency enforcement.
     *
     * @return void
     */
    public function test_legacy_mutation_routes_do_not_opt_in_implicitly(): void
    {
        $route = Route::getRoutes()->getByName('api.v2.backend.links.store');

        $this->assertNotNull($route);
        $this->assertNotContains('api_v2_idempotency', $route->gatherMiddleware());
    }

    /**
     * Send one mutation with its idempotency key.
     *
     * @param  string  $uri
     * @param  array<string, mixed>  $payload
     * @param  string  $key
     *
     * @return \Illuminate\Testing\TestResponse
     */
    protected function postMutation(string $uri, array $payload, string $key): TestResponse
    {
        return $this->withHeader('Idempotency-Key', $key)->postJson($uri, $payload);
    }

    /**
     * Send one form mutation with its idempotency key.
     *
     * @param  string  $uri
     * @param  array<string, mixed>  $payload
     * @param  string  $key
     *
     * @return \Illuminate\Testing\TestResponse
     */
    protected function postFormMutation(string $uri, array $payload, string $key): TestResponse
    {
        return $this->withHeader('Idempotency-Key', $key)
            ->post($uri, $payload, ['Accept' => 'application/json']);
    }

    /**
     * Create a personal access token fixture for an existing user.
     *
     * @param  \Wncms\Models\User  $user
     * @param  string  $plainTextToken
     *
     * @return string
     */
    protected function createToken(User $user, string $plainTextToken): string
    {
        $tokenId = DB::table('personal_access_tokens')->insertGetId([
            'tokenable_type' => get_class($user),
            'tokenable_id' => $user->id,
            'name' => 'idempotency-test',
            'token' => hash('sha256', $plainTextToken),
            'abilities' => json_encode(['*']),
            'last_used_at' => null,
            'expires_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $tokenId.'|'.$plainTextToken;
    }

    /**
     * Assert the stable API v2 response envelope and matching request IDs.
     *
     * @param  \Illuminate\Testing\TestResponse  $response
     *
     * @return void
     */
    protected function assertEnvelope(TestResponse $response): void
    {
        $this->assertSame(
            ['code', 'status', 'message', 'data', 'meta', 'errors'],
            array_keys($response->json())
        );
        $this->assertSame(
            $response->headers->get('X-Request-ID'),
            $response->json('meta.request_id')
        );
    }
}

class InspectingIdempotencyStore implements IdempotencyStore
{
    public bool $lockAvailable = true;

    public bool $released = false;

    public ?string $scope = null;

    public int $lockSeconds = 0;

    public int $ttlSeconds = 0;

    public ?array $record = null;

    /**
     * Retrieve the captured response record.
     *
     * @param  string  $scope
     *
     * @return array|null
     */
    public function get(string $scope): ?array
    {
        $this->scope = $scope;

        return $this->record;
    }

    /**
     * Capture a completed response record and TTL.
     *
     * @param  string  $scope
     * @param  array  $record
     * @param  int  $ttlSeconds
     *
     * @return void
     */
    public function put(string $scope, array $record, int $ttlSeconds): void
    {
        $this->scope = $scope;
        $this->record = $record;
        $this->ttlSeconds = $ttlSeconds;
    }

    /**
     * Return a controllable lock for the captured scope.
     *
     * @param  string  $scope
     * @param  int  $seconds
     *
     * @return \Illuminate\Contracts\Cache\Lock
     */
    public function lock(string $scope, int $seconds): \Illuminate\Contracts\Cache\Lock
    {
        $this->scope = $scope;
        $this->lockSeconds = $seconds;

        return new InspectingIdempotencyLock($this);
    }
}

class InspectingIdempotencyLock implements \Illuminate\Contracts\Cache\Lock
{
    /**
     * Create a controllable idempotency lock.
     *
     * @param  \Wncms\Tests\Feature\Api\V2\InspectingIdempotencyStore  $store
     */
    public function __construct(protected InspectingIdempotencyStore $store)
    {
    }

    /**
     * Attempt to acquire the configured test lock.
     *
     * @param  callable|null  $callback
     *
     * @return mixed
     */
    public function get($callback = null): mixed
    {
        if (! $this->store->lockAvailable) {
            return false;
        }

        if (is_callable($callback)) {
            try {
                return $callback();
            } finally {
                $this->release();
            }
        }

        return true;
    }

    /**
     * Acquire the test lock without waiting.
     *
     * @param  int  $seconds
     * @param  callable|null  $callback
     *
     * @return mixed
     */
    public function block($seconds, $callback = null): mixed
    {
        return $this->get($callback);
    }

    /**
     * Release the test lock.
     *
     * @return bool
     */
    public function release(): bool
    {
        $this->store->released = true;

        return true;
    }

    /**
     * Return the stable test lock owner.
     *
     * @return string
     */
    public function owner(): string
    {
        return 'idempotency-test-owner';
    }

    /**
     * Force release the test lock.
     *
     * @return void
     */
    public function forceRelease(): void
    {
        $this->store->released = true;
    }
}
