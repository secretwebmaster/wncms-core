<?php

namespace Wncms\Services\Managers;

use Illuminate\Support\Facades\File;
use Wncms\Models\Theme;

class ThemeManager
{
    /**
     * Get all available themes detected in /public/themes directory.
     *
     * This function scans the filesystem and returns a flat array of theme IDs.
     * A theme is considered AVAILABLE if and only if:
     *   - A directory exists under /public/themes/{themeId}
     *
     * No database lookup is performed.
     *
     * @example
     *   /public/themes/demo
     *   /public/themes/starter
     *   /public/themes/default
     *
     * Returns:
     *   ['demo', 'starter', 'default']
     *
     * @return array<int, string>
     */
    public function getThemes(): array
    {
        $themeDir = public_path('themes');

        if (!is_dir($themeDir)) {
            return [];
        }

        return collect(File::directories($themeDir))
            ->map(fn($path) => basename($path))
            ->values()
            ->toArray();
    }

    public function getThemeMetas()
    {
        $themes = $this->getThemes();
        $metas = [];

        foreach ($themes as $themeId) {
            $configPath = public_path("themes/{$themeId}/config.php");
            if (File::exists($configPath)) {
                $config = include $configPath;
                $metas[$themeId] = $config['info'];
                if (is_array($metas[$themeId]['name'])) {
                    $metas[$themeId]['name'] = $metas[$themeId]['name'][app()->getLocale()] ?? $themeId;
                }
                $metas[$themeId]['isValid'] = true;
            } else {
                $metas[$themeId] = [
                    'id' => $themeId,
                    'type' => 'blog',
                    'name' => $themeId,
                    'description' => '',
                    'author' => 'unknown',
                    'version' => 'unknown',
                    'created_at' => null,
                    'updated_at' => null,
                    'demo_url' => null,
                    'isValid' => false,
                ];
            }
        }

        return $metas;
    }

    /**
     * Resolve a translation key for the given theme with fallback behavior.
     *
     * Priorities:
     * Try theme-based translation: __('themeId.key')
     * Try old theme-based translation: __('themeId::word.key')
     * Try local CMS translation: __('wncms::word.key')
     * Fallback to raw key name
     */
    public function translate(string $themeId, string $key): string
    {
        // try theme namespace first
        $translationKey = "{$themeId}::word.{$key}";
        $translated = __($translationKey);
        if ($translated !== $translationKey) {
            return $translated;
        }

        // compatible to old way
        $translationKey = "{$themeId}.{$key}";
        $translated = __($translationKey);
        if ($translated !== $translationKey) {
            return $translated;
        }

        // fallback to wncms default translation
        $fallbackKey = "wncms::word.{$key}";
        $fallback = __($fallbackKey);

        if ($fallback !== $fallbackKey) {
            return $fallback;
        }

        // last fallback: raw key
        return ucfirst(str_replace('_', ' ', $key));
    }

    public function asset($themeId, $path, $type = null)
    {
        if ($type === null) {
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            $type = $ext ? '.' . $ext : '';

            if(in_array($type, ['.js', '.css'])) {
                // keep
            } else {
                $type = '';
            }
        }
        return asset("themes/{$themeId}/assets/{$path}") . wncms()->addVersion($type);
    }

    public function view($themeId, $path)
    {
        return "{$themeId}::{$path}";
    }
}
