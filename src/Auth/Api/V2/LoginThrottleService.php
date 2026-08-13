<?php

namespace Wncms\Auth\Api\V2;

use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;

final class LoginThrottleService
{
    /**
     * Create the login failure-state service.
     *
     * @param  \Illuminate\Cache\RateLimiter  $limiter
     */
    public function __construct(private RateLimiter $limiter)
    {
    }

    /**
     * Record one account failure and apply its configured progressive delay.
     *
     * @param  string  $identifier
     * @return void
     */
    public function recordFailure(string $identifier): void
    {
        $key = $this->delayKey($identifier);
        $this->limiter->hit($key, $this->windowSeconds());
        $attempts = max(1, $this->limiter->attempts($key));
        $delays = (array) config('wncms.auth_security.login_progressive_delay_seconds', [1, 2, 4, 8, 16, 30]);
        $delay = (int) ($delays[min($attempts - 1, max(0, count($delays) - 1))] ?? 0);

        if ($delay > 0) {
            Sleep::sleep($delay);
        }
    }

    /**
     * Clear only the successful account's failure and account-limit state.
     *
     * Shared IP abuse state deliberately remains intact.
     *
     * @param  string  $identifier
     * @return void
     */
    public function clearAccount(string $identifier): void
    {
        try {
            $this->limiter->clear($this->delayKey($identifier));
            $this->limiter->clear($this->middlewareStorageKey(self::accountKey($identifier)));
        } catch (\Throwable $exception) {
            Log::warning('WNCMS login limiter state could not be cleared after successful authentication.', [
                'account_key' => self::accountKey($identifier),
                'exception' => $exception::class,
            ]);
        }
    }

    /**
     * Return a non-reversible normalized account limiter identity.
     *
     * @param  string  $identifier
     * @return string
     */
    public static function accountKey(string $identifier): string
    {
        return 'account:'.hash('sha256', mb_strtolower(trim($identifier)));
    }

    /**
     * Return a non-reversible IP limiter identity.
     *
     * @param  string  $ip
     * @return string
     */
    public static function ipKey(string $ip): string
    {
        return 'ip:'.hash('sha256', trim($ip));
    }

    /**
     * Return the Laravel named-limiter storage key for one dimension.
     *
     * @param  string  $dimensionKey
     * @return string
     */
    private function middlewareStorageKey(string $dimensionKey): string
    {
        return md5('api-v2-login'.$dimensionKey);
    }

    /**
     * Return the progressive-delay state key.
     *
     * @param  string  $identifier
     * @return string
     */
    private function delayKey(string $identifier): string
    {
        return 'api-v2-login-delay:'.hash('sha256', mb_strtolower(trim($identifier)));
    }

    /**
     * Return the configured failure window in seconds.
     *
     * @return int
     */
    private function windowSeconds(): int
    {
        return max(60, (int) config('wncms.auth_security.login_window_minutes', 15) * 60);
    }
}
