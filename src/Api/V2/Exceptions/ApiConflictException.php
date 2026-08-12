<?php

namespace Wncms\Api\V2\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiConflictException extends HttpException
{
    /**
     * Create an API optimistic-concurrency conflict exception.
     *
     * @param  string  $message
     *
     * @return void
     */
    public function __construct(string $message = 'The resource has changed since it was loaded.')
    {
        parent::__construct(409, $message);
    }
}
