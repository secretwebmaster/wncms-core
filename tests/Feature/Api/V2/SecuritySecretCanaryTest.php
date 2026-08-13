<?php

namespace Wncms\Tests\Feature\Api\V2;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\TestHandler;
use Wncms\Events\ApiSecurityEventRecorded;
use Wncms\Models\ApiSecurityEvent;
use Wncms\Models\MutationAudit;
use Wncms\Services\Automation\MutationAuditService;
use Wncms\Services\Security\SecurityEventService;
use Wncms\Tests\TestCase;

class SecuritySecretCanaryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_approved_canaries_do_not_escape_security_or_mutation_audit_boundaries(): void
    {
        $canaries = [
            'CANARY-PASSWORD',
            'CANARY-TOKEN',
            'CANARY-CONFIRMATION',
            'CANARY-SECRET',
        ];
        $handler = new TestHandler();
        Log::getLogger()->pushHandler($handler);
        Event::fake([ApiSecurityEventRecorded::class]);
        config([
            'wncms.mutation_audit.enabled' => true,
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

        $event = app(SecurityEventService::class)->record('auth.login.failed', 'warning', 'denied', [
            'request_id' => 'canary-request-id',
            'ip' => '203.0.113.10',
            'login_identifier' => 'canary@example.test',
            'user_agent' => 'Canary user agent',
            'password' => $canaries[0],
            'nested' => [
                'token' => $canaries[1],
                'confirmation_token' => $canaries[2],
                'provider_secret' => $canaries[3],
            ],
        ]);
        $audit = app(MutationAuditService::class)->writeFromPlan([
            'operation' => 'create',
            'model_key' => 'link',
            'dry_run' => false,
            'will_write' => true,
            'target' => ['id' => 1],
            'attributes' => [
                'password' => $canaries[0],
                'nested' => ['confirmation_token' => $canaries[2]],
            ],
            'relationships' => ['website_ids' => []],
            'validation' => ['errors' => []],
            'guard' => ['status' => 'pass', 'errors' => []],
            'safety' => ['write_mode' => 'guarded'],
        ], [
            'context' => ['api_key' => $canaries[3]],
        ]);

        Event::assertDispatched(ApiSecurityEventRecorded::class, function (ApiSecurityEventRecorded $recorded) use ($canaries): bool {
            return !$this->containsCanary(json_encode($recorded->event->toArray(), JSON_THROW_ON_ERROR), $canaries);
        });

        $surfaces = [
            ApiSecurityEvent::query()->findOrFail($event->getKey())->toJson(),
            MutationAudit::query()->findOrFail($audit->getKey())->toJson(),
            response()->json(['data' => $event->toArray()])->getContent(),
            json_encode($handler->getRecords(), JSON_THROW_ON_ERROR),
        ];

        foreach ($surfaces as $surface) {
            $this->assertFalse($this->containsCanary($surface, $canaries));
        }
    }

    protected function containsCanary(string $value, array $canaries): bool
    {
        foreach ($canaries as $canary) {
            if (str_contains($value, $canary)) {
                return true;
            }
        }

        return false;
    }
}
