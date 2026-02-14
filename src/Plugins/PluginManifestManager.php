<?php

namespace Wncms\Plugins;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PluginManifestManager
{
    public function readManifestPath(string $manifestPath): array
    {
        if (!File::exists($manifestPath)) {
            return [
                'passed' => false,
                'message' => 'plugin.json not found',
                'manifest' => [],
            ];
        }

        $manifest = json_decode((string) File::get($manifestPath), true);
        if (!is_array($manifest)) {
            return [
                'passed' => false,
                'message' => 'plugin.json is invalid JSON',
                'manifest' => [],
            ];
        }

        return [
            'passed' => true,
            'message' => '',
            'manifest' => $manifest,
        ];
    }

    public function validateRequired(array $manifest, array $requiredKeys = ['id', 'name', 'version']): array
    {
        foreach ($requiredKeys as $key) {
            $value = $manifest[$key] ?? null;
            $isValid = $key === 'name'
                ? $this->hasValidTranslatableField($value)
                : (is_string($value) && trim($value) !== '');

            if (!$isValid) {
                return [
                    'passed' => false,
                    'message' => "plugin.json missing required key: {$key}",
                ];
            }
        }

        return [
            'passed' => true,
            'message' => '',
        ];
    }

    public function readAndValidateManifestPath(string $manifestPath): array
    {
        $read = $this->readManifestPath($manifestPath);
        if (!$read['passed']) {
            return $read;
        }

        $validation = $this->validateRequired($read['manifest']);
        if (!$validation['passed']) {
            return [
                'passed' => false,
                'message' => $validation['message'],
                'manifest' => $read['manifest'],
            ];
        }

        return [
            'passed' => true,
            'message' => '',
            'manifest' => $read['manifest'],
        ];
    }

    public function hasValidTranslatableField($value): bool
    {
        if (is_string($value)) {
            return trim($value) !== '';
        }

        if (!is_array($value) || empty($value)) {
            return false;
        }

        foreach ($value as $localizedValue) {
            if (is_string($localizedValue) && trim($localizedValue) !== '') {
                return true;
            }
        }

        return false;
    }

    public function resolveTranslatableField($value, string $fallback = ''): string
    {
        if (is_string($value)) {
            $value = trim($value);
            return $value === '' ? $fallback : $value;
        }

        if (!is_array($value)) {
            return $fallback;
        }

        $locale = (string) app()->getLocale();
        $localeCandidates = array_values(array_unique(array_filter([
            $locale,
            str_replace('-', '_', $locale),
            str_replace('_', '-', $locale),
            strtok(str_replace('-', '_', $locale), '_') ?: null,
            'en',
        ])));

        foreach ($localeCandidates as $candidate) {
            if (isset($value[$candidate]) && is_string($value[$candidate]) && trim($value[$candidate]) !== '') {
                return trim($value[$candidate]);
            }
        }

        foreach ($value as $localizedValue) {
            if (is_string($localizedValue) && trim($localizedValue) !== '') {
                return trim($localizedValue);
            }
        }

        return $fallback;
    }

    public function resolvePluginId(array $manifest, string $folderName): string
    {
        $pluginId = (string) ($manifest['id'] ?? Str::slug($folderName, '-'));

        if ($pluginId === '') {
            return Str::slug($folderName, '-');
        }

        return $pluginId;
    }
}
