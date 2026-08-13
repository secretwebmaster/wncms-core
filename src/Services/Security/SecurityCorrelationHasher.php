<?php

namespace Wncms\Services\Security;

final class SecurityCorrelationHasher
{
    /**
     * Determine whether all active correlation keys are configured.
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        $version = $this->keyVersion();

        if ($version === null) {
            return false;
        }

        foreach (['ip', 'login_identifier', 'user_agent'] as $purpose) {
            if ($this->key($version, $purpose) === null) {
                return false;
            }
        }

        return true;
    }

    /**
     * Hash an IP address with the active versioned key.
     *
     * @param  string  $ip
     *
     * @return string
     */
    public function hashIp(string $ip): string
    {
        return $this->hash('ip', trim($ip));
    }

    /**
     * Hash a normalized login identifier with the active versioned key.
     *
     * @param  string  $identifier
     *
     * @return string
     */
    public function hashLoginIdentifier(string $identifier): string
    {
        return $this->hash('login_identifier', mb_strtolower(trim($identifier)));
    }

    /**
     * Hash a User-Agent with the active versioned key.
     *
     * @param  string  $userAgent
     *
     * @return string
     */
    public function hashUserAgent(string $userAgent): string
    {
        return $this->hash('user_agent', trim($userAgent));
    }

    /**
     * Return the active correlation key version.
     *
     * @return string|null
     */
    public function keyVersion(): ?string
    {
        $version = config('wncms-api-v2.auth_security.security_event_correlation.active_key_version');

        return is_string($version) && preg_match('/^[A-Za-z0-9_-]{1,32}$/', $version) === 1
            ? $version
            : null;
    }

    /**
     * Hash a normalized value for one correlation purpose.
     *
     * @param  string  $purpose
     * @param  string  $value
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    protected function hash(string $purpose, string $value): string
    {
        $version = $this->keyVersion();
        $key = $version === null ? null : $this->key($version, $purpose);

        if ($key === null) {
            throw new \RuntimeException('Security event correlation keys are unavailable.');
        }

        return hash_hmac('sha256', $value, $key);
    }

    /**
     * Return one configured key when it is a valid deployment secret.
     *
     * @param  string  $version
     * @param  string  $purpose
     *
     * @return string|null
     */
    protected function key(string $version, string $purpose): ?string
    {
        $key = config("wncms-api-v2.auth_security.security_event_correlation.keys.{$version}.{$purpose}");

        return is_string($key) && strlen($key) >= 32 ? $key : null;
    }
}
