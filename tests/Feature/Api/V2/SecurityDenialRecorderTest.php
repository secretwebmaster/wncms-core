<?php

namespace Wncms\Tests\Feature\Api\V2;

use Carbon\CarbonImmutable;
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
     * Verify all attacker tuples share one bounded row per event time bucket.
     */
    public function test_repeated_same_and_different_denial_tuples_share_one_time_bucket(): void
    {
        CarbonImmutable::setTestNow('2026-08-14 08:05:00 UTC');
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

        $csrfEvent = ApiSecurityEvent::query()->where('event_type', 'security.csrf.denied')->firstOrFail();
        $this->assertSame(1, ApiSecurityEvent::query()->where('event_type', 'security.csrf.denied')->count());
        $this->assertSame(5, $csrfEvent->context['aggregate']['count']);

        CarbonImmutable::setTestNow('2026-08-14 09:00:00 UTC');
        $recorder->record(
            $this->request('192.0.2.99', 'Task7 next bucket attacker'),
            'security.csrf.denied',
            'authentication.csrf_failed',
        );

        $this->assertSame(2, ApiSecurityEvent::query()->where('event_type', 'security.csrf.denied')->count());
        CarbonImmutable::setTestNow();
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
     * Verify a failed cache limiter permits only one emergency fallback per minute.
     */
    public function test_cache_failure_has_a_process_bounded_redacted_emergency_fallback(): void
    {
        CarbonImmutable::setTestNow('2040-01-01 12:34:00 UTC');
        config(['wncms-api-v2.auth_security.security_event_correlation' => []]);
        RateLimiter::shouldReceive('attempt')->times(5)->andThrow(new \RuntimeException('CANARY-CACHE-SECRET'));
        Log::partialMock()->shouldReceive('warning')->once()->withArgs(static function (string $message, array $context): bool {
            return $message === 'WNCMS Cookie security denial event could not be persisted.'
                && ! str_contains(json_encode($context, JSON_THROW_ON_ERROR), 'CANARY-CACHE-SECRET');
        });
        $recorder = app(SecurityDenialRecorder::class);

        foreach (range(1, 5) as $attempt) {
            $recorder->record(
                $this->request("192.0.2.{$attempt}", "CANARY-CACHE-AGENT-{$attempt}"),
                'security.origin.denied',
                'authentication.origin_denied',
            );
        }

        CarbonImmutable::setTestNow();
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
