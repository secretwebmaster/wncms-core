<?php

namespace Wncms\Services\Security;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

final class SecurityDenialRecorder
{
    public const FALLBACK_GLOBAL_KEY = 'wncms:security-denial-fallback:global';

    private const FALLBACK_GLOBAL_MAX = 10;

    private const FALLBACK_DECAY_SECONDS = 60;

    private static ?string $emergencyFallbackMinute = null;

    /**
     * Create the bounded denial-event recorder.
     *
     *
     * @return void
     */
    public function __construct(private SecurityEventService $events) {}

    /**
     * Aggregate a denial by redacted correlation tuple or emit a bounded fallback warning.
     *
     * @param  array<string, mixed>  $context
     */
    public function record(Request $request, string $eventType, string $errorCode, array $context = []): void
    {
        $ip = trim((string) $request->ip()) ?: 'unknown-ip';
        $userAgent = trim((string) $request->userAgent()) ?: 'unknown-user-agent';

        try {
            $this->events->recordAggregate(
                $eventType,
                'warning',
                'denied',
                array_replace($context, [
                    'surface' => 'api_v2',
                    'request_id' => $request->attributes->get('wncms_api_v2_request_id'),
                    'error_code' => $errorCode,
                    'http_status' => 403,
                    'ip' => $ip,
                    'login_identifier' => $eventType,
                    'user_agent' => $userAgent,
                    'context' => ['reason' => str_replace(['security.', '.denied'], '', $eventType).'_denied'],
                ]),
                bucketStartsAt: CarbonImmutable::now('UTC')->startOfHour(),
            );
        } catch (\Throwable $exception) {
            $this->fallback($request, $eventType, $errorCode, $exception);
        }
    }

    /**
     * Emit at most one warning per tuple and ten warnings globally per minute.
     */
    private function fallback(Request $request, string $eventType, string $errorCode, \Throwable $exception): void
    {
        $tupleKey = 'wncms:security-denial-fallback:tuple:'.hash('sha256', implode("\n", [
            $eventType,
            (string) $request->ip(),
            (string) $request->userAgent(),
        ]));

        try {
            RateLimiter::attempt(
                self::FALLBACK_GLOBAL_KEY,
                self::FALLBACK_GLOBAL_MAX,
                fn (): bool => RateLimiter::attempt(
                    $tupleKey,
                    1,
                    fn (): bool => $this->warning($request, $eventType, $errorCode, $exception),
                    self::FALLBACK_DECAY_SECONDS,
                ),
                self::FALLBACK_DECAY_SECONDS,
            );
        } catch (\Throwable $limiterException) {
            $minute = CarbonImmutable::now('UTC')->format('Y-m-d-H-i');
            if (self::$emergencyFallbackMinute !== $minute) {
                self::$emergencyFallbackMinute = $minute;
                $this->warning($request, $eventType, $errorCode, $limiterException);
            }
        }
    }

    /**
     * Emit one redacted best-effort warning and swallow logger failures.
     */
    private function warning(Request $request, string $eventType, string $errorCode, \Throwable $exception): bool
    {
        try {
            Log::warning('WNCMS Cookie security denial event could not be persisted.', [
                'event_type' => $eventType,
                'error_code' => $errorCode,
                'request_id' => $request->attributes->get('wncms_api_v2_request_id'),
                'exception' => $exception::class,
            ]);
        } catch (\Throwable $loggerException) {
            try {
                error_log(json_encode([
                    'message' => 'WNCMS Cookie security denial fallback failed.',
                    'event_type' => $eventType,
                    'error_code' => $errorCode,
                    'exception' => $loggerException::class,
                ], JSON_THROW_ON_ERROR));
            } catch (\Throwable) {
                // A denial response must remain fail-closed when observability is unavailable.
            }
        }

        return true;
    }
}
