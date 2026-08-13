<?php

namespace Wncms\Tests\Unit\Api\V2;

use InvalidArgumentException;
use Wncms\Auth\Api\V2\TokenHasher;
use Wncms\Tests\TestCase;

class TokenHasherTest extends TestCase
{
    private TokenHasher $hasher;

    /**
     * Create the hasher used by credential issuance scenarios.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->hasher = new TokenHasher;
    }

    /**
     * Verify issued secrets are URL-safe hash-only storage material.
     *
     * @return void
     */
    public function test_issued_secret_is_hash_only_storage_material(): void
    {
        $issued = $this->hasher->issue('wncms_at');

        $this->assertSame(['plain_text', 'public_id', 'hash'], array_keys($issued));
        $this->assertMatchesRegularExpression('/^wncms_at_[0-9A-HJKMNP-TV-Z]{26}\.[A-Za-z0-9_-]{43}$/', $issued['plain_text']);
        $this->assertMatchesRegularExpression('/^[0-9A-HJKMNP-TV-Z]{26}$/', $issued['public_id']);
        $this->assertSame(hash('sha256', $issued['plain_text']), $issued['hash']);
        $this->assertNotSame($issued['plain_text'], $issued['hash']);
        $this->assertTrue($this->hasher->matches($issued['plain_text'], $issued['hash']));
        $this->assertFalse($this->hasher->matches('wncms_at_wrong.secret', $issued['hash']));
    }

    /**
     * Verify each issuance uses a distinct opaque identifier and 32-byte secret.
     *
     * @return void
     */
    public function test_issued_credentials_are_unique_and_use_unpadded_url_safe_secrets(): void
    {
        $first = $this->hasher->issue('wncms_rt');
        $second = $this->hasher->issue('wncms_rt');

        $this->assertNotSame($first['public_id'], $second['public_id']);
        $this->assertNotSame($first['plain_text'], $second['plain_text']);
        $this->assertDoesNotMatchRegularExpression('/[+=\/]/', explode('.', $first['plain_text'], 2)[1]);
        $this->assertSame(43, strlen(explode('.', $first['plain_text'], 2)[1]));
    }

    /**
     * Verify unsupported issuance prefixes fail without disclosing caller-provided material.
     *
     * @return void
     */
    public function test_unsupported_prefix_errors_do_not_disclose_input(): void
    {
        $prefix = 'invalid-prefix-with-secret-material';

        try {
            $this->hasher->issue($prefix);
            $this->fail('Expected an unsupported prefix exception.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringNotContainsString($prefix, $exception->getMessage());
        }
    }
}
