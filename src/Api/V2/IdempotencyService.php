<?php

namespace Wncms\Api\V2;

use BackedEnum;
use Closure;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use JsonSerializable;
use Stringable;
use Symfony\Component\HttpFoundation\Response;
use UnitEnum;
use Wncms\Api\V2\Contracts\IdempotencyStore;

class IdempotencyService
{
    /**
     * Create the API v2 idempotency service.
     *
     * @param  \Wncms\Api\V2\Contracts\IdempotencyStore  $store
     * @param  \Wncms\Api\V2\ApiResponseFactory  $responses
     */
    public function __construct(
        protected IdempotencyStore $store,
        protected ApiResponseFactory $responses
    ) {
    }

    /**
     * Enforce replay-safe execution for one authenticated API mutation.
     *
     * The completed response is cached only after the downstream JSON handler returns successfully.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->idempotencyKey($request);
        if ($key === '') {
            return $this->responses->failure(
                'idempotency.key_missing',
                'Missing idempotency key',
                Response::HTTP_BAD_REQUEST
            );
        }

        if (strlen($key) < 8 || strlen($key) > 255) {
            return $this->responses->failure(
                'idempotency.key_invalid',
                'Idempotency key must contain between 8 and 255 bytes',
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
            $key
        );
        $fingerprint = $this->fingerprint($request);

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

            $this->store->put($scope, [
                'fingerprint' => $fingerprint,
                'status' => $response->getStatusCode(),
                'body' => $response->getData(true),
                'headers' => ['Content-Type' => 'application/json'],
            ], (int) config('wncms-api-v2.idempotency.ttl_seconds', 86400));

            return $response;
        } finally {
            $lock->release();
        }
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
     * Hash all authorization and operation dimensions into a cache-safe scope.
     *
     * @param  string  $actorId
     * @param  string  $tokenId
     * @param  string  $operationId
     * @param  string  $key
     *
     * @return string
     */
    protected function scope(string $actorId, string $tokenId, string $operationId, string $key): string
    {
        return hash('sha256', json_encode([
            'actor_id' => $actorId,
            'token_id' => $tokenId,
            'operation_id' => $operationId,
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
        $body = $request->isJson() ? $request->json()->all() : $request->request->all();

        $normalized = $this->normalize([
            'method' => strtoupper($request->getMethod()),
            'route' => $this->removeApiToken($routeParameters),
            'query' => $this->removeApiToken($request->query->all()),
            'body' => $this->removeApiToken($body),
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
            return $this->normalize(get_object_vars($value));
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

        return response()
            ->json(
                is_array($record['body'] ?? null) ? $record['body'] : [],
                (int) ($record['status'] ?? Response::HTTP_OK),
                is_array($record['headers'] ?? null) ? $record['headers'] : []
            )
            ->header('Idempotency-Replayed', 'true');
    }
}
