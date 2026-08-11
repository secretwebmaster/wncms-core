<?php

namespace Wncms\Api\V2\Contracts;

use Wncms\Api\V2\Data\AsyncOperation;

interface OperationRepository
{
    /**
     * Persist an asynchronous operation for the requested lifetime.
     *
     * @param  \Wncms\Api\V2\Data\AsyncOperation  $operation
     * @param  int  $ttlSeconds
     *
     * @return void
     */
    public function save(AsyncOperation $operation, int $ttlSeconds): void;

    /**
     * Find an unexpired asynchronous operation.
     *
     * @param  string  $id
     *
     * @return \Wncms\Api\V2\Data\AsyncOperation|null
     */
    public function find(string $id): ?AsyncOperation;

    /**
     * Remove an asynchronous operation.
     *
     * @param  string  $id
     *
     * @return void
     */
    public function forget(string $id): void;
}
