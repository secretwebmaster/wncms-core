<?php

namespace Wncms\Services\Managers;

use Illuminate\Support\Facades\File;

class ThemeManager
{
    public const CORE_THEMES = ['default', 'starter', 'demo'];

    /**
     * Get all available themes.
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

    /**
     * Get metadata for all available themes.
     */
    public function getThemeMetas(): array
    {
        $metas = [];

        foreach ($this->getThemes() as $themeId) {
            [$configFile, $themePath] = $this->resolveThemeConfigFile($themeId);

            if ($configFile) {
                $config = include $configFile;
                $metas[$themeId] = $this->buildMetaFromConfig($themeId, $config, $themePath);
                continue;
            }

            $metas[$themeId] = $this->buildInvalidMeta($themeId, public_path("themes/{$themeId}"));
        }

        return $metas;
    }

    public function getThemePages(string $themeId): array
    {
        $pages = config('theme.' . $themeId . '.pages') ?? [];

        foreach ($pages as $key => $page) {
            if (!isset($page['route'])) {
                $pages[$key]['url'] = null;
                continue;
            }

            $fullUrl = wncms()->getRoute($page['route'], $page['route_params'] ?? []);

            $path = parse_url($fullUrl, PHP_URL_PATH) ?? '/';
            $query = parse_url($fullUrl, PHP_URL_QUERY);
            $fragment = parse_url($fullUrl, PHP_URL_FRAGMENT);

            $relative = $path;
            if ($query) {
                $relative .= '?' . $query;
            }
            if ($fragment) {
                $relative .= '#' . $fragment;
            }

            $pages[$key]['url'] = $relative;
        }

        return $pages;
    }

    /**
     * Resolve the configuration file path for a given theme ID.
     */
    protected function resolveThemeConfigFile(string $themeId): array
    {
        $localThemePath = public_path("themes/{$themeId}");
        $localConfigFile = $localThemePath . '/config.php';

        if (File::exists($localConfigFile)) {
            return [$localConfigFile, $localThemePath];
        }

        if (in_array($themeId, self::CORE_THEMES, true) && defined('WNCMS_RESOURCES_PATH')) {
            $coreThemePath = WNCMS_RESOURCES_PATH . 'themes' . DIRECTORY_SEPARATOR . $themeId;
            $coreConfigFile = $coreThemePath . '/config.php';

            if (File::exists($coreConfigFile)) {
                return [$coreConfigFile, $coreThemePath];
            }
        }

        return [null, null];
    }

    /**
     * Build theme metadata from configuration array.
     */
    protected function buildMetaFromConfig(string $themeId, array $config, string $themePath): array
    {
        $meta = $config['info'] ?? [];
        $meta['id'] = $meta['id'] ?? $themeId;

        // name
        if (isset($meta['name']) && is_array($meta['name'])) {
            $meta['name'] = $meta['name'][app()->getLocale()] ?? $themeId;
        } else {
            $meta['name'] = $meta['name'] ?? $themeId;
        }

        // description
        if (isset($meta['description']) && is_array($meta['description'])) {
            $meta['description'] = $meta['description'][app()->getLocale()] ?? '';
        } else {
            $meta['description'] = $meta['description'] ?? '';
        }

        $meta['isValid'] = true;
        $meta['path'] = $themePath;
        
        // thumbnail
        $thumbnailPath = "themes/{$themeId}/assets/images/screenshot.png";
        if (File::exists(public_path($thumbnailPath))) {
            $meta['screenshot'] = asset($thumbnailPath) . wncms()->addVersion('image');
        } else {
            $meta['screenshot'] = null;
        }

        return $meta;
    }

    /**
     * Build invalid theme metadata.
     */
    protected function buildInvalidMeta(string $themeId, string $themePath): array
    {
        return [
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
            'path' => $themePath,
        ];
    }

    /**
     * Translate a key for a given theme, with fallback to core translations.
     */
    public function translate(string $themeId, string $key): string
    {
        $translationKey = "{$themeId}::word.{$key}";
        $translated = __($translationKey);
        if ($translated !== $translationKey) {
            return $translated;
        }

        $translationKey = "{$themeId}.{$key}";
        $translated = __($translationKey);
        if ($translated !== $translationKey) {
            return $translated;
        }

        $fallbackKey = "wncms::word.{$key}";
        $fallback = __($fallbackKey);

        if ($fallback !== $fallbackKey) {
            return $fallback;
        }

        return ucfirst(str_replace('_', ' ', $key));
    }

    /**
     * Get asset URL for a given theme.
     */
    public function asset($themeId, $path, $type = null)
    {
        if ($type === null) {
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            $type = $ext ? '.' . $ext : '';

            if (in_array($type, ['.js', '.css'], true)) {
                $type = str_replace('.', '', $type);
            } else {
                $type = '';
            }
        }

        return asset("themes/{$themeId}/assets/{$path}") . wncms()->addVersion($type);
    }

    /**
     * Get view namespace for a given theme.
     */
    public function view($themeId, $path)
    {
        return "{$themeId}::{$path}";
    }
}
