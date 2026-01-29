<?php

namespace Wncms\Providers;

use Exception;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Wncms\Exceptions\WncmsExceptionHandler;
use Illuminate\Support\Facades\File;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

class WncmsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->defineConstants();

        // Facades
        $this->loadFacades();

        // Alias
        $this->loadAlias();

        // Exception handler
        $this->loadExceptionHandler();

        // Package configs
        $this->mergeConfigs();

        // Register service providers
        $this->registerServiceProviders();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        define('WNCMS_CORE_PATH', base_path('vendor/secretwebmaster/wncms-core/'));
        config('app.debug') ? error_reporting(E_ALL) : error_reporting(0);

        $this->loadSystemSettings();

        $this->loadTranslationSettings();

        // Middleware aliases
        $router = $this->app['router'];
        $router->aliasMiddleware('localize', \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes::class);
        $router->aliasMiddleware('localizationRedirect', \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter::class);
        $router->aliasMiddleware('localeSessionRedirect', \Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect::class);
        $router->aliasMiddleware('localeCookieRedirect', \Mcamara\LaravelLocalization\Middleware\LocaleCookieRedirect::class);
        $router->aliasMiddleware('localeViewPath', \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath::class);
        $router->aliasMiddleware('is_installed', \Wncms\Http\Middleware\IsInstalled::class);
        $router->aliasMiddleware('has_website', \Wncms\Http\Middleware\HasWebsite::class);
        $router->aliasMiddleware('full_page_cache', \Wncms\Http\Middleware\FullPageCache::class);

        // Exclude paths from CSRF check
        $this->app->resolving(VerifyCsrfToken::class, function ($csrf) {
            $csrf->except('panel/uploads/image');
            $csrf->except('install/*');
        });

        // Core resources
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'wncms');
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'wncms');
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->registerModels();

        // Console commands & publishable files
        if ($this->app->runningInConsole()) {
            $this->loadCommands();
            $this->loadPublishFiles();
        }

        try {
            if (config('app.force_https') || gss('force_https') || request()->force_https) {
                \URL::forceScheme('https');
            }

            // View and shared variables
            $this->loadGlobalVariables();

            Paginator::useBootstrap();
        } catch (Exception $e) {
            logger()->error($e);
        }
    }

    /**
     * Define WNCMS core constants.
     */
    protected function defineConstants(): void
    {
        if (!defined('WNCMS_START')) {
            define('WNCMS_START', true);
        }

        // package root
        if (!defined('WNCMS_ROOT')) {
            $root = realpath(__DIR__ . '/../../');
            define('WNCMS_ROOT', rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
        }

        // config path
        if (!defined('WNCMS_CONFIG_PATH')) {
            define('WNCMS_CONFIG_PATH', WNCMS_ROOT . 'config' . DIRECTORY_SEPARATOR);
        }

        // database path
        if (!defined('WNCMS_DATABASE_PATH')) {
            define('WNCMS_DATABASE_PATH', WNCMS_ROOT . 'database' . DIRECTORY_SEPARATOR);
        }

        // src path
        if (!defined('WNCMS_APP_PATH')) {
            define('WNCMS_APP_PATH', WNCMS_ROOT . 'src' . DIRECTORY_SEPARATOR);
        }

        // resources path
        if (!defined('WNCMS_RESOURCES_PATH')) {
            define('WNCMS_RESOURCES_PATH', WNCMS_ROOT . 'resources' . DIRECTORY_SEPARATOR);
        }

        // lang path
        if (!defined('WNCMS_LANG_PATH')) {
            define('WNCMS_LANG_PATH', WNCMS_RESOURCES_PATH . 'lang' . DIRECTORY_SEPARATOR);
        }

        // route path
        if (!defined('WNCMS_ROUTE_PATH')) {
            define('WNCMS_ROUTE_PATH', WNCMS_ROOT . 'routes' . DIRECTORY_SEPARATOR);
        }

        // update path
        if (!defined('WNCMS_UPDATE_PATH')) {
            define('WNCMS_UPDATE_PATH', WNCMS_DATABASE_PATH . 'updates' . DIRECTORY_SEPARATOR);
        }
    }

    /**
     * Load WNCMS facades.
     */
    protected function loadFacades(): void
    {
        $this->app->singleton('wncms', fn($app) => new \Wncms\Services\Wncms);
        $this->app->singleton('macroable-models', fn($app) => new \Wncms\Services\MacroableModels\MacroableModels);
        $this->app->singleton(\Wncms\Services\Managers\TagManager::class, fn($app) => new \Wncms\Services\Managers\TagManager);
    }

    /**
     * Load class aliases.
     */
    protected function loadAlias(): void
    {
        $loader = AliasLoader::getInstance();
        $loader->alias('Wncms', \Wncms\Facades\Wncms::class);
    }

    /**
     * Merge package configuration files.
     */
    protected function mergeConfigs(): void
    {
        $configs = [
            'installer',
            'laravellocalization',
            'media-library',
            'translatable',
            'wncms-system-settings',
            'wncms-tags',
            'permission',
            'wncms',
        ];

        foreach ($configs as $config) {
            $this->mergeConfigFrom(__DIR__ . "/../../config/{$config}.php", $config);
        }
    }

    /**
     * Load exception handler.
     */
    protected function loadExceptionHandler(): void
    {
        $this->app->singleton(\Illuminate\Contracts\Debug\ExceptionHandler::class, WncmsExceptionHandler::class);
    }

    /**
     * Register additional service providers.
     */
    protected function registerServiceProviders(): void
    {
        $this->app->register(\Wncms\Providers\ViewServiceProvider::class);
        $this->app->register(\Mcamara\LaravelLocalization\LaravelLocalizationServiceProvider::class);
    }

    /**
     * Load system-level settings (fallback + database overrides).
     */
    protected function loadSystemSettings(): void
    {
        // Ensure media disk always exists
        $disks = config('filesystems.disks', []);
        if (!isset($disks['media'])) {
            $disks['media'] = [
                'driver' => 'local',
                'root' => public_path('media'),
                'url' => env('APP_URL') . '/media',
                'visibility' => 'public',
            ];
        }

        // Base filesystem overrides
        config(['filesystems.disks' => $disks]);
    }

    protected function loadTranslationSettings(): void
    {
        if (!function_exists('wncms_is_installed') || !wncms_is_installed()) {
            return;
        }

        if (!gss('enable_translation', true)) {
            return;
        }

        $locale = gss('app_locale', config('app.locale'));

        // override runtime config
        config([
            'app.locale' => $locale,
            'laravellocalization.hideDefaultLocaleInURL' => gss('hide_default_locale_in_url', config('laravellocalization.hideDefaultLocaleInURL', false)),
            'laravellocalization.useAcceptLanguageHeader' => gss('use_accept_language_header', config('laravellocalization.useAcceptLanguageHeader', false)),
        ]);

        wncms()->setDefaultLocale($locale);

        wncms()->setLocalesMapping(gss('use_locales_mapping', false) ? config('laravellocalization.localesMapping', []) : []);
    }

    /**
     * Setup shared view variables and composers.
     */
    protected function loadGlobalVariables(): void
    {
        View::share('wncms', wncms());

        if (function_exists('wncms_is_installed') && wncms_is_installed()) {
            View::share('website', wncms()->website()->get());

            View::composer('*', function ($view) {
                // Share errors with all views
                $view->with('errors', session()->get('errors', new \Illuminate\Support\ViewErrorBag()));

                if (Route::currentRouteName() && str_starts_with(Route::currentRouteName(), 'frontend.')) {
                    $view->with('user', auth()->user());
                }
            });
        }
    }

    /**
     * Define publishable resources.
     */
    protected function loadPublishFiles(): void
    {
        // Core assets
        $this->publishes([
            __DIR__ . '/../../resources/core-assets' => public_path('wncms'),
            __DIR__ . '/../../resources/stubs' => base_path('stubs'),
            __DIR__ . '/../../resources/views/errors' => resource_path('views/errors'),
            __DIR__ . '/../../resources/views/layouts/error.blade.php' => resource_path('views/layouts/error.blade.php'),
        ], 'wncms-core-assets');

        // Theme assets (assets only)
        $themesPath = __DIR__ . '/../../resources/themes';

        foreach (glob($themesPath . '/*', GLOB_ONLYDIR) as $themeDir) {
            $themeId = basename($themeDir);

            if (!is_dir($themeDir . '/assets')) {
                continue;
            }

            $this->publishes([
                $themeDir . '/assets' => public_path('themes/' . $themeId . '/assets'),
            ], 'wncms-theme-assets');
        }
    }

    /**
     * Dynamically register all Artisan commands from the Commands directory.
     */
    protected function loadCommands(): void
    {
        $commandsPath = __DIR__ . '/../Console/Commands';

        if (!is_dir($commandsPath)) {
            return;
        }

        $commandFiles = File::files($commandsPath);
        $commandClasses = [];

        foreach ($commandFiles as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            // Example: ImportDemoCommand.php → Wncms\Console\Commands\ImportDemoCommand
            $class = 'Wncms\\Console\\Commands\\' . $file->getFilenameWithoutExtension();

            if (class_exists($class)) {
                $commandClasses[] = $class;
            }
        }

        if (!empty($commandClasses)) {
            $this->commands($commandClasses);
        }
    }

    protected function registerModels(): void
    {
        // 1. Register all WNCMS core models
        foreach (glob(WNCMS_CORE_PATH . 'src/Models/*.php') as $file) {
            $class = 'Wncms\\Models\\' . basename($file, '.php');

            if (class_exists($class)) {
                wncms()->registerModel($class);
            }
        }

        // 2. Register all App\Models (user overrides)
        foreach (glob(app_path('Models') . '/*.php') as $file) {
            $class = 'App\\Models\\' . basename($file, '.php');

            if (class_exists($class)) {
                wncms()->registerModel($class);
            }
        }
    }
}
