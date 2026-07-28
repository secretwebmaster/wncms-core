<?php

namespace Wncms\Services\Automation;

use RuntimeException;

class BulkSyncTagsAbortException extends RuntimeException
{
    /**
     * Create an exception that aborts an atomic bulk tag synchronization.
     *
     * @param  array  $result
     * @return void
     */
    public function __construct(protected array $result)
    {
        parent::__construct((string) ($result['message'] ?? 'Link bulk tag synchronization aborted.'));
    }

    /**
     * Return the result envelope to emit after the transaction rolls back.
     *
     * @return array
     */
    public function result(): array
    {
        return $this->result;
    }
}
