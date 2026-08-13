<?php

namespace Wncms\Auth\Api\V2;

use Illuminate\Hashing\HashManager;

final class DummyPasswordHasher
{
    /**
     * Cached dummy material keyed by the complete active hashing configuration.
     *
     * @var array<string, string>
     */
    private array $material = [];

    /**
     * Create the configured-driver dummy password service.
     *
     * @param  \Illuminate\Hashing\HashManager  $hashes
     */
    public function __construct(private HashManager $hashes)
    {
    }

    /**
     * Return one process-cached hash made by the currently configured password driver.
     *
     * The configuration signature keeps long-lived workers and tests safe when the driver or
     * work factors change. No `needsRehash` branch is used on attacker-selected identifiers.
     *
     * @return string
     */
    public function material(): string
    {
        $driver = $this->hashes->getDefaultDriver();
        $signature = hash('sha256', json_encode([
            'driver' => $driver,
            'configuration' => config("hashing.{$driver}", $driver === 'argon2id' ? config('hashing.argon', []) : []),
        ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        return $this->material[$signature] ??= $this->hashes
            ->driver($driver)
            ->make(bin2hex(random_bytes(32)));
    }
}
