<?php

namespace Wncms\Services\Automation;

use RuntimeException;

class BulkUpdateAbortException extends RuntimeException
{
    /**
     * Create an exception that aborts an atomic bulk-update transaction.
     *
     * @param  array  $result
     * @return void
     */
    public function __construct(protected array $result)
    {
        parent::__construct((string) ($result['message'] ?? 'Link bulk update aborted.'));
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
