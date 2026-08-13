<?php

namespace Wncms\Auth\Api\V2;

use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class RefreshTokenException extends RuntimeException
{
    /**
     * Create a typed refresh exchange failure.
     *
     * @param  string  $errorCode
     * @param  int  $httpStatus
     */
    public function __construct(
        public readonly string $errorCode,
        public readonly int $httpStatus = Response::HTTP_UNAUTHORIZED,
    ) {
        parent::__construct($errorCode);
    }
}
