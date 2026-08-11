<?php

namespace Wncms\Api\V2;

use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Carry private one-shot trust for reconstructed idempotency replay responses.
 *
 * @internal
 */
final class ReplayResponseTrust
{
    /**
     * @var \WeakMap<\Symfony\Component\HttpFoundation\Response, string>
     */
    private \WeakMap $requestIds;

    /**
     * Create one isolated replay trust capability.
     */
    private function __construct()
    {
        $this->requestIds = new \WeakMap();
    }

    /**
     * Create an isolated capability instance.
     *
     * @return self
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Trust one reconstructed replay response with its stored request ID.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  string  $requestId
     *
     * @return void
     */
    public function trust(Response $response, string $requestId): void
    {
        if (! Str::isUuid($requestId)) {
            throw new \UnexpectedValueException('Stored idempotency request ID is invalid');
        }

        $this->requestIds[$response] = $requestId;
    }

    /**
     * Consume and remove trusted replay identity for one response.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     *
     * @return string|null
     */
    public function consume(Response $response): ?string
    {
        if (! isset($this->requestIds[$response])) {
            return null;
        }

        $requestId = $this->requestIds[$response];
        unset($this->requestIds[$response]);

        return $requestId;
    }
}
