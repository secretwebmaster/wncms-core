<?php

namespace Wncms\Auth\Api\V2;

use Wncms\Models\ApiSession;

final class CsrfTokenService
{
    /**
     * Issue and hash-bind a fresh double-submit value to one interactive session.
     *
     * @param  \Wncms\Models\ApiSession  $session
     * @return string
     */
    public function issue(ApiSession $session): string
    {
        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $session->forceFill(['csrf_hash' => hash('sha256', $token)])->save();

        return $token;
    }

    /**
     * Assert constant-time double-submit equality and server-side session binding.
     *
     * @param  \Wncms\Models\ApiSession  $session
     * @param  string  $cookie
     * @param  string  $header
     * @return void
     *
     * @throws \RuntimeException
     */
    public function assertValid(ApiSession $session, string $cookie, string $header): void
    {
        $storedHash = (string) ($session->csrf_hash ?? '');
        $submittedHash = hash('sha256', $cookie);
        $valid = $cookie !== ''
            && $header !== ''
            && $storedHash !== ''
            && hash_equals($cookie, $header)
            && hash_equals($storedHash, $submittedHash);

        if (!$valid) {
            throw new \RuntimeException('authentication.csrf_failed');
        }
    }
}
