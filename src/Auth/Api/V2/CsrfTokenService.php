<?php

namespace Wncms\Auth\Api\V2;

use Wncms\Models\ApiRefreshToken;
use Wncms\Models\ApiSession;

final class CsrfTokenService
{
    /**
     * Issue and hash-bind a fresh double-submit value to one refresh credential.
     *
     * @param  \Wncms\Models\ApiSession  $session
     * @param  \Wncms\Models\ApiRefreshToken  $refresh
     *
     * @return string
     */
    public function issue(ApiSession $session, ApiRefreshToken $refresh): string
    {
        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $hash = hash('sha256', $token);
        $refresh->forceFill(['csrf_hash' => $hash])->save();
        $session->forceFill(['csrf_hash' => $hash])->save();

        return $token;
    }

    /**
     * Assert constant-time double-submit equality and refresh-token binding.
     *
     * @param  \Wncms\Models\ApiRefreshToken  $refresh
     * @param  string  $cookie
     * @param  string  $header
     *
     * @return void
     *
     * @throws \RuntimeException
     */
    public function assertValid(ApiRefreshToken $refresh, string $cookie, string $header): void
    {
        $storedHash = (string) ($refresh->csrf_hash ?? '');
        $submittedHash = hash('sha256', $cookie);
        $valid = $cookie !== ''
            && $header !== ''
            && $storedHash !== ''
            && hash_equals($cookie, $header)
            && hash_equals($storedHash, $submittedHash);

        if (! $valid) {
            throw new \RuntimeException('authentication.csrf_failed');
        }
    }
}
