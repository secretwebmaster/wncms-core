<?php

namespace Wncms\Auth\Api\V2;

final class CredentialParser
{
    private const PREFIX_TYPES = [
        'wncms_at' => ApiCredential::TYPE_INTERACTIVE_ACCESS,
        'wncms_rt' => ApiCredential::TYPE_REFRESH,
        'wncms_st' => ApiCredential::TYPE_SERVICE_TOKEN,
    ];

    /**
     * Parse a bearer credential into an isolated credential type.
     *
     * A recognized WNCMS prefix always retains its resolved type, even when its
     * public identifier or secret cannot later be found or verified.
     *
     * @param  string  $token
     * @return \Wncms\Auth\Api\V2\ApiCredential
     */
    public function parse(string $token): ApiCredential
    {
        foreach (self::PREFIX_TYPES as $prefix => $type) {
            if ($token === $prefix || str_starts_with($token, $prefix.'_')) {
                return new ApiCredential($type, $this->publicIdFor($token, $prefix), $token);
            }
        }

        if (str_starts_with($token, 'wncms_')) {
            return new ApiCredential(ApiCredential::TYPE_LEGACY_PERSONAL_ACCESS_TOKEN, null, $token);
        }

        if (preg_match('/^([0-9]+)\\|([^|\\s]+)$/D', $token, $matches) === 1) {
            return new ApiCredential(ApiCredential::TYPE_LEGACY_PERSONAL_ACCESS_TOKEN, $matches[1], $token, true);
        }

        return new ApiCredential(
            ApiCredential::TYPE_LEGACY_PERSONAL_ACCESS_TOKEN,
            null,
            $token,
            $token !== '' && ! preg_match('/\\s/', $token),
        );
    }

    /**
     * Extract the opaque public identifier when a new-format credential is structurally complete.
     *
     * @param  string  $token
     * @param  string  $prefix
     * @return string|null
     */
    private function publicIdFor(string $token, string $prefix): ?string
    {
        $pattern = '/^'.preg_quote($prefix, '/').'\\_([A-Za-z0-9_-]+)\\.[A-Za-z0-9_-]+$/D';

        return preg_match($pattern, $token, $matches) === 1 ? $matches[1] : null;
    }
}
