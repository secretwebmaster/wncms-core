<?php

namespace Wncms\Api\V2;

use BackedEnum;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route;
use JsonSerializable;
use Stringable;
use Symfony\Component\HttpFoundation\Response;
use UnitEnum;
use Wncms\Api\V2\Contracts\IdempotencyStore;

class IdempotencyService
{
    public const WEBSITE_CONTEXT_ATTRIBUTE = 'wncms_api_v2_website';

    /**
     * Create the API v2 idempotency service.
     *
     * @param  \Wncms\Api\V2\Contracts\IdempotencyStore  $store
     * @param  \Wncms\Api\V2\ApiResponseFactory  $responses
     * @param  \Wncms\Api\V2\ApiV2ResponseFinalizer  $finalizer
     * @param  \Wncms\Api\V2\ReplayResponseTrust  $replayTrust
     */
    public function __construct(
        protected IdempotencyStore $store,
        protected ApiResponseFactory $responses,
        protected ApiV2ResponseFinalizer $finalizer,
        private ReplayResponseTrust $replayTrust
    ) {
    }

    /**
     * Enforce replay-safe execution for one authenticated API mutation.
     *
     * The completed response is cached only after the downstream JSON handler returns successfully.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $requestId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $requestId): Response
    {
        $key = $this->idempotencyKey($request);
        if ($key === '') {
            return $this->responses->failure(
                'idempotency.key_missing',
                'Missing idempotency key',
                Response::HTTP_BAD_REQUEST
            );
        }

        if (preg_match('//u', $key) !== 1 || strlen($key) < 8 || strlen($key) > 255) {
            return $this->responses->failure(
                'idempotency.key_invalid',
                'Idempotency key must be valid UTF-8 and contain between 8 and 255 bytes',
                Response::HTTP_BAD_REQUEST
            );
        }

        $actorId = $request->user()?->getAuthIdentifier();
        if ($actorId === null || $actorId === '') {
            return $this->responses->failure(
                'authentication.missing_token',
                'Authenticated actor is required',
                Response::HTTP_UNAUTHORIZED
            );
        }

        $operationId = $this->operationId($request);
        if ($operationId === '') {
            return $this->responses->failure(
                'idempotency.operation_missing',
                'API operation identity is required',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $scope = $this->scope(
            (string) $actorId,
            $this->tokenIdentity($request),
            $operationId,
            $this->websiteIdentity($request),
            $key
        );
        try {
            $fingerprint = $this->fingerprint($request);
        } catch (\JsonException|\UnexpectedValueException) {
            return $this->responses->failure(
                'idempotency.payload_invalid',
                'Request payload cannot be fingerprinted',
                Response::HTTP_BAD_REQUEST
            );
        }

        $existing = $this->store->get($scope);
        if ($existing !== null) {
            return $this->resolveExisting($existing, $fingerprint);
        }

        $lock = $this->store->lock(
            $scope,
            (int) config('wncms-api-v2.idempotency.lock_seconds', 15)
        );

        if (! $lock->get()) {
            return $this->responses->failure(
                'idempotency.in_progress',
                'An identical mutation is already in progress',
                Response::HTTP_CONFLICT
            );
        }

        try {
            $existing = $this->store->get($scope);
            if ($existing !== null) {
                return $this->resolveExisting($existing, $fingerprint);
            }

            $response = $next($request);
            if (! $response instanceof JsonResponse) {
                throw new \UnexpectedValueException('Idempotent API operations must return JSON responses');
            }

            if ($response->exception instanceof \Throwable) {
                return $response;
            }

            if ($response->getStatusCode() >= Response::HTTP_INTERNAL_SERVER_ERROR) {
                return $response;
            }

            $response = $this->finalizer->finalize($response, $requestId);
            $body = $this->validatedResponseBody($response);

            $this->store->put($scope, [
                'fingerprint' => $fingerprint,
                'status' => $response->getStatusCode(),
                'body' => $body,
                'headers' => [
                    'Content-Type' => (string) $response->headers->get('Content-Type', 'application/json'),
                    'X-Request-ID' => (string) $response->headers->get('X-Request-ID', ''),
                ],
            ], (int) config('wncms-api-v2.idempotency.ttl_seconds', 86400));

            return $response;
        } finally {
            $lock->release();
        }
    }

    /**
     * Validate and return the final raw JSON response body for replay storage.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     *
     * @return string
     *
     * @throws \JsonException
     * @throws \UnexpectedValueException
     */
    protected function validatedResponseBody(Response $response): string
    {
        $body = $response->getContent();
        if (! is_string($body)) {
            throw new \UnexpectedValueException('Idempotent API response body is invalid');
        }

        json_decode($body, false, 512, JSON_THROW_ON_ERROR);

        return $body;
    }

    /**
     * Resolve the configured idempotency header value.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return string
     */
    protected function idempotencyKey(Request $request): string
    {
        $header = (string) config('wncms-api-v2.idempotency.header', 'Idempotency-Key');

        return trim((string) $request->headers->get($header, ''));
    }

    /**
     * Resolve the formal operation identity from the route defaults.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return string
     */
    protected function operationId(Request $request): string
    {
        $route = $request->route();
        if (! $route instanceof Route) {
            return '';
        }

        return trim((string) ($route->defaults['api_operation_id'] ?? ''));
    }

    /**
     * Resolve the access-token identity or the isolated session sentinel.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return string
     */
    protected function tokenIdentity(Request $request): string
    {
        $tokenId = $request->attributes->get('api_v2_token_id');

        return $tokenId === null || $tokenId === '' ? 'session' : (string) $tokenId;
    }

    /**
     * Resolve the trusted website primary key or an explicit no-context sentinel.
     *
     * Website middleware stores the resolved model on the request, so this value never derives from Host.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return string
     */
    protected function websiteIdentity(Request $request): string
    {
        $website = $request->attributes->get(self::WEBSITE_CONTEXT_ATTRIBUTE);
        if ($website === null) {
            return 'website:none';
        }

        if (! $website instanceof Model) {
            throw new \UnexpectedValueException('Resolved website context is not a model');
        }

        $primaryKey = $website->getKey();
        if ($primaryKey === null || $primaryKey === '') {
            throw new \UnexpectedValueException('Resolved website has no primary key');
        }

        return 'website:'.(string) $primaryKey;
    }

    /**
     * Hash all authorization and operation dimensions into a cache-safe scope.
     *
     * @param  string  $actorId
     * @param  string  $tokenId
     * @param  string  $operationId
     * @param  string  $websiteId
     * @param  string  $key
     *
     * @return string
     */
    protected function scope(
        string $actorId,
        string $tokenId,
        string $operationId,
        string $websiteId,
        string $key
    ): string {
        return hash('sha256', json_encode([
            'actor_id' => $actorId,
            'token_id' => $tokenId,
            'operation_id' => $operationId,
            'website_id' => $websiteId,
            'key' => $key,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    /**
     * Hash the normalized request method, route, query, and body input.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return string
     */
    protected function fingerprint(Request $request): string
    {
        $route = $request->route();
        $routeParameters = $route instanceof Route ? $route->parameters() : [];
        $body = $request->isJson()
            ? json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR)
            : $request->request->all();

        $normalized = $this->normalize([
            'method' => strtoupper($request->getMethod()),
            'route' => $this->removeApiToken($routeParameters),
            'query' => $this->removeApiToken($request->query->all()),
            'body' => $this->removeApiToken($body),
            'files' => $this->fingerprintFiles($request->allFiles()),
        ]);

        return hash('sha256', json_encode(
            $normalized,
            JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE
                | JSON_PRESERVE_ZERO_FRACTION
                | JSON_THROW_ON_ERROR
        ));
    }

    /**
     * Remove API tokens recursively before hashing request input.
     *
     * @param  mixed  $value
     *
     * @return mixed
     */
    protected function removeApiToken(mixed $value): mixed
    {
        if ($value instanceof \stdClass) {
            $sanitized = new \stdClass();
            foreach (get_object_vars($value) as $key => $item) {
                if ($key === 'api_token') {
                    continue;
                }

                $sanitized->{$key} = $this->removeApiToken($item);
            }

            return $sanitized;
        }

        if (! is_array($value)) {
            return $value;
        }

        $sanitized = [];
        foreach ($value as $key => $item) {
            if ((string) $key === 'api_token') {
                continue;
            }

            $sanitized[$key] = $this->removeApiToken($item);
        }

        return $sanitized;
    }

    /**
     * Build deterministic descriptors for all multipart uploads.
     *
     * Only metadata and content hashes enter the request fingerprint; upload bytes are never stored.
     *
     * @param  array  $files
     * @param  array<int, string>  $path
     *
     * @return array<int, array<string, mixed>>
     */
    protected function fingerprintFiles(array $files, array $path = []): array
    {
        $fingerprints = [];

        foreach ($files as $key => $file) {
            $filePath = [...$path, (string) $key];

            if (is_array($file)) {
                $fingerprints = array_merge(
                    $fingerprints,
                    $this->fingerprintFiles($file, $filePath)
                );

                continue;
            }

            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                throw new \UnexpectedValueException('Request contains an unreadable upload');
            }

            $contentHash = hash_file('sha256', $file->getPathname());
            if ($contentHash === false) {
                throw new \UnexpectedValueException('Request upload cannot be hashed');
            }

            $fingerprints[] = [
                'path' => $filePath,
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getClientMimeType(),
                'content_sha256' => $contentHash,
            ];
        }

        usort($fingerprints, static function (array $left, array $right): int {
            return strcmp(
                json_encode($left['path'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                json_encode($right['path'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
            );
        });

        return $fingerprints;
    }

    /**
     * Recursively normalize values while preserving list order.
     *
     * @param  mixed  $value
     *
     * @return mixed
     */
    protected function normalize(mixed $value): mixed
    {
        if ($value instanceof UrlRoutable) {
            return $this->normalize($value->getRouteKey());
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        if ($value instanceof JsonSerializable) {
            return $this->normalize($value->jsonSerialize());
        }

        if ($value instanceof Stringable) {
            return (string) $value;
        }

        if (is_object($value)) {
            $properties = get_object_vars($value);
            ksort($properties, SORT_STRING);

            $normalized = new \stdClass();
            foreach ($properties as $key => $item) {
                $normalized->{$key} = $this->normalize($item);
            }

            return $normalized;
        }

        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->normalize($item), $value);
        }

        $normalized = [];
        foreach ($value as $key => $item) {
            $normalized[$key] = $this->normalize($item);
        }
        ksort($normalized, SORT_STRING);

        return $normalized;
    }

    /**
     * Replay a completed response or reject a mismatched request fingerprint.
     *
     * @param  array  $record
     * @param  string  $fingerprint
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function resolveExisting(array $record, string $fingerprint): JsonResponse
    {
        $storedFingerprint = (string) ($record['fingerprint'] ?? '');
        if ($storedFingerprint === '' || ! hash_equals($storedFingerprint, $fingerprint)) {
            return $this->responses->failure(
                'idempotency.key_conflict',
                'Idempotency key has already been used with different input',
                Response::HTTP_CONFLICT
            );
        }

        $body = $record['body'] ?? null;
        if (! is_string($body)) {
            throw new \UnexpectedValueException('Stored idempotency response body is invalid');
        }

        json_decode($body, false, 512, JSON_THROW_ON_ERROR);

        $headers = is_array($record['headers'] ?? null) ? $record['headers'] : [];
        $response = JsonResponse::fromJsonString(
            $body,
            (int) ($record['status'] ?? Response::HTTP_OK),
            $headers
        )->header('Idempotency-Replayed', 'true');

        $this->replayTrust->trust(
            $response,
            (string) ($headers['X-Request-ID'] ?? '')
        );

        return $response;
    }
}
