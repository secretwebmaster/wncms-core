<?php

namespace Wncms\Tests\Feature\Api\V2;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Wncms\Api\V2\ApiResponseFactory;
use Wncms\Api\V2\ApiV2ResponseFinalizer;
use Wncms\Api\V2\Contracts\IdempotencyStore;
use Wncms\Models\User;
use Wncms\Models\Website;
use Wncms\Tests\TestCase;

class IdempotencyMiddlewareTest extends TestCase
{
    use DatabaseTransactions;

    protected int $executions = 0;

    /**
     * @var array<int, resource>
     */
    protected array $uploadStreams = [];

    /**
     * @var array<int, string>
     */
    protected array $cacheDirectories = [];

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
        config(['wncms-api-v2.idempotency.store' => 'array']);
        $this->actingAs(User::firstOrFail());
        $this->app['router']->aliasMiddleware(
            'api_v2_test_website_context',
            TestWebsiteContextMiddleware::class
        );
        $this->app['router']->aliasMiddleware(
            'api_v2_test_retain_response',
            TestRetainApiV2ResponseMiddleware::class
        );

        Route::post('/api/v2/_test/idempotent/{subject}', function (Request $request, string $subject) {
            $this->executions++;

            if ($request->boolean('throw')) {
                throw new \RuntimeException('idempotency handler failure');
            }

            if ($request->boolean('fail_once') && $this->executions === 1) {
                return app(ApiResponseFactory::class)->failure(
                    'server.temporary_failure',
                    'temporary failure',
                    500
                );
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

        Route::post('/api/v2/_test/idempotent-website/{subject}', function (string $subject) {
            $this->executions++;

            return app(ApiResponseFactory::class)->success([
                'execution' => $this->executions,
                'subject' => $subject,
            ], 'created', 201);
        })
            ->defaults('api_operation_id', 'backend.test.create')
            ->middleware(['api_v2_request_id', 'api_v2_has_website', 'api_v2_idempotency']);

        Route::post('/api/v2/_test/idempotent-wire/{shape}', function (string $shape) {
            $this->executions++;

            [$json, $contentType] = match ($shape) {
                'object' => ['{}', 'application/json'],
                'list' => ['[]', 'application/json'],
                'numeric-object' => ['{"0":"zero","1":"one"}', 'application/json'],
                'scalar' => ['"scalar-root"', 'application/problem+json'],
            };

            return JsonResponse::fromJsonString($json, 202, ['Content-Type' => $contentType]);
        })
            ->defaults('api_operation_id', 'backend.test.wire')
            ->middleware(['api_v2_request_id', 'api_v2_idempotency']);

        Route::post('/api/v2/_test/idempotent-spoofed-request-id/{subject}', function (string $subject) {
            $this->executions++;

            return app(ApiResponseFactory::class)->success([
                'execution' => $this->executions,
                'subject' => $subject,
            ], 'created', 201)->header(
                'X-Request-ID',
                '123e4567-e89b-42d3-a456-426614174099'
            );
        })
            ->defaults('api_operation_id', 'backend.test.spoofed-request-id')
            ->middleware(['api_v2_request_id', 'api_v2_idempotency']);

        Route::post('/api/v2/_test/idempotent-invalid-response/{kind}', function (string $kind) {
            $this->executions++;

            $json = match ($kind) {
                'malformed' => '{"broken":',
                'depth' => str_repeat('[', 513).'0'.str_repeat(']', 513),
            };

            return JsonResponse::fromJsonString($json, 202, ['Content-Type' => 'application/json']);
        })
            ->defaults('api_operation_id', 'backend.test.invalid-response')
            ->middleware(['api_v2_request_id', 'api_v2_idempotency']);

        Route::post('/api/v2/_test/idempotent-model-website/{subject}', function (string $subject) {
            $this->executions++;

            return app(ApiResponseFactory::class)->success([
                'execution' => $this->executions,
                'subject' => $subject,
            ], 'created', 201);
        })
            ->defaults('api_operation_id', 'backend.test.model-website')
            ->middleware([
                'api_v2_request_id',
                'api_v2_test_website_context',
                'api_v2_idempotency',
            ]);

        Route::get('/api/v2/_test/request-id-forgery', function () {
            $forgedRequestId = '123e4567-e89b-42d3-a456-426614174099';
            $response = app(ApiResponseFactory::class)->success([], 'forgery attempt');
            $response->headers->set('X-Request-ID', $forgedRequestId);

            $finalizer = app(ApiV2ResponseFinalizer::class);
            if (is_callable([$finalizer, 'markTrustedReplay'])) {
                $finalizer->markTrustedReplay($response, $forgedRequestId);
            }

            return $response;
        })->middleware(['api_v2_request_id']);

        Route::get('/api/v2/_test/request-id-attribute-mutation', function (Request $request) {
            $request->attributes->set(
                'wncms_api_v2_request_id',
                '123e4567-e89b-42d3-a456-426614174098'
            );

            return app(ApiResponseFactory::class)->success([], 'attribute mutation');
        })->middleware(['api_v2_request_id']);

        Route::post('/api/v2/_test/idempotent-request-id-attribute-mutation', function (Request $request) {
            $this->executions++;
            $request->attributes->set(
                'wncms_api_v2_request_id',
                '123e4567-e89b-42d3-a456-426614174097'
            );

            return app(ApiResponseFactory::class)->success([
                'execution' => $this->executions,
            ], 'attribute mutation', 201);
        })
            ->defaults('api_operation_id', 'backend.test.request-id-attribute-mutation')
            ->middleware(['api_v2_request_id', 'api_v2_idempotency']);

        Route::post('/api/v2/_test/idempotent-retained-response', function () {
            $this->executions++;

            return app(ApiResponseFactory::class)->success([
                'execution' => $this->executions,
            ], 'retained response', 201);
        })
            ->defaults('api_operation_id', 'backend.test.retained-response')
            ->middleware([
                'api_v2_request_id',
                'api_v2_test_retain_response',
                'api_v2_idempotency',
            ]);

        Route::get('/api/v2/_test/reuse-retained-response', function () {
            if (! TestRetainApiV2ResponseMiddleware::$response instanceof Response) {
                throw new \RuntimeException('Retained response is unavailable');
            }

            return TestRetainApiV2ResponseMiddleware::$response;
        })->middleware(['api_v2_request_id']);
    }

    /**
     * Close temporary upload streams created by the test fixtures.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        TestWebsiteContextMiddleware::$website = null;
        TestRetainApiV2ResponseMiddleware::$response = null;

        foreach ($this->uploadStreams as $stream) {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        foreach ($this->cacheDirectories as $directory) {
            File::deleteDirectory($directory);
        }

        parent::tearDown();
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
     * Verify replay keeps the first finalized request ID when the retry sends a different ID.
     *
     * Trusting an ordinary handler response header would also allow it to bypass request-ID finalization.
     *
     * @return void
     */
    public function test_replay_preserves_the_first_finalized_response_across_different_request_ids(): void
    {
        $firstRequestId = '123e4567-e89b-42d3-a456-426614174010';
        $retryRequestId = '123e4567-e89b-42d3-a456-426614174011';
        $key = 'finalized-response-key-01';
        $payload = ['title' => 'One'];

        $first = $this->withHeader('X-Request-ID', $firstRequestId)->postMutation(
            '/api/v2/_test/idempotent-spoofed-request-id/alpha',
            $payload,
            $key
        );

        $first
            ->assertCreated()
            ->assertHeader('X-Request-ID', $firstRequestId)
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('meta.request_id', $firstRequestId);
        $this->assertNull($first->headers->get('Idempotency-Replayed'));

        $replayed = $this->withHeader('X-Request-ID', $retryRequestId)->postMutation(
            '/api/v2/_test/idempotent-spoofed-request-id/alpha',
            $payload,
            $key
        );

        $replayed
            ->assertCreated()
            ->assertHeader('X-Request-ID', $firstRequestId)
            ->assertHeader('Content-Type', 'application/json')
            ->assertHeader('Idempotency-Replayed', 'true')
            ->assertJsonPath('meta.request_id', $firstRequestId);
        $this->assertSame($first->getContent(), $replayed->getContent());
        $this->assertSame(1, $this->executions);
    }

    /**
     * Verify generated request IDs also replay the first response identity exactly.
     *
     * @return void
     */
    public function test_replay_preserves_the_first_automatically_generated_request_id(): void
    {
        $key = 'generated-request-id-key-01';
        $payload = ['title' => 'One'];

        $first = $this->postMutation('/api/v2/_test/idempotent/alpha', $payload, $key);
        $firstRequestId = (string) $first->headers->get('X-Request-ID');

        $first->assertCreated();
        $this->assertNotSame('', $firstRequestId);
        $this->assertSame($firstRequestId, $first->json('meta.request_id'));
        $this->assertNull($first->headers->get('Idempotency-Replayed'));

        $replayed = $this->postMutation('/api/v2/_test/idempotent/alpha', $payload, $key);

        $replayed
            ->assertCreated()
            ->assertHeader('X-Request-ID', $firstRequestId)
            ->assertHeader('Idempotency-Replayed', 'true')
            ->assertJsonPath('meta.request_id', $firstRequestId);
        $this->assertSame($first->getContent(), $replayed->getContent());
        $this->assertSame(1, $this->executions);
    }

    /**
     * Verify handlers cannot forge replay trust through headers or the finalizer API.
     *
     * A public replay marker would let the handler replace the request identity selected by middleware.
     *
     * @return void
     */
    public function test_handler_cannot_forge_replay_trust_through_headers_or_public_finalizer_api(): void
    {
        $requestId = '123e4567-e89b-42d3-a456-426614174020';
        $forgedRequestId = '123e4567-e89b-42d3-a456-426614174099';

        $response = $this->withHeader('X-Request-ID', $requestId)
            ->getJson('/api/v2/_test/request-id-forgery');

        $response
            ->assertOk()
            ->assertHeader('X-Request-ID', $requestId)
            ->assertJsonPath('meta.request_id', $requestId);
        $this->assertStringNotContainsString($forgedRequestId, (string) $response->getContent());
        $this->assertFalse(is_callable([
            app(ApiV2ResponseFinalizer::class),
            'markTrustedReplay',
        ]));
    }

    /**
     * Verify ordinary response finalization uses the request ID captured before the handler.
     *
     * Rereading the request attribute after downstream execution would accept the handler mutation.
     *
     * @return void
     */
    public function test_downstream_attribute_mutation_cannot_replace_an_ordinary_response_request_id(): void
    {
        $requestId = '123e4567-e89b-42d3-a456-426614174021';

        $response = $this->withHeader('X-Request-ID', $requestId)
            ->getJson('/api/v2/_test/request-id-attribute-mutation');

        $response
            ->assertOk()
            ->assertHeader('X-Request-ID', $requestId)
            ->assertJsonPath('meta.request_id', $requestId);
    }

    /**
     * Verify idempotency stores the request ID captured before the mutation handler.
     *
     * Trusting the downstream attribute would persist its forged ID in the first record and every replay.
     *
     * @return void
     */
    public function test_downstream_attribute_mutation_cannot_replace_the_cached_response_request_id(): void
    {
        $firstRequestId = '123e4567-e89b-42d3-a456-426614174022';
        $retryRequestId = '123e4567-e89b-42d3-a456-426614174023';
        $key = 'captured-request-id-key-01';
        $payload = ['title' => 'One'];

        $first = $this->withHeader('X-Request-ID', $firstRequestId)->postMutation(
            '/api/v2/_test/idempotent-request-id-attribute-mutation',
            $payload,
            $key
        );
        $replayed = $this->withHeader('X-Request-ID', $retryRequestId)->postMutation(
            '/api/v2/_test/idempotent-request-id-attribute-mutation',
            $payload,
            $key
        );

        $first
            ->assertCreated()
            ->assertHeader('X-Request-ID', $firstRequestId)
            ->assertJsonPath('meta.request_id', $firstRequestId);
        $replayed
            ->assertCreated()
            ->assertHeader('X-Request-ID', $firstRequestId)
            ->assertHeader('Idempotency-Replayed', 'true')
            ->assertJsonPath('meta.request_id', $firstRequestId);
        $this->assertSame($first->getContent(), $replayed->getContent());
        $this->assertSame(1, $this->executions);
    }

    /**
     * Verify replay trust is consumed by one outer finalization only.
     *
     * Retaining and returning the replay Response on a later request must use that later request's identity.
     *
     * @return void
     */
    public function test_replay_response_trust_is_one_shot_when_the_response_object_is_reused(): void
    {
        $firstRequestId = '123e4567-e89b-42d3-a456-426614174024';
        $retryRequestId = '123e4567-e89b-42d3-a456-426614174025';
        $laterRequestId = '123e4567-e89b-42d3-a456-426614174026';
        $key = 'retained-response-key-01';
        $payload = ['title' => 'One'];

        $first = $this->withHeader('X-Request-ID', $firstRequestId)->postMutation(
            '/api/v2/_test/idempotent-retained-response',
            $payload,
            $key
        );
        $replayed = $this->withHeader('X-Request-ID', $retryRequestId)->postMutation(
            '/api/v2/_test/idempotent-retained-response',
            $payload,
            $key
        );

        $first
            ->assertCreated()
            ->assertHeader('X-Request-ID', $firstRequestId);
        $replayed
            ->assertCreated()
            ->assertHeader('X-Request-ID', $firstRequestId)
            ->assertHeader('Idempotency-Replayed', 'true')
            ->assertJsonPath('meta.request_id', $firstRequestId);
        $this->assertInstanceOf(Response::class, TestRetainApiV2ResponseMiddleware::$response);

        $reused = $this->withHeader('X-Request-ID', $laterRequestId)
            ->getJson('/api/v2/_test/reuse-retained-response');

        $reused
            ->assertCreated()
            ->assertHeader('X-Request-ID', $laterRequestId)
            ->assertJsonPath('meta.request_id', $laterRequestId);
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
     * Verify JSON objects, lists, numeric-key objects, and scalar roots retain their wire shapes.
     *
     * Associative decoding would collapse each object/list pair into the same PHP array.
     *
     * @return void
     */
    public function test_json_wire_shapes_remain_distinct_in_request_fingerprints(): void
    {
        $this->postRawJsonMutation('/api/v2/_test/idempotent/alpha', '{}', 'json-shape-empty-01')
            ->assertCreated();
        $this->postRawJsonMutation('/api/v2/_test/idempotent/alpha', '[]', 'json-shape-empty-01')
            ->assertConflict()
            ->assertJsonPath('meta.error_code', 'idempotency.key_conflict');

        $this->postRawJsonMutation(
            '/api/v2/_test/idempotent/alpha',
            '{"0":"first","1":"second"}',
            'json-shape-numeric-01'
        )->assertCreated();
        $this->postRawJsonMutation(
            '/api/v2/_test/idempotent/alpha',
            '["first","second"]',
            'json-shape-numeric-01'
        )
            ->assertConflict()
            ->assertJsonPath('meta.error_code', 'idempotency.key_conflict');

        $firstScalar = $this->postRawJsonMutation(
            '/api/v2/_test/idempotent/alpha',
            '"scalar-root"',
            'json-shape-scalar-01'
        );
        $replayedScalar = $this->postRawJsonMutation(
            '/api/v2/_test/idempotent/alpha',
            '"scalar-root"',
            'json-shape-scalar-01'
        );

        $firstScalar->assertCreated();
        $replayedScalar
            ->assertCreated()
            ->assertHeader('Idempotency-Replayed', 'true');
        $this->assertSame($firstScalar->getContent(), $replayedScalar->getContent());
        $this->assertSame(3, $this->executions);
    }

    /**
     * Verify malformed JSON fails before executing the mutation.
     *
     * @return void
     */
    public function test_malformed_json_returns_a_stable_idempotency_failure(): void
    {
        $response = $this->postRawJsonMutation(
            '/api/v2/_test/idempotent/alpha',
            '{"broken":',
            'malformed-json-key-01'
        );

        $response
            ->assertBadRequest()
            ->assertJsonPath('meta.error_code', 'idempotency.payload_invalid');
        $this->assertEnvelope($response);
        $this->assertSame(0, $this->executions);
    }

    /**
     * Verify multipart file paths and metadata contribute to a deterministic fingerprint.
     *
     * Ignoring any uploaded file would replay instead of conflicting for these mutations.
     *
     * @return void
     */
    public function test_multipart_files_are_fingerprinted_by_path_name_size_mime_and_content(): void
    {
        $uri = '/api/v2/_test/idempotent/alpha';
        $baseline = fn (): UploadedFile => $this->uploadedFile('clip.txt', 'alpha', 'text/plain');
        $preview = fn (): UploadedFile => $this->uploadedFile('preview.txt', 'preview', 'text/plain');
        $this->withHeader('X-Request-ID', '123e4567-e89b-42d3-a456-426614174002');

        $first = $this->postUploadMutation($uri, [
            'attachment' => $baseline(),
            'preview' => $preview(),
        ], 'upload-stable-key-01');
        $replayed = $this->postUploadMutation($uri, [
            'preview' => $preview(),
            'attachment' => $baseline(),
        ], 'upload-stable-key-01');

        $first->assertCreated();
        $replayed
            ->assertCreated()
            ->assertHeader('Idempotency-Replayed', 'true');
        $this->assertSame($first->getContent(), $replayed->getContent());

        $variants = [
            'upload-path-key-0001' => ['other_attachment' => $baseline()],
            'upload-name-key-0001' => ['attachment' => $this->uploadedFile('other.txt', 'alpha', 'text/plain')],
            'upload-size-key-0001' => ['attachment' => $this->uploadedFile('clip.txt', 'alpha-longer', 'text/plain')],
            'upload-mime-key-0001' => ['attachment' => $this->uploadedFile('clip.txt', 'alpha', 'application/octet-stream')],
            'upload-hash-key-0001' => ['attachment' => $this->uploadedFile('clip.txt', 'bravo', 'text/plain')],
        ];

        foreach ($variants as $key => $variant) {
            $this->postUploadMutation($uri, ['attachment' => $baseline()], $key)->assertCreated();

            $this->postUploadMutation($uri, $variant, $key)
                ->assertConflict()
                ->assertJsonPath('meta.error_code', 'idempotency.key_conflict');
        }

        $this->assertSame(6, $this->executions);
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
     * Verify trusted website identity isolates scopes without trusting the raw Host value.
     *
     * Omitting the resolved website ID would replay across the sentinel and secondary website.
     * Using Host directly would fail to replay the alias request for the same website object.
     *
     * @return void
     */
    public function test_resolved_website_identity_and_no_context_sentinel_isolate_scopes(): void
    {
        $user = User::firstOrFail();
        $primary = Website::firstOrFail();
        $primary->update(['domain' => 'primary-idempotency.test']);
        $primary->domain_aliases()->create(['domain' => 'alias-idempotency.test']);

        $secondary = Website::create([
            'user_id' => $user->id,
            'domain' => 'secondary-idempotency.test',
            'site_name' => 'Secondary Idempotency Website',
            'theme' => 'default',
        ]);
        $user->websites()->syncWithoutDetaching([$primary->id, $secondary->id]);
        wncms()->cache()->flush(['websites']);

        $key = 'website-scope-key-01';
        $payload = ['title' => 'One'];

        $this->postMutation('/api/v2/_test/idempotent/alpha', $payload, $key)
            ->assertCreated()
            ->assertJsonPath('data.execution', 1);

        $primaryResponse = $this->postMutation(
            'http://primary-idempotency.test/api/v2/_test/idempotent-website/alpha',
            $payload,
            $key
        );
        $primaryResponse
            ->assertCreated()
            ->assertJsonPath('data.execution', 2);
        $this->assertNull($primaryResponse->headers->get('Idempotency-Replayed'));

        $this->postMutation(
            'http://alias-idempotency.test/api/v2/_test/idempotent-website/alpha',
            $payload,
            $key
        )
            ->assertCreated()
            ->assertHeader('Idempotency-Replayed', 'true')
            ->assertJsonPath('data.execution', 2);

        $secondaryResponse = $this->postMutation(
            'http://secondary-idempotency.test/api/v2/_test/idempotent-website/alpha',
            $payload,
            $key
        );
        $secondaryResponse
            ->assertCreated()
            ->assertJsonPath('data.execution', 3);
        $this->assertNull($secondaryResponse->headers->get('Idempotency-Replayed'));
        $this->assertSame(3, $this->executions);
    }

    /**
     * Verify website scope uses the stable model primary key rather than a mutable route key.
     *
     * @return void
     */
    public function test_website_scope_survives_route_key_changes_and_isolates_primary_keys(): void
    {
        $primary = new MutableRouteKeyWebsite();
        $primary->setAttribute('id', 101);
        $primary->mutableRouteKey = 'route-key-alpha';
        TestWebsiteContextMiddleware::$website = $primary;

        $uri = '/api/v2/_test/idempotent-model-website/alpha';
        $key = 'stable-website-key-01';
        $payload = ['title' => 'One'];

        $first = $this->postMutation($uri, $payload, $key);
        $first
            ->assertCreated()
            ->assertJsonPath('data.execution', 1);
        $this->assertNull($first->headers->get('Idempotency-Replayed'));

        $primary->mutableRouteKey = 'route-key-beta';
        $replayed = $this->postMutation($uri, $payload, $key);
        $replayed
            ->assertCreated()
            ->assertHeader('Idempotency-Replayed', 'true')
            ->assertJsonPath('data.execution', 1);

        $secondary = new MutableRouteKeyWebsite();
        $secondary->setAttribute('id', 202);
        $secondary->mutableRouteKey = 'route-key-beta';
        TestWebsiteContextMiddleware::$website = $secondary;

        $isolated = $this->postMutation($uri, $payload, $key);
        $isolated
            ->assertCreated()
            ->assertJsonPath('data.execution', 2);
        $this->assertNull($isolated->headers->get('Idempotency-Replayed'));
        $this->assertSame(2, $this->executions);
    }

    /**
     * Verify an unavailable atomic lock returns the stable in-progress conflict.
     *
     * @return void
     */
    public function test_concurrent_lock_failure_returns_an_in_progress_conflict(): void
    {
        $store = new InspectingIdempotencyStore();
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
        $store = new InspectingIdempotencyStore();
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
     * Verify server-error responses remain retryable and are never replayed.
     *
     * Caching the first response would make the second request replay the temporary 500.
     *
     * @return void
     */
    public function test_server_error_response_is_not_recorded_and_can_be_retried(): void
    {
        $key = 'retry-server-error-01';
        $payload = ['fail_once' => true];

        $first = $this->postMutation('/api/v2/_test/idempotent/alpha', $payload, $key);

        $first
            ->assertServerError()
            ->assertJsonPath('meta.error_code', 'server.temporary_failure');
        $this->assertNull($first->headers->get('Idempotency-Replayed'));

        $second = $this->postMutation('/api/v2/_test/idempotent/alpha', $payload, $key);

        $second
            ->assertCreated()
            ->assertJsonPath('data.execution', 2);
        $this->assertNull($second->headers->get('Idempotency-Replayed'));

        $this->postMutation('/api/v2/_test/idempotent/alpha', $payload, $key)
            ->assertCreated()
            ->assertHeader('Idempotency-Replayed', 'true')
            ->assertJsonPath('data.execution', 2);
        $this->assertSame(2, $this->executions);
    }

    /**
     * Verify replay preserves JSON root types, body bytes, status, and content type.
     *
     * Associative response decoding would turn empty and numeric-key objects or scalars into arrays.
     *
     * @return void
     */
    public function test_replay_preserves_exact_json_wire_types_and_content_type(): void
    {
        $requestId = '123e4567-e89b-42d3-a456-426614174004';
        $retryRequestId = '123e4567-e89b-42d3-a456-426614174005';

        $cases = [
            'object' => ['{"meta":{"request_id":"'.$requestId.'"}}', 'application/json'],
            'list' => ['[]', 'application/json'],
            'numeric-object' => [
                '{"0":"zero","1":"one","meta":{"request_id":"'.$requestId.'"}}',
                'application/json',
            ],
            'scalar' => ['"scalar-root"', 'application/problem+json'],
        ];

        foreach ($cases as $shape => [$expectedBody, $expectedContentType]) {
            $uri = "/api/v2/_test/idempotent-wire/{$shape}";
            $key = "wire-type-{$shape}-key";

            $first = $this->withHeader('X-Request-ID', $requestId)
                ->postMutation($uri, ['title' => 'One'], $key);
            $replayed = $this->withHeader('X-Request-ID', $retryRequestId)
                ->postMutation($uri, ['title' => 'One'], $key);

            $first
                ->assertStatus(202)
                ->assertHeader('X-Request-ID', $requestId)
                ->assertHeader('Content-Type', $expectedContentType);
            $replayed
                ->assertStatus(202)
                ->assertHeader('X-Request-ID', $requestId)
                ->assertHeader('Content-Type', $expectedContentType)
                ->assertHeader('Idempotency-Replayed', 'true');
            $this->assertSame($expectedBody, $first->getContent());
            $this->assertSame($first->getContent(), $replayed->getContent());
        }

        $this->assertSame(4, $this->executions);
    }

    /**
     * Verify malformed and over-depth JSON responses are never persisted for replay.
     *
     * Skipping strict final-body validation would return and cache both invalid payloads.
     *
     * @return void
     */
    public function test_invalid_final_json_body_is_not_recorded_and_releases_the_lock(): void
    {
        foreach (['malformed', 'depth'] as $kind) {
            $store = new InspectingIdempotencyStore();
            app()->instance(IdempotencyStore::class, $store);
            $uri = "/api/v2/_test/idempotent-invalid-response/{$kind}";
            $key = "invalid-response-{$kind}-key";

            $first = $this->postMutation($uri, ['title' => 'One'], $key);

            $first
                ->assertServerError()
                ->assertJsonPath('meta.error_code', 'server.unexpected_error');
            $this->assertEnvelope($first);
            $this->assertTrue($store->released);
            $this->assertNull($store->record);

            $store->released = false;
            $retry = $this->postMutation($uri, ['title' => 'One'], $key);

            $retry
                ->assertServerError()
                ->assertJsonPath('meta.error_code', 'server.unexpected_error');
            $this->assertEnvelope($retry);
            $this->assertTrue($store->released);
            $this->assertNull($store->record);
        }

        $this->assertSame(4, $this->executions);
    }

    /**
     * Verify a cache backend that reports a failed write cannot return an unprotected success.
     *
     * Ignoring the false write result would return 201 and execute the same mutation again later.
     *
     * @return void
     */
    public function test_cache_record_write_failure_fails_closed_and_remains_retryable(): void
    {
        config([
            'cache.stores.idempotency_null' => ['driver' => 'null'],
            'wncms-api-v2.idempotency.store' => 'idempotency_null',
        ]);
        app('cache')->forgetDriver('idempotency_null');
        app()->forgetInstance(IdempotencyStore::class);

        $key = 'failed-cache-write-01';
        $payload = ['title' => 'One'];

        $first = $this->postMutation('/api/v2/_test/idempotent/alpha', $payload, $key);
        $first
            ->assertServerError()
            ->assertJsonPath('meta.error_code', 'server.unexpected_error');
        $this->assertEnvelope($first);
        $this->assertNull($first->headers->get('Idempotency-Replayed'));

        $second = $this->postMutation('/api/v2/_test/idempotent/alpha', $payload, $key);
        $second
            ->assertServerError()
            ->assertJsonPath('meta.error_code', 'server.unexpected_error');
        $this->assertSame(2, $this->executions);
    }

    /**
     * Verify store exceptions use the standard envelope, release the lock, and permit a retry.
     *
     * @return void
     */
    public function test_cache_record_write_exception_releases_the_lock_and_remains_retryable(): void
    {
        $store = new InspectingIdempotencyStore();
        $store->putThrows = true;
        app()->instance(IdempotencyStore::class, $store);

        $key = 'throwing-cache-write-01';
        $payload = ['title' => 'One'];
        $failed = $this->postMutation('/api/v2/_test/idempotent/alpha', $payload, $key);

        $failed
            ->assertServerError()
            ->assertJsonPath('meta.error_code', 'server.unexpected_error');
        $this->assertEnvelope($failed);
        $this->assertTrue($store->released);
        $this->assertNull($store->record);

        $store->putThrows = false;
        $retry = $this->postMutation('/api/v2/_test/idempotent/alpha', $payload, $key);

        $retry
            ->assertCreated()
            ->assertJsonPath('data.execution', 2);
        $this->assertNull($retry->headers->get('Idempotency-Replayed'));
        $this->assertSame(2, $this->executions);
    }

    /**
     * Verify production rejects ephemeral or non-locking resolved cache backends.
     *
     * A null idempotency store config intentionally resolves Laravel's default store; it is
     * distinct from a named cache store whose driver is NullStore.
     *
     * @return void
     */
    public function test_production_rejects_unsafe_resolved_cache_backends_before_execution(): void
    {
        config([
            'cache.stores.idempotency_null' => ['driver' => 'null'],
            'cache.stores.idempotency_storage' => [
                'driver' => 'storage',
                'disk' => 'local',
                'path' => 'idempotency-tests',
            ],
        ]);
        $this->app->detectEnvironment(static fn (): string => 'production');

        try {
            $unsafeConfigurations = [
                'default-array' => [null, 'array'],
                'named-null-driver' => ['idempotency_null', 'array'],
                'non-locking-storage-driver' => ['idempotency_storage', 'array'],
                'invalid-config-value' => [['array'], 'array'],
            ];

            foreach ($unsafeConfigurations as $case => [$idempotencyStore, $defaultStore]) {
                config([
                    'cache.default' => $defaultStore,
                    'wncms-api-v2.idempotency.store' => $idempotencyStore,
                ]);
                app()->forgetInstance(IdempotencyStore::class);

                $response = $this->postMutation(
                    '/api/v2/_test/idempotent/alpha',
                    ['case' => $case],
                    "unsafe-store-{$case}"
                );

                $response
                    ->assertServerError()
                    ->assertJsonPath('meta.error_code', 'server.unexpected_error');
                $this->assertEnvelope($response);
            }
        } finally {
            $this->app->detectEnvironment(static fn (): string => 'testing');
        }

        $this->assertSame(0, $this->executions);
    }

    /**
     * Verify production rejects a failover store even when its first backend supports locks.
     *
     * Accepting the wrapper could split the record and lock across different fallback stores.
     *
     * @return void
     */
    public function test_production_rejects_an_array_first_failover_cache_store(): void
    {
        $cacheDirectory = sys_get_temp_dir().'/wncms-idempotency-'.bin2hex(random_bytes(8));
        $this->cacheDirectories[] = $cacheDirectory;
        config([
            'cache.stores.idempotency_file' => [
                'driver' => 'file',
                'path' => $cacheDirectory,
                'lock_path' => $cacheDirectory,
            ],
            'cache.stores.idempotency_failover' => [
                'driver' => 'failover',
                'stores' => ['array', 'idempotency_file'],
            ],
            'wncms-api-v2.idempotency.store' => 'idempotency_failover',
        ]);
        app('cache')->forgetDriver('idempotency_file');
        app('cache')->forgetDriver('idempotency_failover');
        app()->forgetInstance(IdempotencyStore::class);
        $this->app->detectEnvironment(static fn (): string => 'production');

        try {
            $response = $this->postMutation(
                '/api/v2/_test/idempotent/alpha',
                ['title' => 'One'],
                'failover-store-key-01'
            );
        } finally {
            $this->app->detectEnvironment(static fn (): string => 'testing');
        }

        $response
            ->assertServerError()
            ->assertJsonPath('meta.error_code', 'server.unexpected_error');
        $this->assertEnvelope($response);
        $this->assertSame(0, $this->executions);
    }

    /**
     * Verify a safe atomic file store remains usable outside production.
     *
     * @return void
     */
    public function test_non_production_accepts_a_safe_atomic_file_cache_store(): void
    {
        $cacheDirectory = sys_get_temp_dir().'/wncms-idempotency-'.bin2hex(random_bytes(8));
        $this->cacheDirectories[] = $cacheDirectory;
        config([
            'cache.stores.idempotency_file' => [
                'driver' => 'file',
                'path' => $cacheDirectory,
                'lock_path' => $cacheDirectory,
            ],
            'wncms-api-v2.idempotency.store' => 'idempotency_file',
        ]);
        app('cache')->forgetDriver('idempotency_file');
        app()->forgetInstance(IdempotencyStore::class);

        $key = 'non-production-file-key-01';
        $payload = ['title' => 'One'];
        $first = $this->postMutation('/api/v2/_test/idempotent/alpha', $payload, $key);
        $replayed = $this->postMutation('/api/v2/_test/idempotent/alpha', $payload, $key);

        $first->assertCreated();
        $replayed
            ->assertCreated()
            ->assertHeader('Idempotency-Replayed', 'true')
            ->assertJsonPath('data.execution', 1);
        $this->assertSame(1, $this->executions);
    }

    /**
     * Verify a null idempotency config uses a safe Laravel default store in production.
     *
     * @return void
     */
    public function test_production_accepts_a_shared_locking_default_cache_store(): void
    {
        $cacheDirectory = sys_get_temp_dir().'/wncms-idempotency-'.bin2hex(random_bytes(8));
        $this->cacheDirectories[] = $cacheDirectory;
        config([
            'cache.default' => 'idempotency_file',
            'cache.stores.idempotency_file' => [
                'driver' => 'file',
                'path' => $cacheDirectory,
                'lock_path' => $cacheDirectory,
            ],
            'wncms-api-v2.idempotency.store' => null,
        ]);
        app('cache')->forgetDriver('idempotency_file');
        app()->forgetInstance(IdempotencyStore::class);
        $this->app->detectEnvironment(static fn (): string => 'production');

        try {
            $key = 'shared-default-store-01';
            $first = $this->postMutation(
                '/api/v2/_test/idempotent/alpha',
                ['title' => 'One'],
                $key
            );
            $replayed = $this->postMutation(
                '/api/v2/_test/idempotent/alpha',
                ['title' => 'One'],
                $key
            );
        } finally {
            $this->app->detectEnvironment(static fn (): string => 'testing');
        }

        $first->assertCreated();
        $replayed
            ->assertCreated()
            ->assertHeader('Idempotency-Replayed', 'true')
            ->assertJsonPath('data.execution', 1);
        $this->assertSame(1, $this->executions);
    }

    /**
     * Verify only the stable response record is stored with configured durations.
     *
     * @return void
     */
    public function test_completed_response_record_and_durations_match_the_contract(): void
    {
        $store = new InspectingIdempotencyStore();
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
        $this->assertSame($response->getContent(), $store->record['body']);
        $this->assertSame([
            'Content-Type' => 'application/json',
            'X-Request-ID' => '123e4567-e89b-42d3-a456-426614174000',
        ], $store->record['headers']);
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
     * Send one raw JSON mutation without changing its root wire type.
     *
     * @param  string  $uri
     * @param  string  $json
     * @param  string  $key
     *
     * @return \Illuminate\Testing\TestResponse
     */
    protected function postRawJsonMutation(string $uri, string $json, string $key): TestResponse
    {
        return $this->call('POST', $uri, [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_IDEMPOTENCY_KEY' => $key,
            'HTTP_X_REQUEST_ID' => '123e4567-e89b-42d3-a456-426614174003',
        ], $json);
    }

    /**
     * Send one multipart mutation with uploaded files.
     *
     * @param  string  $uri
     * @param  array<string, mixed>  $payload
     * @param  string  $key
     *
     * @return \Illuminate\Testing\TestResponse
     */
    protected function postUploadMutation(string $uri, array $payload, string $key): TestResponse
    {
        return $this->withHeader('Idempotency-Key', $key)
            ->post($uri, $payload, ['Accept' => 'application/json']);
    }

    /**
     * Create one stable multipart upload fixture.
     *
     * @param  string  $name
     * @param  string  $content
     * @param  string  $mimeType
     *
     * @return \Illuminate\Http\UploadedFile
     */
    protected function uploadedFile(string $name, string $content, string $mimeType): UploadedFile
    {
        $stream = tmpfile();
        if ($stream === false) {
            throw new \RuntimeException('Unable to create an upload fixture');
        }

        fwrite($stream, $content);
        $this->uploadStreams[] = $stream;
        $path = stream_get_meta_data($stream)['uri'];

        return new UploadedFile($path, $name, $mimeType, UPLOAD_ERR_OK, true);
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

class MutableRouteKeyWebsite extends Model
{
    public string $mutableRouteKey = '';

    /**
     * Return the mutable route identity used to prove scope stability.
     *
     * @return string
     */
    public function getRouteKey(): string
    {
        return $this->mutableRouteKey;
    }
}

class TestWebsiteContextMiddleware
{
    public static ?Model $website = null;

    /**
     * Attach the controlled resolved website model to the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): \Symfony\Component\HttpFoundation\Response
    {
        if (! self::$website instanceof Model) {
            throw new \RuntimeException('Test website context is unavailable');
        }

        $request->attributes->set('wncms_api_v2_website', self::$website);

        return $next($request);
    }
}

class TestRetainApiV2ResponseMiddleware
{
    public static ?Response $response = null;

    /**
     * Retain the exact downstream response object for a later test request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        self::$response = $response;

        return $response;
    }
}

class InspectingIdempotencyStore implements IdempotencyStore
{
    public bool $lockAvailable = true;

    public bool $released = false;

    public bool $putThrows = false;

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
        if ($this->putThrows) {
            throw new \RuntimeException('idempotency store write failure');
        }

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
