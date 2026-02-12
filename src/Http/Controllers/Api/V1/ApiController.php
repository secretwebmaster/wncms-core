<?php

namespace Wncms\Http\Controllers\Api\V1;

use Wncms\Http\Controllers\Controller;
use Illuminate\Http\Request;

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
        ]);
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
        ]);
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

        if (empty($mode)) {
            return ['user' => null];
        }

        if ($mode === 'simple') {
            $token = $request->input('api_token');

            if (empty($token)) {
                return ['error' => $this->fail('Missing api_token', 401)];
            }

            $userModel = wncms()->getModelClass('user');
            $user = $userModel::where('api_token', $token)->first();

            if (!$user) {
                return ['error' => $this->fail('Invalid api_token', 401)];
            }

            auth()->login($user);
            return ['user' => $user];
        }

        return ['error' => $this->fail("Unsupported auth mode: {$mode}", 403)];
    }
}
