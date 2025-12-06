<?php

namespace Wncms\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Response;

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

        if (gss('multi_website')) {
            $themes = wncms()->website()->getList()->pluck('theme')->unique()->toArray();
            foreach ($themes as $theme) {
                $themePath = public_path("themes/{$theme}");
                if (!File::exists($themePath)) {
                    continue;
                }
                $this->loadThemeConfig($theme, $themePath);

                // Load config.php
                $this->loadThemeConfig($theme, $themePath);

                // Load views
                $this->loadThemeViews($theme, $themePath);

                // Load translations
                $this->loadThemeTranslations($theme, $themePath);

                // Load functions.php
                $this->loadThemeFunctions($themePath);
            }
        } else {
            // Determine theme path
            $themePath = public_path("themes/{$themeId}");

            // If theme folder is missing → immediately show inactive theme screen
            if (!File::exists($themePath)) {
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
        }


        view()->share('themeId', $themeId);
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
    }
}
