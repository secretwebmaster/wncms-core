<?php

namespace Wncms\Api\V2\Risk;

final class ActionPlanException extends \RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        public readonly int $httpStatus,
    ) {
        parent::__construct($errorCode);
    }
}
