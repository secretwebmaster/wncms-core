<?php

namespace Wncms\Services\Security;

final class SecurityEventMutationScope
{
    /**
     * Create a scoped security-event mutation capability.
     *
     * @param  string  $purpose
     *
     * @return void
     */
    private function __construct(protected string $purpose)
    {
    }

    /**
     * Create the capability used for aggregate counter updates.
     *
     * @return self
     */
    public static function aggregate(): self
    {
        return new self('aggregate');
    }

    /**
     * Create the capability used for retention deletion.
     *
     * @return self
     */
    public static function retention(): self
    {
        return new self('retention');
    }

    /**
     * Determine whether this capability permits one named mutation.
     *
     * @param  string  $purpose
     *
     * @return bool
     */
    public function permits(string $purpose): bool
    {
        return hash_equals($this->purpose, $purpose);
    }
}
