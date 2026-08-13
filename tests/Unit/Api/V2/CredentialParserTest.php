<?php

namespace Wncms\Tests\Unit\Api\V2;

use Wncms\Auth\Api\V2\ApiCredential;
use Wncms\Auth\Api\V2\AuthenticationContext;
use Wncms\Auth\Api\V2\CredentialParser;
use Wncms\Models\User;
use Wncms\Tests\TestCase;

class CredentialParserTest extends TestCase
{
    private CredentialParser $parser;

    /**
     * Create the parser used by credential classification scenarios.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new CredentialParser;
    }

    /**
     * Verify each complete WNCMS credential prefix resolves to its isolated type.
     *
     * @return void
     */
    public function test_it_classifies_complete_wncms_credentials_by_prefix(): void
    {
        foreach ([
            ['wncms_at_public-id.secret', ApiCredential::TYPE_INTERACTIVE_ACCESS],
            ['wncms_rt_public-id.secret', ApiCredential::TYPE_REFRESH],
            ['wncms_st_public-id.secret', ApiCredential::TYPE_SERVICE_TOKEN],
        ] as [$plainText, $type]) {
            $credential = $this->parser->parse($plainText);

            $this->assertSame($type, $credential->type());
            $this->assertSame('public-id', $credential->publicId());
            $this->assertSame($plainText, $credential->plainText());
            $this->assertFalse($credential->isLegacyCandidate());
        }
    }

    /**
     * Verify a failed lookup for every new credential format cannot fall back to legacy authentication.
     *
     * @return void
     */
    public function test_failed_new_prefix_never_falls_back_to_legacy(): void
    {
        foreach ([
            ['wncms_at_public.invalid', ApiCredential::TYPE_INTERACTIVE_ACCESS],
            ['wncms_rt_public.invalid', ApiCredential::TYPE_REFRESH],
            ['wncms_st_public.invalid', ApiCredential::TYPE_SERVICE_TOKEN],
            ['wncms_st_not-a-complete-token', ApiCredential::TYPE_SERVICE_TOKEN],
        ] as [$plainText, $type]) {
            $credential = $this->parser->parse($plainText);

            $this->assertSame($type, $credential->type());
            $this->assertFalse($credential->isLegacyCandidate());
        }
    }

    /**
     * Verify only supported legacy forms are eligible for legacy authentication.
     *
     * @return void
     */
    public function test_it_marks_only_legacy_forms_as_legacy_candidates(): void
    {
        $idAndSecret = $this->parser->parse('42|legacy-secret');
        $bearer = $this->parser->parse('legacy-bearer-token');
        $pipeBearer = $this->parser->parse('legacy|bearer-token');
        $reservedPrefix = $this->parser->parse('wncms_future_credential');

        $this->assertSame(ApiCredential::TYPE_LEGACY_PERSONAL_ACCESS_TOKEN, $idAndSecret->type());
        $this->assertSame('42', $idAndSecret->publicId());
        $this->assertTrue($idAndSecret->isLegacyCandidate());
        $this->assertSame(ApiCredential::TYPE_LEGACY_PERSONAL_ACCESS_TOKEN, $bearer->type());
        $this->assertNull($bearer->publicId());
        $this->assertTrue($bearer->isLegacyCandidate());
        $this->assertNull($pipeBearer->publicId());
        $this->assertTrue($pipeBearer->isLegacyCandidate());
        $this->assertFalse($reservedPrefix->isLegacyCandidate());
    }

    /**
     * Verify serialized credential representations never expose the plaintext credential.
     *
     * @return void
     */
    public function test_credential_serialization_never_exposes_plaintext(): void
    {
        $plainText = 'wncms_st_public-id.secret-value';
        $credential = $this->parser->parse($plainText);

        $this->assertStringNotContainsString($plainText, json_encode($credential, JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString($plainText, json_encode($credential->toArray(), JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString($plainText, (string) $credential);
    }

    /**
     * Verify an authentication context retains immutable actor, capability, and scope information.
     *
     * @return void
     */
    public function test_authentication_context_exposes_immutable_request_authentication_state(): void
    {
        $actor = new User;
        $actor->setRawAttributes(['id' => 42], true);
        $abilities = ['links.read'];
        $websiteIds = [7];
        $context = new AuthenticationContext(
            $actor,
            ApiCredential::TYPE_INTERACTIVE_ACCESS,
            'credential-public-id',
            'session-public-id',
            $abilities,
            $websiteIds,
        );
        $abilities[] = 'links.write';
        $websiteIds[] = 8;

        $this->assertSame($actor, $context->actor());
        $this->assertSame(42, $context->actorId());
        $this->assertSame(ApiCredential::TYPE_INTERACTIVE_ACCESS, $context->credentialType());
        $this->assertSame('credential-public-id', $context->credentialPublicId());
        $this->assertSame('session-public-id', $context->sessionPublicId());
        $this->assertSame(['links.read'], $context->abilities());
        $this->assertSame([7], $context->websiteIds());
        $this->assertTrue($context->hasAbility('links.read'));
        $this->assertFalse($context->hasAbility('links.write'));
        $this->assertTrue($context->hasWebsite(7));
        $this->assertFalse($context->hasWebsite(8));
    }
}
