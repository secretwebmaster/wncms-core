<?php

namespace Wncms\Auth\Api\V2;

use Illuminate\Support\Str;
use InvalidArgumentException;

final class TokenHasher
{
    private const SUPPORTED_PREFIXES = [
        'wncms_at',
        'wncms_rt',
        'wncms_st',
    ];

    /**
     * Issue a token with an opaque public identifier and hash-only storage value.
     *
     * @param  string  $prefix
     * @return array{plain_text: string, public_id: string, hash: string}
     *
     * @throws \InvalidArgumentException
     */
    public function issue(string $prefix): array
    {
        if (! in_array($prefix, self::SUPPORTED_PREFIXES, true)) {
            throw new InvalidArgumentException('Unsupported credential prefix.');
        }

        $publicId = (string) Str::ulid();
        $plainText = $prefix.'_'.$publicId.'.'.$this->secret();

        return [
            'plain_text' => $plainText,
            'public_id' => $publicId,
            'hash' => hash('sha256', $plainText),
        ];
    }

    /**
     * Compare credential plaintext with a stored SHA-256 hash in constant time.
     *
     * @param  string  $plainText
     * @param  string  $hash
     * @return bool
     */
    public function matches(string $plainText, string $hash): bool
    {
        return hash_equals($hash, hash('sha256', $plainText));
    }

    /**
     * Create a 32-byte URL-safe Base64 secret without padding.
     *
     * @return string
     */
    private function secret(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}
