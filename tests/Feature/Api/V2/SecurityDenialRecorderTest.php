<?php

namespace Wncms\Tests\Feature\Api\V2;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Wncms\Models\ApiSecurityEvent;
use Wncms\Services\Security\SecurityDenialRecorder;
use Wncms\Tests\TestCase;

class SecurityDenialRecorderTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Configure deterministic correlation keys for denial aggregation.
     */
    protected function setUp(): void
    {
        parent::setUp();

        config(['wncms-api-v2.auth_security.security_event_correlation' => [
            'active_key_version' => 'v1',
            'keys' => ['v1' => [
                'ip' => 'task7-denial-ip-correlation-key-1234567890',
                'login_identifier' => 'task7-denial-login-correlation-key-1234567890',
                'user_agent' => 'task7-denial-agent-correlation-key-1234567890',
            ]],
        ]]);
        RateLimiter::clear(SecurityDenialRecorder::FALLBACK_GLOBAL_KEY);
    }

    /**
     * Verify repeated denials use one row per redacted attacker tuple.
     */
    public function test_repeated_same_and_different_denial_tuples_remain_aggregated(): void
    {
        $recorder = app(SecurityDenialRecorder::class);
        $same = $this->request('203.0.113.10', 'Task7 same attacker');
        foreach (range(1, 20) as $attempt) {
            $same->attributes->set('wncms_api_v2_request_id', "same-{$attempt}");
            $recorder->record($same, 'security.origin.denied', 'authentication.origin_denied');
        }

        $sameEvent = ApiSecurityEvent::query()->where('event_type', 'security.origin.denied')->firstOrFail();
        $this->assertSame(1, ApiSecurityEvent::query()->where('event_type', 'security.origin.denied')->count());
        $this->assertSame(20, $sameEvent->context['aggregate']['count']);

        foreach (range(1, 5) as $attacker) {
            $recorder->record(
                $this->request("203.0.113.{$attacker}", "Task7 attacker {$attacker}"),
                'security.csrf.denied',
                'authentication.csrf_failed',
            );
        }

        $this->assertSame(5, ApiSecurityEvent::query()->where('event_type', 'security.csrf.denied')->count());
    }

    /**
     * Verify persistence failures produce bounded redacted fallback warnings.
     */
    public function test_denial_fallback_logging_is_globally_bounded_and_redacted(): void
    {
        config(['wncms-api-v2.auth_security.security_event_correlation' => []]);
        Log::spy();
        $recorder = app(SecurityDenialRecorder::class);

        foreach (range(1, 30) as $attacker) {
            $request = $this->request("198.51.100.{$attacker}", "CANARY-ATTACKER-{$attacker}");
            $request->attributes->set('wncms_api_v2_request_id', "fallback-{$attacker}");
            $recorder->record($request, 'security.origin.denied', 'authentication.origin_denied');
        }

        Log::shouldHaveReceived('warning')->withArgs(static function (string $message, array $context): bool {
            return $message === 'WNCMS Cookie security denial event could not be persisted.'
                && ($context['event_type'] ?? null) === 'security.origin.denied'
                && ($context['error_code'] ?? null) === 'authentication.origin_denied'
                && ! str_contains(json_encode($context, JSON_THROW_ON_ERROR), 'CANARY-ATTACKER');
        })->times(10);
    }

    /**
     * Build one attacker request without embedding raw values in persisted context.
     */
    private function request(string $ip, string $userAgent): Request
    {
        return Request::create(
            'https://api.example.test/api/v2/backend/auth/refresh',
            'POST',
            server: ['REMOTE_ADDR' => $ip, 'HTTP_USER_AGENT' => $userAgent],
        );
    }
}
