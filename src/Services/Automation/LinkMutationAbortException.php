<?php

namespace Wncms\Services\Automation;

use RuntimeException;

class LinkMutationAbortException extends RuntimeException
{
    /**
     * Create an exception that aborts a guarded Link mutation transaction.
     *
     * @param  array  $result
     * @return void
     */
    public function __construct(protected array $result)
    {
        parent::__construct((string) ($result['message'] ?? 'Link mutation aborted.'));
    }

    /**
     * Return the result envelope to emit after rollback.
     *
     * @return array
     */
    public function result(): array
    {
        return $this->result;
    }
}
