<?php

namespace Wncms\Api\V2\Contracts;

use Wncms\Api\V2\Data\AsyncOperation;

interface AtomicOperationRepository extends OperationRepository
{
    /**
     * Replace an operation only when the expected persisted revision is still current.
     *
     * @param  \Wncms\Api\V2\Data\AsyncOperation  $expected
     * @param  \Wncms\Api\V2\Data\AsyncOperation  $replacement
     * @param  int  $ttlSeconds
     *
     * @return bool
     */
    public function compareAndSwap(
        AsyncOperation $expected,
        AsyncOperation $replacement,
        int $ttlSeconds
    ): bool;
}
