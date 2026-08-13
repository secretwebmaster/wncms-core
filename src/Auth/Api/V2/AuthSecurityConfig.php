<?php

namespace Wncms\Auth\Api\V2;

use DateTimeImmutable;
use Illuminate\Support\Str;

final class AuthSecurityConfig
{
    private ?array $validationErrors = null;

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
     */
    public static function fromRuntime(): self
    {
        $defaults = (array) config('wncms-api-v2.auth_security', []);
        $values = [];

        foreach (self::SETTING_KEYS as $settingKey) {
            $values[$settingKey] = gss($settingKey, self::runtimeDefault($defaults, $settingKey));
        }

        return self::fromValues($values);
    }

    /**
     * Create a configuration value object from candidate system-setting values.
     *
     * @param  array<string, mixed>  $values
     */
    public static function fromValues(array $values): self
    {
        $defaults = (array) config('wncms-api-v2.auth_security', []);

        return new self(array_replace(self::defaultSettings(), array_intersect_key($values, array_flip(self::SETTING_KEYS))), $defaults);
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
     * Return stable default values keyed by persisted system-setting name.
     *
     * @return array<string, mixed>
     */
    public static function defaultSettings(): array
    {
        $defaults = (array) config('wncms-api-v2.auth_security', []);
        $settings = [];

        foreach (self::SETTING_KEYS as $settingKey) {
            $settings[$settingKey] = self::defaultFor($defaults, $settingKey);
        }

        return $settings;
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
    ) {}

    /**
     * Validate all configured stable settings.
     *
     * @return array<string, string>
     */
    public function validate(): array
    {
        if ($this->validationErrors !== null) {
            return $this->validationErrors;
        }

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

        return $this->validationErrors = $errors;
    }

    /**
     * Return values mapped for runtime configuration consumers.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $errors = $this->validate();

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
            'valid' => $errors === [],
            'errors' => $errors,
        ];
    }

    /**
     * Return the access token lifetime in minutes.
     */
    public function accessLifetimeMinutes(): int
    {
        return $this->integerValue('api_access_token_lifetime_minutes');
    }

    /**
     * Return the refresh token lifetime in days.
     */
    public function refreshLifetimeDays(): int
    {
        return $this->integerValue('api_refresh_token_lifetime_days');
    }

    /**
     * Return the configured refresh transport.
     */
    public function refreshTransport(): string
    {
        if ($this->rawEnumValue('api_refresh_transport', ['json', 'cookie']) !== 'cookie') {
            return 'json';
        }

        return $this->hasExactAllowedOrigins()
            && $this->hasCredentialedHostCorsConfiguration()
            && $this->hasSecureSameSiteNoneConfiguration()
            ? 'cookie'
            : 'json';
    }

    /**
     * Return whether the persisted transport setting explicitly requests Cookie mode.
     */
    public function cookieTransportConfigured(): bool
    {
        return $this->rawEnumValue('api_refresh_transport', ['json', 'cookie']) === 'cookie';
    }

    /**
     * Return whether permanent remember-me sessions are enabled.
     */
    public function permanentRememberEnabled(): bool
    {
        return $this->booleanValue('api_permanent_remember_enabled');
    }

    /**
     * Return the optional shared refresh cookie domain.
     */
    public function refreshCookieDomain(): ?string
    {
        $value = trim((string) $this->values['api_refresh_cookie_domain']);

        return $value === '' || ! $this->isValidCookieDomain($value) ? null : $value;
    }

    /**
     * Return the configured refresh cookie SameSite policy.
     */
    public function refreshCookieSameSite(): string
    {
        return $this->rawEnumValue('api_refresh_cookie_same_site', ['strict', 'lax', 'none']) ?? 'strict';
    }

    /**
     * Return exact allowed origins for cookie refresh requests.
     *
     * @return array<int, string>
     */
    public function refreshCookieAllowedOrigins(): array
    {
        return $this->hasExactAllowedOrigins() ? $this->rawAllowedOrigins() : [];
    }

    /**
     * Return whether Referer fallback is enabled for cookie requests.
     */
    public function refreshCookieRefererFallback(): bool
    {
        return $this->booleanValue('api_refresh_cookie_referer_fallback');
    }

    /**
     * Return the account login attempt limit.
     */
    public function loginAccountAttempts(): int
    {
        return $this->integerValue('api_login_account_attempts');
    }

    /**
     * Return the IP login attempt limit.
     */
    public function loginIpAttempts(): int
    {
        return $this->integerValue('api_login_ip_attempts');
    }

    /**
     * Return the login throttle window in minutes.
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
        return $this->isValidProgressiveDelay()
            ? array_map(static fn ($delay) => (int) trim($delay), explode(',', (string) $this->values['api_login_progressive_delay_seconds']))
            : $this->defaultProgressiveDelays();
    }

    /**
     * Return the high-risk action mode.
     */
    public function highRiskMode(): string
    {
        return $this->rawEnumValue('api_high_risk_action_mode', ['direct', 'planned']) ?? 'planned';
    }

    /**
     * Return the action-plan lifetime in seconds.
     */
    public function actionPlanLifetimeSeconds(): int
    {
        return $this->integerValue('api_action_plan_lifetime_seconds');
    }

    /**
     * Return the step-up proof lifetime in seconds.
     */
    public function stepUpLifetimeSeconds(): int
    {
        return $this->integerValue('api_step_up_lifetime_seconds');
    }

    /**
     * Return whether WNCMS Blade routes are enabled.
     */
    public function bladeEnabled(): bool
    {
        return $this->parseBoolean($this->values['blade_enabled']) ?? false;
    }

    /**
     * Return whether legacy personal tokens are enabled.
     */
    public function legacyPersonalTokensEnabled(): bool
    {
        return $this->booleanValue('api_legacy_personal_tokens_enabled');
    }

    /**
     * Return the optional legacy personal token cutoff in UTC.
     */
    public function legacyPersonalTokensCutoffAt(): ?string
    {
        $value = trim((string) $this->values['api_legacy_personal_tokens_cutoff_at']);

        if ($value === '') {
            return null;
        }

        try {
            return (new DateTimeImmutable($value))->getOffset() === 0 ? $value : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Return the security event retention period in days.
     */
    public function securityEventRetentionDays(): int
    {
        return $this->integerValue('api_security_event_retention_days');
    }

    /**
     * Return one configured default value.
     *
     * @param  array<string, mixed>  $defaults
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
     * Return the loaded runtime value before falling back to package defaults.
     *
     * @param  array<string, mixed>  $defaults
     */
    private static function runtimeDefault(array $defaults, string $settingKey): mixed
    {
        $configKey = $settingKey === 'blade_enabled'
            ? 'blade_enabled'
            : str_replace('api_', '', $settingKey);

        return config('wncms.auth_security.'.$configKey, self::defaultFor($defaults, $settingKey));
    }

    /**
     * Return one raw enum value when it is valid.
     *
     * @param  array<int, string>  $allowedValues
     */
    private function rawEnumValue(string $settingKey, array $allowedValues): ?string
    {
        $value = strtolower(trim((string) $this->values[$settingKey]));

        return in_array($value, $allowedValues, true) ? $value : null;
    }

    /**
     * Return the untrusted, normalized list of configured origins.
     *
     * @return array<int, string>
     */
    private function rawAllowedOrigins(): array
    {
        $origins = preg_split('/\r\n|\r|\n/', (string) $this->values['api_refresh_cookie_allowed_origins']) ?: [];
        $origins = array_map(static fn ($origin) => trim((string) $origin), $origins);
        $origins = array_filter($origins, static fn ($origin) => $origin !== '');

        return array_values(array_unique($origins));
    }

    /**
     * Determine whether every configured Origin is exact and valid.
     */
    private function hasExactAllowedOrigins(): bool
    {
        $origins = $this->rawAllowedOrigins();
        if ($origins === []) {
            return false;
        }

        foreach ($origins as $origin) {
            $parsed = parse_url($origin);
            $isExactOrigin = is_array($parsed)
                && isset($parsed['scheme'], $parsed['host'])
                && in_array(strtolower($parsed['scheme']), ['http', 'https'], true)
                && ! isset($parsed['path'], $parsed['query'], $parsed['fragment'], $parsed['user'], $parsed['pass'])
                && ! str_contains($parsed['host'], '*')
                && strtolower($origin) !== 'null';

            if (! $isExactOrigin) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine whether an optional cookie domain is a valid application parent.
     */
    private function isValidCookieDomain(string $domain): bool
    {
        $normalizedDomain = ltrim(strtolower($domain), '.');
        $appHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        $isDomain = preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/', $normalizedDomain) === 1;

        return $isDomain && $appHost !== '' && str_ends_with($appHost, '.'.$normalizedDomain);
    }

    /**
     * Determine whether SameSite=None has a secure application and origin boundary.
     */
    private function hasSecureSameSiteNoneConfiguration(): bool
    {
        if ($this->rawEnumValue('api_refresh_cookie_same_site', ['strict', 'lax', 'none']) !== 'none') {
            return true;
        }

        $origins = $this->rawAllowedOrigins();
        $appScheme = strtolower((string) parse_url((string) config('app.url'), PHP_URL_SCHEME));
        $secureCookies = $this->parseBoolean(config('session.secure')) === true;
        $hasSecureOrigins = $this->hasExactAllowedOrigins()
            && count(array_filter($origins, static fn ($origin) => str_starts_with(strtolower($origin), 'https://'))) === count($origins);

        return $appScheme === 'https'
            && $secureCookies
            && $hasSecureOrigins
            && $this->hasCredentialedHostCorsConfiguration();
    }

    /**
     * Determine whether host CORS covers auth endpoints with exact credentialed origins.
     */
    private function hasCredentialedHostCorsConfiguration(): bool
    {
        if (config('cors.supports_credentials') !== true) {
            return false;
        }

        $rawAllowedOrigins = (array) config('cors.allowed_origins', []);
        if (in_array('*', $rawAllowedOrigins, true)
            || (array) config('cors.allowed_origins_patterns', []) !== []) {
            return false;
        }

        $allowedOrigins = array_values(array_filter($rawAllowedOrigins, 'is_string'));
        foreach ($this->rawAllowedOrigins() as $origin) {
            if (! in_array($origin, $allowedOrigins, true)) {
                return false;
            }
        }

        $paths = $this->hostCorsPaths();
        foreach (AuthRouteSurface::corsRouteDescriptors() as $descriptor) {
            if (! collect($paths)->contains(fn (string $path): bool => $this->corsPathCoversRoute($path, $descriptor))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine whether one Laravel CORS path covers an entire route descriptor.
     *
     * @param  array{pattern: string, parameterized: bool}  $descriptor
     */
    private function corsPathCoversRoute(string $configuredPath, array $descriptor): bool
    {
        $configuredPattern = $configuredPath === '/' ? $configuredPath : trim($configuredPath, '/');
        $routePattern = $descriptor['pattern'];
        $fullUrlPattern = rtrim((string) config('app.url'), '/').'/'.$routePattern;

        if (! $descriptor['parameterized']) {
            if (Str::is($configuredPattern, $routePattern)) {
                return true;
            }

            return str_contains($configuredPattern, '*')
                && Str::is($configuredPattern, $fullUrlPattern)
                && Str::is($configuredPattern, $fullUrlPattern.'?wncms_query_probe=1');
        }

        return $this->wildcardPatternCovers($configuredPattern, $routePattern)
            || $this->wildcardPatternCovers($configuredPattern, $fullUrlPattern);
    }

    /**
     * Conservatively prove that one single-wildcard pattern covers another.
     */
    private function wildcardPatternCovers(string $configuredPattern, string $routePattern): bool
    {
        if (substr_count($configuredPattern, '*') !== 1 || substr_count($routePattern, '*') !== 1) {
            return false;
        }

        [$configuredPrefix, $configuredSuffix] = explode('*', $configuredPattern, 2);
        [$routePrefix, $routeSuffix] = explode('*', $routePattern, 2);

        return str_starts_with($routePrefix, $configuredPrefix)
            && str_ends_with($routeSuffix, $configuredSuffix);
    }

    /**
     * Return host-applicable paths using Laravel HandleCors configuration semantics.
     *
     * @return array<int, string>
     */
    private function hostCorsPaths(): array
    {
        $paths = (array) config('cors.paths', []);
        $appHost = (string) parse_url((string) config('app.url'), PHP_URL_HOST);
        $appPaths = $paths[$appHost] ?? array_filter($paths, 'is_string');

        return array_values(array_filter((array) $appPaths, 'is_string'));
    }

    /**
     * Determine whether configured progressive delays are valid and ascending.
     */
    private function isValidProgressiveDelay(): bool
    {
        $rawValue = trim((string) $this->values['api_login_progressive_delay_seconds']);
        $parts = $rawValue === '' ? [] : explode(',', $rawValue);
        $previous = -1;

        if ($parts === []) {
            return false;
        }

        foreach ($parts as $part) {
            $value = filter_var(trim($part), FILTER_VALIDATE_INT);
            if ($value === false || $value < 0 || $value > 300 || $value < $previous) {
                return false;
            }

            $previous = $value;
        }

        return true;
    }

    /**
     * Validate one integer setting range.
     *
     * @param  array<string, string>  $errors
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
     * @param  array<int, string>  $allowedValues
     */
    private function validateEnum(array &$errors, string $settingKey, array $allowedValues): void
    {
        $value = strtolower(trim((string) $this->values[$settingKey]));
        if (! in_array($value, $allowedValues, true)) {
            $errors[$settingKey] = 'Must be one of: '.implode(', ', $allowedValues).'.';
        }
    }

    /**
     * Validate one boolean setting.
     *
     * @param  array<string, string>  $errors
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
     */
    private function validateCookieDomain(array &$errors): void
    {
        $domain = trim((string) $this->values['api_refresh_cookie_domain']);
        if ($domain === '') {
            return;
        }

        if (! $this->isValidCookieDomain($domain)) {
            $errors['api_refresh_cookie_domain'] = 'Must be empty or a valid parent domain for the application host.';
        }
    }

    /**
     * Validate exact configured origins.
     *
     * @param  array<string, string>  $errors
     */
    private function validateAllowedOrigins(array &$errors): void
    {
        if (! $this->hasExactAllowedOrigins() && $this->rawAllowedOrigins() !== []) {
            $errors['api_refresh_cookie_allowed_origins'] = 'Must contain newline-separated exact HTTP or HTTPS origins.';
        }
    }

    /**
     * Validate progressive login failure delays.
     *
     * @param  array<string, string>  $errors
     */
    private function validateProgressiveDelay(array &$errors): void
    {
        if (! $this->isValidProgressiveDelay()) {
            $errors['api_login_progressive_delay_seconds'] = 'Must be ascending comma-separated integers between 0 and 300.';
        }
    }

    /**
     * Validate the optional UTC cutoff timestamp.
     *
     * @param  array<string, string>  $errors
     */
    private function validateLegacyCutoff(array &$errors): void
    {
        $value = trim((string) $this->values['api_legacy_personal_tokens_cutoff_at']);
        if ($value === '') {
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
     */
    private function validateCookieCompatibility(array &$errors): void
    {
        $transport = $this->rawEnumValue('api_refresh_transport', ['json', 'cookie']);
        $sameSite = $this->rawEnumValue('api_refresh_cookie_same_site', ['strict', 'lax', 'none']);

        if ($transport === 'cookie' && ! $this->hasExactAllowedOrigins()) {
            $errors['api_refresh_cookie_allowed_origins'] = 'At least one exact allowed origin is required when Cookie refresh transport is enabled.';
        }

        if ($transport === 'cookie'
            && $this->hasExactAllowedOrigins()
            && ! $this->hasCredentialedHostCorsConfiguration()) {
            $errors['api_refresh_cookie_allowed_origins'] = 'Cookie refresh transport requires exact credentialed host CORS coverage for every API auth path.';
        }

        if ($sameSite !== 'none') {
            return;
        }

        if (! $this->hasSecureSameSiteNoneConfiguration()) {
            $errors['api_refresh_cookie_same_site'] = 'SameSite=None requires HTTPS, secure cookies, and exact host credentialed CORS for API auth paths.';
        }
    }

    /**
     * Return a validated integer value or its stable default.
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

        if ($value === false || ! isset($ranges[$settingKey]) || $value < $ranges[$settingKey][0] || $value > $ranges[$settingKey][1]) {
            return (int) self::defaultFor($this->defaults, $settingKey);
        }

        return (int) $value;
    }

    /**
     * Return a validated enum value or its stable default.
     *
     * @param  array<int, string>  $allowedValues
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

        return array_map(static fn ($delay) => (int) trim($delay), explode(',', $value));
    }
}
