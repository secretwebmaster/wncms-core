<?php

namespace Wncms\Auth\Api\V2;

final class StepUpException extends \RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        public readonly int $httpStatus,
    ) {
        parent::__construct($errorCode);
    }
}
