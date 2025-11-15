<?php

namespace Wncms\Providers;

use Exception;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Wncms\Exceptions\WncmsExceptionHandler;
use Illuminate\Support\Facades\File;
use Illuminate\Foundation\AliasLoader;
use Wncms\Facades\Wncms;
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
        // Facades
        $this->app->singleton('wncms', fn($app) => new \Wncms\Services\Wncms);
        $this->app->singleton('macroable-models', fn($app) => new \Wncms\Services\MacroableModels\MacroableModels);
        $this->app->singleton(\Wncms\Services\Managers\TagManager::class, fn($app) => new \Wncms\Services\Managers\TagManager);

        // Alias
        AliasLoader::getInstance()->alias('Wncms', \Wncms\Facades\Wncms::class);

        // Custom exception handler
        $this->app->singleton(\Illuminate\Contracts\Debug\ExceptionHandler::class, WncmsExceptionHandler::class);

        // Package configs
        $this->mergeConfigFrom(__DIR__ . '/../../config/installer.php', 'installer');
        $this->mergeConfigFrom(__DIR__ . '/../../config/laravellocalization.php', 'laravellocalization');
        $this->mergeConfigFrom(__DIR__ . '/../../config/media-library.php', 'media-library');
        $this->mergeConfigFrom(__DIR__ . '/../../config/translatable.php', 'translatable');
        $this->mergeConfigFrom(__DIR__ . '/../../config/wncms-system-settings.php', 'wncms-system-settings');
        $this->mergeConfigFrom(__DIR__ . '/../../config/wncms-tags.php', 'wncms-tags');
        $this->mergeConfigFrom(__DIR__ . '/../../config/permission.php', 'wncms');
        $this->mergeConfigFrom(__DIR__ . '/../../config/wncms.php', 'wncms');
        $this->mergeConfigFrom(__DIR__ . '/../../config/theme/default.php', 'theme.default');
        $this->mergeConfigFrom(__DIR__ . '/../../config/theme/starter.php', 'theme.starter');

        // Ensure fallback and DB-based system settings
        $this->loadSystemSettings();

        // Third-party providers
        $this->app->register(\Mcamara\LaravelLocalization\LaravelLocalizationServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        define('WNCMS_CORE_PATH', base_path('vendor/secretwebmaster/wncms-core/'));
        config('app.debug') ? error_reporting(E_ALL) : error_reporting(0);

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

        // Database-based settings (only after installation)
        if (function_exists('wncms_is_installed') && wncms_is_installed()) {
            config([
                'multi_website' => gss('multi_website', config('wncms.multi_website', false)),
                'filesystems.disks.public.url' => url('/storage'),
            ]);

            // Load app locale from database and override Laravel's locale
            if ($locale = gss('app_locale')) {
                app()->setLocale($locale);
                config(['app.locale' => $locale]);
            }
        }
    }

    /**
     * Setup shared view variables and composers.
     */
    protected function loadGlobalVariables(): void
    {
        $wncms = wncms();
        View::share('wncms', $wncms);

        if (function_exists('wncms_is_installed') && wncms_is_installed()) {
            View::share('website', $wncms->website()->get());

            View::composer('*', function ($view) {
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
        ], 'wncms-core-assets');

        // Theme assets
        $this->publishes([
            __DIR__ . '/../../resources/theme-assets' => public_path('themes'),
            // __DIR__ . '/../../resources/views/frontend' => resource_path('views/frontend'),
            __DIR__ . '/../../resources/views/errors' => resource_path('views/errors'),
            __DIR__ . '/../../resources/views/layouts/error.blade.php' => resource_path('views/layouts/error.blade.php'),
        ], 'wncms-theme-assets');
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

    public function registerModelTagMeta()
    {
        $models = [
            // 'model_key' => ModelClass::class,
            'post' => \Wncms\Models\Post::class,
            'page' => \Wncms\Models\Page::class,
        ];
        wncms()->tag()->registerPackageModels($models);
    }
}
