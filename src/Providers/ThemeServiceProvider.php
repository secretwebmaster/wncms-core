<?php

namespace Wncms\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Response;
use Wncms\Services\Managers\ThemeManager;

class ThemeServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(\Wncms\Services\Managers\ThemeManager::class, fn($app) => new \Wncms\Services\Managers\ThemeManager);
    }

    public function boot(): void
    {
        // Only load theme after installation.
        if (!function_exists('wncms_is_installed') || !wncms_is_installed()) {
            return;
        }

        if (!defined('WNCMS_THEME_START')) {
            define('WNCMS_THEME_START', true);
        }

        // Detect current website
        $website = wncms()->website()->get();
        if (!$website) {
            return; // No website found (multi-site disabled or installation incomplete)
        }

        $themeId = $website->theme ?: 'default';
        Event::dispatch('wncms.frontend.themes.boot.before', [&$themeId, $website]);
        view()->share('themeId', $themeId);

        if (gss('multi_website')) {
            $themes = wncms()->website()->getList()->pluck('theme')->unique()->toArray();
            foreach ($themes as $theme) {
                $theme = $theme ?: 'default';
                $themePath = $this->resolveThemePath($theme);
                Event::dispatch('wncms.frontend.themes.load.before', [&$theme, &$themePath, $website]);
                if (!$themePath) {
                    continue;
                }

                // Load config.php
                $this->loadThemeConfig($theme, $themePath);

                // Load views
                $this->loadThemeViews($theme, $themePath);

                // Load translations
                $this->loadThemeTranslations($theme, $themePath);

                // Load functions.php
                $this->loadThemeFunctions($themePath);
                Event::dispatch('wncms.frontend.themes.load.after', [$theme, $themePath, $website]);
            }
        } else {
            // Determine theme path
            $themePath = $this->resolveThemePath($themeId);
            Event::dispatch('wncms.frontend.themes.load.before', [&$themeId, &$themePath, $website]);

            // Missing non-core themes still show inactive screen
            if (!$themePath) {
                $this->showThemeInactiveScreen();
                return; // STOP further boot process
            }

            // Load config.php
            $this->loadThemeConfig($themeId, $themePath);

            // Load views
            $this->loadThemeViews($themeId, $themePath);

            // Load translations
            $this->loadThemeTranslations($themeId, $themePath);

            // Load functions.php
            $this->loadThemeFunctions($themePath);
            Event::dispatch('wncms.frontend.themes.load.after', [$themeId, $themePath, $website]);
        }

        Event::dispatch('wncms.frontend.themes.boot.after', [$themeId, $website]);
    }

    protected function resolveThemePath(string $themeId): ?string
    {
        $publicThemePath = public_path("themes/{$themeId}");
        if (File::exists($publicThemePath)) {
            return $publicThemePath;
        }

        if (in_array($themeId, ThemeManager::CORE_THEMES)) {
            $coreThemePath = WNCMS_RESOURCES_PATH . 'themes' . DIRECTORY_SEPARATOR . $themeId;
            if (File::exists($coreThemePath)) {
                return $coreThemePath;
            }
        }

        return null;
    }

    /**
     * Immediately show the theme inactive screen.
     * No View::composer, no recursion, no stuck output.
     */
    protected function showThemeInactiveScreen(): void
    {
        // try {
        //     echo view('wncms::errors.theme_inactive')->render();
        // } catch (\Throwable $e) {
        //     echo 'The active theme is missing. Please contact the administrator.';
        // }
        return;
    }

    /**
     * Load /public/themes/{theme}/config.php into config("theme.{themeId}")
     */
    protected function loadThemeConfig(string $themeId, string $themePath): void
    {
        $configFile = $themePath . '/config.php';

        if (File::exists($configFile)) {
            $config = include $configFile;

            // Store as config('theme.demo')
            config(["theme.{$themeId}" => $config]);
        }

        // fallback to core themes
        elseif (in_array($themeId, ThemeManager::CORE_THEMES)) {

            $coreThemePath = WNCMS_RESOURCES_PATH . 'themes' . DIRECTORY_SEPARATOR . $themeId;
            $coreConfigFile = $coreThemePath . '/config.php';

            if (File::exists($coreConfigFile)) {
                $config = include $coreConfigFile;

                // Store as config('theme.demo')
                config(["theme.{$themeId}" => $config]);
            }
        }
    }

    /**
     * Register the theme view namespace:
     * usage:  demo::index
     *         demo::layouts.app
     */
    protected function loadThemeViews(string $themeId, string $themePath): void
    {
        $viewPath = $themePath . '/views';

        if (File::exists($viewPath)) {
            $namespace = "{$themeId}";
            $this->loadViewsFrom($viewPath, $namespace);
        }

        // Fallback to wncms-core theme views
        elseif (in_array($themeId, ThemeManager::CORE_THEMES)) {
            $coreThemePath = WNCMS_RESOURCES_PATH . 'themes' . DIRECTORY_SEPARATOR . $themeId;
            $coreViewPath = $coreThemePath . '/views';

            if (File::exists($coreViewPath)) {
                $namespace = "{$themeId}";
                $this->loadViewsFrom($coreViewPath, $namespace);
            }
        }
    }

    /**
     * Load translations from /public/themes/{theme}/lang
     * usage:
     *     __('demo.header')
     *     @lang('demo.footer')
     */
    protected function loadThemeTranslations(string $themeId, string $themePath): void
    {
        $langPath = $themePath . '/lang';

        if (File::exists($langPath)) {
            $this->loadTranslationsFrom($langPath, $themeId);
        }
        // Fallback to wncms-core theme translations
        elseif (in_array($themeId, ThemeManager::CORE_THEMES)) {
            $coreThemePath = WNCMS_RESOURCES_PATH . 'themes' . DIRECTORY_SEPARATOR . $themeId;
            $coreLangPath = $coreThemePath . '/lang';

            if (File::exists($coreLangPath)) {
                $this->loadTranslationsFrom($coreLangPath, $themeId);
            }
        }
    }

    /**
     * Require /public/themes/{theme}/functions.php
     * Used for theme helpers, custom hooks, filters
     */
    protected function loadThemeFunctions(string $themePath): void
    {
        $functionsFile = $themePath . '/functions.php';

        if (File::exists($functionsFile)) {
            require_once $functionsFile;
        }

        // Fallback to wncms-core theme functions.php
        elseif (in_array(basename($themePath), ThemeManager::CORE_THEMES)) {
            $coreThemePath = WNCMS_RESOURCES_PATH . 'themes' . DIRECTORY_SEPARATOR . basename($themePath);
            $coreFunctionsFile = $coreThemePath . '/functions.php';

            if (File::exists($coreFunctionsFile)) {
                require_once $coreFunctionsFile;
            }
        }
    }
}
