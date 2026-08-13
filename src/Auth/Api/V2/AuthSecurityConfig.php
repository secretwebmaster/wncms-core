<?php

namespace Wncms\Auth\Api\V2;

use DateTimeImmutable;

final class AuthSecurityConfig
{
    private const SETTING_KEYS = [
        'api_access_token_lifetime_minutes',
        'api_refresh_token_lifetime_days',
        'api_refresh_transport',
        'api_permanent_remember_enabled',
        'api_refresh_cookie_domain',
        'api_refresh_cookie_same_site',
        'api_refresh_cookie_allowed_origins',
        'api_refresh_cookie_referer_fallback',
        'api_login_account_attempts',
        'api_login_ip_attempts',
        'api_login_window_minutes',
        'api_login_progressive_delay_seconds',
        'api_high_risk_action_mode',
        'api_action_plan_lifetime_seconds',
        'api_step_up_lifetime_seconds',
        'blade_enabled',
        'api_legacy_personal_tokens_enabled',
        'api_legacy_personal_tokens_cutoff_at',
        'api_security_event_retention_days',
    ];

    /**
     * Create a configuration value object from package defaults and system settings.
     *
     * @return self
     */
    public static function fromRuntime(): self
    {
        $defaults = (array) config('wncms-api-v2.auth_security', []);
        $values = [];

        foreach (self::SETTING_KEYS as $settingKey) {
            $values[$settingKey] = gss($settingKey, self::defaultFor($defaults, $settingKey));
        }

        return new self($values, $defaults);
    }

    /**
     * Return stable authentication security setting keys.
     *
     * @return array<int, string>
     */
    public static function settingKeys(): array
    {
        return self::SETTING_KEYS;
    }

    /**
     * Create the immutable configuration value object.
     *
     * @param  array<string, mixed>  $values
     * @param  array<string, mixed>  $defaults
     */
    private function __construct(
        private readonly array $values,
        private readonly array $defaults,
    ) {
    }

    /**
     * Validate all configured stable settings.
     *
     * @return array<string, string>
     */
    public function validate(): array
    {
        $errors = [];

        $this->validateInteger($errors, 'api_access_token_lifetime_minutes', 1, 60);
        $this->validateInteger($errors, 'api_refresh_token_lifetime_days', 1, 365);
        $this->validateEnum($errors, 'api_refresh_transport', ['json', 'cookie']);
        $this->validateBoolean($errors, 'api_permanent_remember_enabled');
        $this->validateCookieDomain($errors);
        $this->validateEnum($errors, 'api_refresh_cookie_same_site', ['strict', 'lax', 'none']);
        $this->validateAllowedOrigins($errors);
        $this->validateBoolean($errors, 'api_refresh_cookie_referer_fallback');
        $this->validateInteger($errors, 'api_login_account_attempts', 1, 100);
        $this->validateInteger($errors, 'api_login_ip_attempts', 1, 1000);
        $this->validateInteger($errors, 'api_login_window_minutes', 1, 1440);
        $this->validateProgressiveDelay($errors);
        $this->validateEnum($errors, 'api_high_risk_action_mode', ['direct', 'planned']);
        $this->validateInteger($errors, 'api_action_plan_lifetime_seconds', 60, 900);
        $this->validateInteger($errors, 'api_step_up_lifetime_seconds', 60, 900);
        $this->validateBoolean($errors, 'blade_enabled');
        $this->validateBoolean($errors, 'api_legacy_personal_tokens_enabled');
        $this->validateLegacyCutoff($errors);
        $this->validateInteger($errors, 'api_security_event_retention_days', 30, 365);
        $this->validateCookieCompatibility($errors);

        ksort($errors);

        return $errors;
    }

    /**
     * Return values mapped for runtime configuration consumers.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'access_token_lifetime_minutes' => $this->accessLifetimeMinutes(),
            'refresh_token_lifetime_days' => $this->refreshLifetimeDays(),
            'refresh_transport' => $this->refreshTransport(),
            'permanent_remember_enabled' => $this->permanentRememberEnabled(),
            'refresh_cookie_domain' => $this->refreshCookieDomain(),
            'refresh_cookie_same_site' => $this->refreshCookieSameSite(),
            'refresh_cookie_allowed_origins' => $this->refreshCookieAllowedOrigins(),
            'refresh_cookie_referer_fallback' => $this->refreshCookieRefererFallback(),
            'login_account_attempts' => $this->loginAccountAttempts(),
            'login_ip_attempts' => $this->loginIpAttempts(),
            'login_window_minutes' => $this->loginWindowMinutes(),
            'login_progressive_delay_seconds' => $this->loginProgressiveDelaySeconds(),
            'high_risk_action_mode' => $this->highRiskMode(),
            'action_plan_lifetime_seconds' => $this->actionPlanLifetimeSeconds(),
            'step_up_lifetime_seconds' => $this->stepUpLifetimeSeconds(),
            'blade_enabled' => $this->bladeEnabled(),
            'legacy_personal_tokens_enabled' => $this->legacyPersonalTokensEnabled(),
            'legacy_personal_tokens_cutoff_at' => $this->legacyPersonalTokensCutoffAt(),
            'security_event_retention_days' => $this->securityEventRetentionDays(),
            'valid' => $this->validate() === [],
            'errors' => $this->validate(),
        ];
    }

    /**
     * Return the access token lifetime in minutes.
     *
     * @return int
     */
    public function accessLifetimeMinutes(): int
    {
        return $this->integerValue('api_access_token_lifetime_minutes');
    }

    /**
     * Return the refresh token lifetime in days.
     *
     * @return int
     */
    public function refreshLifetimeDays(): int
    {
        return $this->integerValue('api_refresh_token_lifetime_days');
    }

    /**
     * Return the configured refresh transport.
     *
     * @return string
     */
    public function refreshTransport(): string
    {
        return $this->enumValue('api_refresh_transport', ['json', 'cookie']);
    }

    /**
     * Return whether permanent remember-me sessions are enabled.
     *
     * @return bool
     */
    public function permanentRememberEnabled(): bool
    {
        return $this->booleanValue('api_permanent_remember_enabled');
    }

    /**
     * Return the optional shared refresh cookie domain.
     *
     * @return string|null
     */
    public function refreshCookieDomain(): ?string
    {
        $value = trim((string) $this->values['api_refresh_cookie_domain']);

        return $value === '' ? null : $value;
    }

    /**
     * Return the configured refresh cookie SameSite policy.
     *
     * @return string
     */
    public function refreshCookieSameSite(): string
    {
        return $this->enumValue('api_refresh_cookie_same_site', ['strict', 'lax', 'none']);
    }

    /**
     * Return exact allowed origins for cookie refresh requests.
     *
     * @return array<int, string>
     */
    public function refreshCookieAllowedOrigins(): array
    {
        $origins = preg_split('/\r\n|\r|\n/', (string) $this->values['api_refresh_cookie_allowed_origins']) ?: [];
        $origins = array_map(static fn($origin) => trim((string) $origin), $origins);
        $origins = array_filter($origins, static fn($origin) => $origin !== '');

        return array_values(array_unique($origins));
    }

    /**
     * Return whether Referer fallback is enabled for cookie requests.
     *
     * @return bool
     */
    public function refreshCookieRefererFallback(): bool
    {
        return $this->booleanValue('api_refresh_cookie_referer_fallback');
    }

    /**
     * Return the account login attempt limit.
     *
     * @return int
     */
    public function loginAccountAttempts(): int
    {
        return $this->integerValue('api_login_account_attempts');
    }

    /**
     * Return the IP login attempt limit.
     *
     * @return int
     */
    public function loginIpAttempts(): int
    {
        return $this->integerValue('api_login_ip_attempts');
    }

    /**
     * Return the login throttle window in minutes.
     *
     * @return int
     */
    public function loginWindowMinutes(): int
    {
        return $this->integerValue('api_login_window_minutes');
    }

    /**
     * Return the progressive login failure delays in seconds.
     *
     * @return array<int, int>
     */
    public function loginProgressiveDelaySeconds(): array
    {
        $value = trim((string) $this->values['api_login_progressive_delay_seconds']);
        $parts = $value === '' ? [] : explode(',', $value);
        $delays = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if (filter_var($part, FILTER_VALIDATE_INT) === false) {
                return $this->defaultProgressiveDelays();
            }

            $delays[] = (int) $part;
        }

        return $delays === [] ? $this->defaultProgressiveDelays() : $delays;
    }

    /**
     * Return the high-risk action mode.
     *
     * @return string
     */
    public function highRiskMode(): string
    {
        return $this->enumValue('api_high_risk_action_mode', ['direct', 'planned']);
    }

    /**
     * Return the action-plan lifetime in seconds.
     *
     * @return int
     */
    public function actionPlanLifetimeSeconds(): int
    {
        return $this->integerValue('api_action_plan_lifetime_seconds');
    }

    /**
     * Return the step-up proof lifetime in seconds.
     *
     * @return int
     */
    public function stepUpLifetimeSeconds(): int
    {
        return $this->integerValue('api_step_up_lifetime_seconds');
    }

    /**
     * Return whether WNCMS Blade routes are enabled.
     *
     * @return bool
     */
    public function bladeEnabled(): bool
    {
        return $this->booleanValue('blade_enabled');
    }

    /**
     * Return whether legacy personal tokens are enabled.
     *
     * @return bool
     */
    public function legacyPersonalTokensEnabled(): bool
    {
        return $this->booleanValue('api_legacy_personal_tokens_enabled');
    }

    /**
     * Return the optional legacy personal token cutoff in UTC.
     *
     * @return string|null
     */
    public function legacyPersonalTokensCutoffAt(): ?string
    {
        $value = trim((string) $this->values['api_legacy_personal_tokens_cutoff_at']);

        return $value === '' ? null : $value;
    }

    /**
     * Return the security event retention period in days.
     *
     * @return int
     */
    public function securityEventRetentionDays(): int
    {
        return $this->integerValue('api_security_event_retention_days');
    }

    /**
     * Return one configured default value.
     *
     * @param  array<string, mixed>  $defaults
     * @param  string  $settingKey
     * @return mixed
     */
    private static function defaultFor(array $defaults, string $settingKey): mixed
    {
        $configKey = str_replace('api_', '', $settingKey);
        if ($settingKey === 'blade_enabled') {
            $configKey = 'blade_enabled';
        }

        return $defaults[$configKey] ?? null;
    }

    /**
     * Validate one integer setting range.
     *
     * @param  array<string, string>  $errors
     * @param  string  $settingKey
     * @param  int  $minimum
     * @param  int  $maximum
     * @return void
     */
    private function validateInteger(array &$errors, string $settingKey, int $minimum, int $maximum): void
    {
        $value = filter_var($this->values[$settingKey], FILTER_VALIDATE_INT);
        if ($value === false || $value < $minimum || $value > $maximum) {
            $errors[$settingKey] = "Must be an integer between {$minimum} and {$maximum}.";
        }
    }

    /**
     * Validate one enum setting.
     *
     * @param  array<string, string>  $errors
     * @param  string  $settingKey
     * @param  array<int, string>  $allowedValues
     * @return void
     */
    private function validateEnum(array &$errors, string $settingKey, array $allowedValues): void
    {
        $value = strtolower(trim((string) $this->values[$settingKey]));
        if (!in_array($value, $allowedValues, true)) {
            $errors[$settingKey] = 'Must be one of: ' . implode(', ', $allowedValues) . '.';
        }
    }

    /**
     * Validate one boolean setting.
     *
     * @param  array<string, string>  $errors
     * @param  string  $settingKey
     * @return void
     */
    private function validateBoolean(array &$errors, string $settingKey): void
    {
        if ($this->parseBoolean($this->values[$settingKey]) === null) {
            $errors[$settingKey] = 'Must be a boolean.';
        }
    }

    /**
     * Validate the optional shared refresh cookie domain.
     *
     * @param  array<string, string>  $errors
     * @return void
     */
    private function validateCookieDomain(array &$errors): void
    {
        $domain = $this->refreshCookieDomain();
        if ($domain === null) {
            return;
        }

        $normalizedDomain = ltrim(strtolower($domain), '.');
        $appHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        $isDomain = preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/', $normalizedDomain) === 1;
        $isParent = $appHost !== '' && str_ends_with($appHost, '.' . $normalizedDomain);

        if (!$isDomain || !$isParent) {
            $errors['api_refresh_cookie_domain'] = 'Must be empty or a valid parent domain for the application host.';
        }
    }

    /**
     * Validate exact configured origins.
     *
     * @param  array<string, string>  $errors
     * @return void
     */
    private function validateAllowedOrigins(array &$errors): void
    {
        foreach ($this->refreshCookieAllowedOrigins() as $origin) {
            $parsed = parse_url($origin);
            $isExactOrigin = is_array($parsed)
                && isset($parsed['scheme'], $parsed['host'])
                && in_array(strtolower($parsed['scheme']), ['http', 'https'], true)
                && !isset($parsed['path'], $parsed['query'], $parsed['fragment'], $parsed['user'], $parsed['pass'])
                && !str_contains($parsed['host'], '*')
                && strtolower($origin) !== 'null';

            if (!$isExactOrigin) {
                $errors['api_refresh_cookie_allowed_origins'] = 'Must contain newline-separated exact HTTP or HTTPS origins.';
                return;
            }
        }
    }

    /**
     * Validate progressive login failure delays.
     *
     * @param  array<string, string>  $errors
     * @return void
     */
    private function validateProgressiveDelay(array &$errors): void
    {
        $rawValue = trim((string) $this->values['api_login_progressive_delay_seconds']);
        $parts = $rawValue === '' ? [] : explode(',', $rawValue);
        $previous = -1;

        if ($parts === []) {
            $errors['api_login_progressive_delay_seconds'] = 'Must be ascending comma-separated integers between 0 and 300.';
            return;
        }

        foreach ($parts as $part) {
            $value = filter_var(trim($part), FILTER_VALIDATE_INT);
            if ($value === false || $value < 0 || $value > 300 || $value < $previous) {
                $errors['api_login_progressive_delay_seconds'] = 'Must be ascending comma-separated integers between 0 and 300.';
                return;
            }

            $previous = $value;
        }
    }

    /**
     * Validate the optional UTC cutoff timestamp.
     *
     * @param  array<string, string>  $errors
     * @return void
     */
    private function validateLegacyCutoff(array &$errors): void
    {
        $value = $this->legacyPersonalTokensCutoffAt();
        if ($value === null) {
            return;
        }

        try {
            $date = new DateTimeImmutable($value);
            if ($date->getOffset() !== 0) {
                $errors['api_legacy_personal_tokens_cutoff_at'] = 'Must be a nullable UTC datetime.';
            }
        } catch (\Throwable $e) {
            $errors['api_legacy_personal_tokens_cutoff_at'] = 'Must be a nullable UTC datetime.';
        }
    }

    /**
     * Validate cookie mode boundary requirements.
     *
     * @param  array<string, string>  $errors
     * @return void
     */
    private function validateCookieCompatibility(array &$errors): void
    {
        if ($this->refreshTransport() === 'cookie' && $this->refreshCookieAllowedOrigins() === []) {
            $errors['api_refresh_cookie_allowed_origins'] = 'At least one exact allowed origin is required when Cookie refresh transport is enabled.';
        }

        if ($this->refreshCookieSameSite() !== 'none') {
            return;
        }

        $appScheme = strtolower((string) parse_url((string) config('app.url'), PHP_URL_SCHEME));
        $secureCookies = $this->parseBoolean(config('session.secure')) === true;
        $hasSecureOrigins = $this->refreshCookieAllowedOrigins() !== []
            && count(array_filter($this->refreshCookieAllowedOrigins(), static fn($origin) => str_starts_with(strtolower($origin), 'https://'))) === count($this->refreshCookieAllowedOrigins());

        if ($appScheme !== 'https' || !$secureCookies || !$hasSecureOrigins) {
            $errors['api_refresh_cookie_same_site'] = 'SameSite=None requires an HTTPS application URL and secure refresh cookies.';
        }
    }

    /**
     * Return a validated integer value or its stable default.
     *
     * @param  string  $settingKey
     * @return int
     */
    private function integerValue(string $settingKey): int
    {
        $value = filter_var($this->values[$settingKey], FILTER_VALIDATE_INT);

        $ranges = [
            'api_access_token_lifetime_minutes' => [1, 60],
            'api_refresh_token_lifetime_days' => [1, 365],
            'api_login_account_attempts' => [1, 100],
            'api_login_ip_attempts' => [1, 1000],
            'api_login_window_minutes' => [1, 1440],
            'api_action_plan_lifetime_seconds' => [60, 900],
            'api_step_up_lifetime_seconds' => [60, 900],
            'api_security_event_retention_days' => [30, 365],
        ];

        if ($value === false || !isset($ranges[$settingKey]) || $value < $ranges[$settingKey][0] || $value > $ranges[$settingKey][1]) {
            return (int) self::defaultFor($this->defaults, $settingKey);
        }

        return (int) $value;
    }

    /**
     * Return a validated enum value or its stable default.
     *
     * @param  string  $settingKey
     * @param  array<int, string>  $allowedValues
     * @return string
     */
    private function enumValue(string $settingKey, array $allowedValues): string
    {
        $value = strtolower(trim((string) $this->values[$settingKey]));
        if (in_array($value, $allowedValues, true)) {
            return $value;
        }

        return (string) self::defaultFor($this->defaults, $settingKey);
    }

    /**
     * Return a validated boolean value or its stable default.
     *
     * @param  string  $settingKey
     * @return bool
     */
    private function booleanValue(string $settingKey): bool
    {
        $value = $this->parseBoolean($this->values[$settingKey]);
        if ($value !== null) {
            return $value;
        }

        return self::parseBoolean(self::defaultFor($this->defaults, $settingKey)) ?? false;
    }

    /**
     * Parse a strict persisted boolean value.
     *
     * @param  mixed  $value
     * @return bool|null
     */
    private static function parseBoolean(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) && in_array($value, [0, 1], true)) {
            return $value === 1;
        }

        if (is_string($value)) {
            return match (strtolower(trim($value))) {
                '0', 'false' => false,
                '1', 'true' => true,
                default => null,
            };
        }

        return null;
    }

    /**
     * Return stable default progressive login delays.
     *
     * @return array<int, int>
     */
    private function defaultProgressiveDelays(): array
    {
        $value = (string) self::defaultFor($this->defaults, 'api_login_progressive_delay_seconds');

        return array_map(static fn($delay) => (int) trim($delay), explode(',', $value));
    }
}
