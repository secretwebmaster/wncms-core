<?php

namespace Wncms\Auth\Api\V2;

final class RefreshTokenReuseException extends RefreshTokenException
{
    /**
     * Create the stable refresh replay exception.
     */
    public function __construct()
    {
        parent::__construct('authentication.refresh_reuse_detected');
    }
}
