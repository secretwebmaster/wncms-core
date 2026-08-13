<?php

namespace Wncms\Tests\Feature\Api\V2;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Wncms\Models\ApiSecurityEvent;
use Wncms\Services\Security\SecurityEventService;
use Wncms\Tests\TestCase;

class SecurityEventServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected SecurityEventService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'wncms-api-v2.auth_security.security_event_correlation' => [
                'active_key_version' => 'v1',
                'keys' => [
                    'v1' => [
                        'ip' => 'test-security-correlation-ip-key-123456',
                        'login_identifier' => 'test-security-correlation-login-key-123456',
                        'user_agent' => 'test-security-correlation-agent-key-123456',
                    ],
                ],
            ],
        ]);

        $this->service = app(SecurityEventService::class);
    }

    public function test_event_builder_discards_unknown_and_redacts_sensitive_fields(): void
    {
        $event = $this->service->record('auth.login.failed', 'warning', 'denied', [
            'request_id' => (string) Str::uuid(),
            'password' => 'CANARY-PASSWORD',
            'nested' => ['confirmation_token' => 'CANARY-CONFIRMATION'],
            'unexpected' => 'not-allowlisted',
        ]);

        $serialized = json_encode($event->toArray(), JSON_THROW_ON_ERROR);

        $this->assertStringNotContainsString('CANARY-', $serialized);
        $this->assertArrayNotHasKey('unexpected', $event->context ?? []);
        $this->assertSame('auth.login.failed', $event->event_type);
        $this->assertSame('warning', $event->severity);
        $this->assertSame('denied', $event->outcome);
    }

    public function test_credential_mutation_and_mandatory_event_commit_or_roll_back_together(): void
    {
        $beforeCount = ApiSecurityEvent::count();

        try {
            $this->service->withinTransaction(function (): void {
                DB::table('api_service_tokens')->insert([
                    'token_id' => 'transactional-service-token',
                    'token_hash' => hash('sha256', 'transactional-secret'),
                    'user_id' => 1,
                    'name' => 'Transaction test',
                    'ability_template' => 'read_only',
                    'abilities' => json_encode(['read']),
                    'website_ids' => json_encode([1]),
                    'expires_at' => now()->addDay(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }, [
                'type' => 'invalid.event.type',
                'severity' => 'warning',
                'outcome' => 'denied',
            ]);

            $this->fail('An invalid mandatory security event must reject the mutation.');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame('Unsupported security event type.', $e->getMessage());
        }

        $this->assertSame($beforeCount, ApiSecurityEvent::count());
        $this->assertDatabaseMissing('api_service_tokens', ['token_id' => 'transactional-service-token']);
    }
}
