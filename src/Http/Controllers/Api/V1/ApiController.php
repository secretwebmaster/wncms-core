<?php

namespace Wncms\Http\Controllers\Api\V1;

use Wncms\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ApiController extends Controller
{
    /**
     * Normalize locale keys to the project canonical format.
     * Example: "zh-TW" => "zh_TW", "EN" => "en".
     */
    protected function normalizeLocaleKey(string $locale): string
    {
        $locale = str_replace('-', '_', trim($locale));
        if ($locale === '') {
            return $locale;
        }

        $parts = explode('_', $locale);
        $parts[0] = strtolower($parts[0]);

        if (isset($parts[1]) && strlen($parts[1]) === 2) {
            $parts[1] = strtoupper($parts[1]);
        }

        return implode('_', $parts);
    }

    /**
     * Decode JSON-like translation payloads when input is a JSON string.
     * Non-JSON strings and non-string values are returned as-is.
     */
    protected function decodeTranslationInput(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        $trimmed = trim($value);
        if ($trimmed === '' || !in_array($trimmed[0], ['{', '['])) {
            return $value;
        }

        $decoded = json_decode($trimmed, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }

    /**
     * Convert mixed translation input into a normalized locale=>value map.
     * Only associative arrays are treated as translation maps.
     */
    protected function normalizeTranslationMap(mixed $value): array
    {
        $value = $this->decodeTranslationInput($value);

        if (!is_array($value) || array_is_list($value)) {
            return [];
        }

        $translations = [];
        foreach ($value as $locale => $localizedValue) {
            if (!is_string($locale)) {
                continue;
            }

            if (!is_scalar($localizedValue) && !is_null($localizedValue)) {
                continue;
            }

            $normalizedLocale = $this->normalizeLocaleKey($locale);
            if ($normalizedLocale === '') {
                continue;
            }

            $translations[$normalizedLocale] = is_null($localizedValue) ? '' : (string) $localizedValue;
        }

        return $translations;
    }

    /**
     * Extract a plain-text base value from translation-capable input.
     * Priority: current locale, fallback locale, then first non-empty value.
     */
    protected function extractPlainTextValue(mixed $value): ?string
    {
        $value = $this->decodeTranslationInput($value);

        if (is_null($value)) {
            return null;
        }

        if (is_array($value)) {
            $translations = $this->normalizeTranslationMap($value);
            $currentLocale = $this->normalizeLocaleKey(app()->getLocale());
            $fallbackLocale = $this->normalizeLocaleKey(app()->getFallbackLocale());

            $candidates = [
                $translations[$currentLocale] ?? null,
                $translations[$fallbackLocale] ?? null,
            ];

            foreach ($candidates as $candidate) {
                if (is_scalar($candidate) && trim((string) $candidate) !== '') {
                    return (string) $candidate;
                }
            }

            foreach ($translations as $candidate) {
                if (is_scalar($candidate) && trim((string) $candidate) !== '') {
                    return (string) $candidate;
                }
            }

            return null;
        }

        if (is_string($value)) {
            return trim($value) === '' ? '' : $value;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return null;
    }

    /**
     * Resolve model class name from object/class-string input.
     */
    protected function resolveModelClass(mixed $model): ?string
    {
        if (is_object($model)) {
            return get_class($model);
        }

        if (is_string($model) && class_exists($model)) {
            return $model;
        }

        return null;
    }

    /**
     * Read translatable fields from a model via getTranslatable().
     * Returns empty array when model does not support translations.
     */
    protected function getModelTranslatableFields(mixed $model): array
    {
        $modelClass = $this->resolveModelClass($model);
        if (empty($modelClass)) {
            return [];
        }

        $model = new $modelClass;
        return method_exists($model, 'getTranslatable')
            ? (array) $model->getTranslatable()
            : [];
    }

    /**
     * Build normalized input data for translatable fields from request payload.
     * Each field includes:
     * - base: plain value for model column
     * - translations: locale=>value map for setTranslation()
     */
    protected function getNormalizedTranslatableInputs(Request $request, mixed $model, ?array $fields = null): array
    {
        $translatableFields = $fields ?? $this->getModelTranslatableFields($model);

        $normalized = [];
        foreach ($translatableFields as $field) {
            if (!is_string($field) || !$request->has($field)) {
                continue;
            }

            $input = $request->input($field);
            $normalized[$field] = [
                'base' => $this->extractPlainTextValue($input),
                'translations' => $this->normalizeTranslationMap($input),
            ];
        }

        return $normalized;
    }

    /**
     * Overwrite request values with normalized base values before mass-assign.
     * This keeps DB columns plain-text while translations are persisted separately.
     */
    protected function mergeTranslatableBaseValuesIntoRequest(Request $request, array $normalizedInputs): void
    {
        foreach ($normalizedInputs as $field => $data) {
            $request->merge([
                $field => $data['base'] ?? null,
            ]);
        }
    }

    /**
     * Persist normalized translations onto a model that supports setTranslation().
     */
    protected function applyModelTranslations($model, array $normalizedInputs): void
    {
        if (!method_exists($model, 'setTranslation')) {
            return;
        }

        foreach ($normalizedInputs as $field => $data) {
            foreach (($data['translations'] ?? []) as $locale => $value) {
                $model->setTranslation($field, $locale, $value);
            }
        }
    }

    /**
     * Unified success response
     */
    protected function success($data = [], string $message = 'success', int $code = 200, array $extra = [])
    {
        return response()->json([
            'code' => $code,
            'status' => 'success',
            'message' => $message,
            'data' => $data,
            'extra' => $extra,
        ], $code);
    }

    /**
     * Unified fail response
     */
    protected function fail(string $message = 'fail', int $code = 400, array $extra = [], $data = [])
    {
        return response()->json([
            'code' => $code,
            'status' => 'fail',
            'message' => $message,
            'data' => $data,
            'extra' => $extra,
        ], $code);
    }

    /**
     * Check if API feature is enabled
     */
    protected function checkEnabled(string $key)
    {
        if (!gss($key)) {
            return $this->fail("API feature '{$key}' is disabled", 403);
        }
        return null;
    }

    /**
     * Auth handler
     */
    protected function checkAuthSetting(string $baseKey, Request $request)
    {
        $mode = gss($baseKey . '_should_auth'); // '' | simple | basic

        if ($whitelistError = $this->checkApiWhitelist($request)) {
            return ['error' => $whitelistError];
        }

        if (empty($mode)) {
            return ['user' => null];
        }

        if ($mode === 'simple') {
            if (!$request->filled('api_token')) {
                return ['error' => $this->fail('Missing api_token', 401)];
            }

            $user = $this->resolveApiUserFromToken($request);
            if (!$user) {
                return ['error' => $this->fail('Invalid api_token', 401)];
            }

            auth()->login($user);
            return ['user' => $user];
        }

        if ($mode === 'basic') {
            [$email, $password] = $this->extractBasicCredentials($request);
            if (empty($email) || empty($password)) {
                return ['error' => $this->fail('Missing Basic credentials', 401)];
            }

            $user = $this->resolveApiUserFromBasicCredentials($email, $password);
            if (!$user) {
                return ['error' => $this->fail('Invalid Basic credentials', 401)];
            }

            auth()->login($user);
            return ['user' => $user];
        }

        return ['error' => $this->fail("Unsupported auth mode: {$mode}", 403)];
    }

    /**
     * Parse the global API whitelist into normalized line-based entries.
     */
    protected function getApiWhitelistEntries(): array
    {
        $raw = (string) gss('api_access_whitelist', '');
        if (trim($raw) === '') {
            return [];
        }

        $entries = preg_split('/\r\n|\r|\n/', $raw) ?: [];

        return array_values(array_filter(array_map(function ($entry) {
            $entry = strtolower(trim((string) $entry));
            if ($entry === '') {
                return null;
            }

            return $this->normalizeWhitelistDomain($entry) ?? $entry;
        }, $entries)));
    }

    protected function checkApiWhitelist(Request $request)
    {
        $entries = $this->getApiWhitelistEntries();
        if (empty($entries)) {
            return null;
        }

        $requestIp = $this->getApiRequestIp($request);
        $requestDomain = $this->getApiRequestDomain($request);

        if ($this->requestMatchesApiWhitelist($entries, $requestIp, $requestDomain)) {
            return null;
        }

        return $this->fail('Request IP or domain is not in the API whitelist', 403);
    }

    protected function getApiRequestIp(Request $request): ?string
    {
        $ip = $request->ip();
        return is_string($ip) && trim($ip) !== '' ? trim($ip) : null;
    }

    protected function getApiRequestDomain(Request $request): ?string
    {
        foreach (['Origin', 'Referer'] as $header) {
            $value = trim((string) $request->headers->get($header, ''));
            if ($value === '') {
                continue;
            }

            $host = parse_url($value, PHP_URL_HOST);
            if (is_string($host) && trim($host) !== '') {
                return strtolower(trim($host));
            }
        }

        return null;
    }

    protected function requestMatchesApiWhitelist(array $entries, ?string $requestIp, ?string $requestDomain): bool
    {
        foreach ($entries as $entry) {
            if ($requestIp && $entry === $requestIp) {
                return true;
            }

            if ($requestDomain && $entry === $requestDomain) {
                return true;
            }
        }

        return false;
    }

    protected function normalizeWhitelistDomain(string $value): ?string
    {
        $value = strtolower(trim($value));
        if ($value === '') {
            return null;
        }

        $candidate = str_contains($value, '://') ? $value : "https://{$value}";
        $host = parse_url($candidate, PHP_URL_HOST);

        if (!is_string($host) || trim($host) === '') {
            return null;
        }

        return strtolower(trim($host));
    }

    protected function resolveApiUserFromToken(Request $request)
    {
        $token = trim((string) $request->input('api_token', ''));
        if ($token === '') {
            return null;
        }

        $userModel = wncms()->getModelClass('user');
        return $userModel::where('api_token', $token)->first();
    }

    protected function extractBasicCredentials(Request $request): array
    {
        $username = $request->getUser();
        $password = $request->getPassword();

        return [
            is_string($username) ? trim($username) : null,
            is_string($password) ? $password : null,
        ];
    }

    protected function resolveApiUserFromBasicCredentials(string $email, string $password)
    {
        $userModel = wncms()->getModelClass('user');
        $user = $userModel::where('email', $email)->first();

        if (!$user || empty($user->password) || !Hash::check($password, $user->password)) {
            return null;
        }

        return $user;
    }
}
