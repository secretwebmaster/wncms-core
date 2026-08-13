<?php

namespace Wncms\Api\V2\Risk;

final class RiskContextException extends \RuntimeException
{
    /**
     * Create a stable risk-context failure.
     *
     * @param  string  $errorCode
     * @param  int  $httpStatus
     * @return void
     */
    public function __construct(
        public readonly string $errorCode,
        public readonly int $httpStatus,
    ) {
        parent::__construct($errorCode);
    }
}
