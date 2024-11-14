<?php

namespace Wncms\Providers;

use Exception;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Wncms\Exceptions\WncmsExceptionHandler;
use Illuminate\Support\Facades\File;

class WncmsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Replace the default exception handler with your custom one
        $this->app->singleton(\Illuminate\Contracts\Debug\ExceptionHandler::class, WncmsExceptionHandler::class);

        // Merge configurations
        $configFiles = [
            'activitylog',
            'app',
            'auth',
            'cache',
            'database',
            'debugbar',
            'filesystems',
            'installer',
            'laravellocalization',
            'logging',
            'mail',
            'permission',
            'queue',
            'services',
            'session',
            'translatable',
            'wncms-system-settings',
            'wncms-tags'
        ];

        foreach ($configFiles as $config) {
            $this->mergeConfigFrom(__DIR__ . "/../../config/{$config}.php", $config);
        }

        // Load the theme configurations
        $themeConfigs = ['default', 'starter'];
        foreach ($themeConfigs as $theme) {
            $this->mergeConfigFrom(__DIR__ . "/../../config/theme/{$theme}.php", "theme.{$theme}");
        }

        // Register other necessary bindings
        $this->app->register(\Mcamara\LaravelLocalization\LaravelLocalizationServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Middleware
        $this->app['router']->aliasMiddleware('localize', \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes::class);
        $this->app['router']->aliasMiddleware('localizationRedirect', \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter::class);
        $this->app['router']->aliasMiddleware('localeSessionRedirect', \Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect::class);
        $this->app['router']->aliasMiddleware('localeCookieRedirect', \Mcamara\LaravelLocalization\Middleware\LocaleCookieRedirect::class);
        $this->app['router']->aliasMiddleware('localeViewPath', \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath::class);
        $this->app['router']->aliasMiddleware('is_installed', \Wncms\Http\Middleware\IsInstalled::class);
        $this->app['router']->aliasMiddleware('has_website', \Wncms\Http\Middleware\HasWebsite::class);
        $this->app['router']->aliasMiddleware('full_page_cache', \Wncms\Http\Middleware\FullPageCache::class);

        // Routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'wncms');

        // Translation
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'wncms');

        // Migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Commands
        if ($this->app->runningInConsole()) {
            $this->loadCommands();
            $this->autoPublishAssets();
        }

        // Publish configurations and assets
        $this->publishes([
            __DIR__ . '/../../config/activitylog.php' => config_path('activitylog.php'),
            __DIR__ . '/../../config/app.php' => config_path('app.php'),
            __DIR__ . '/../../config/auth.php' => config_path('auth.php'),
            __DIR__ . '/../../config/cache.php' => config_path('cache.php'),
            __DIR__ . '/../../config/database.php' => config_path('database.php'),
            __DIR__ . '/../../config/debugbar.php' => config_path('debugbar.php'),
            __DIR__ . '/../../config/filesystems.php' => config_path('filesystems.php'),
            __DIR__ . '/../../config/installer.php' => config_path('installer.php'),
            __DIR__ . '/../../config/laravellocalization.php' => config_path('laravellocalization.php'),
            __DIR__ . '/../../config/logging.php' => config_path('logging.php'),
            __DIR__ . '/../../config/mail.php' => config_path('mail.php'),
            __DIR__ . '/../../config/permission.php' => config_path('permission.php'),
            __DIR__ . '/../../config/queue.php' => config_path('queue.php'),
            __DIR__ . '/../../config/services.php' => config_path('services.php'),
            __DIR__ . '/../../config/session.php' => config_path('session.php'),
            __DIR__ . '/../../config/translatable.php' => config_path('translatable.php'),
            __DIR__ . '/../../config/wncms-system-settings.php' => config_path('wncms-system-settings.php'),
            __DIR__ . '/../../config/wncms-tags.php' => config_path('wncms-tags.php'),
        ], 'wncms-system-config');

        // Publish theme config
        $this->publishes([
            __DIR__ . '/../../config/theme/default.php' => config_path('theme/default.php'),
            __DIR__ . '/../../config/theme/starter.php' => config_path('theme/starter.php'),
        ], 'wncms-theme-config');

        // Core assets
        $this->publishes([
            __DIR__ . '/../../resources/core-assets' => public_path('wncms'),
            __DIR__ . '/../../resources/stubs' => base_path('stubs'),
        ], 'wncms-core-assets');

        // Theme assets
        $this->publishes([
            __DIR__ . '/../../resources/theme-assets' => public_path('theme'),
            __DIR__ . '/../../resources/views/frontend' => resource_path('views/frontend'),
            __DIR__ . '/../../resources/views/errors' => resource_path('views/errors'),
            __DIR__ . '/../../resources/views/layouts/error.blade.php' => resource_path('views/layouts/error.blade.php'),
        ], 'wncms-theme-assets');

        try {
            // Force HTTPS if needed
            if (config('app.force_https') || gss('force_https') || request()->force_https) {
                \URL::forceScheme('https');
            }

            $wncms = wncms();
            view()->share('wncms', $wncms);

            if (wncms_is_installed()) {
                // Override config with database settings
                config([
                    'multi_website' => gss('multi_website', config('wncms.multi_website', false)),
                ]);

                $website = wncms()->website()->get();
                view()->share('website', $website);
            }

            Paginator::useBootstrap();
        } catch (Exception $e) {
            logger()->error($e);
        }
    }

    /**
     * Automatically publish assets and configurations.
     */
    private function autoPublishAssets(): void
    {
        $this->callSilent('vendor:publish', ['--tag' => 'wncms-system-config', '--force' => true]);
        $this->callSilent('vendor:publish', ['--tag' => 'wncms-theme-config', '--force' => true]);
        $this->callSilent('vendor:publish', ['--tag' => 'wncms-core-assets', '--force' => true]);
        $this->callSilent('vendor:publish', ['--tag' => 'wncms-theme-assets', '--force' => true]);
    }

    /**
     * Load all commands in the Console/Commands directory.
     */
    protected function loadCommands(): void
    {
        $commandFiles = File::allFiles(__DIR__ . '/../Console/Commands');

        $commands = [];
        foreach ($commandFiles as $commandFile) {
            $commandClass = 'Wncms\\Console\\Commands\\' . $commandFile->getFilenameWithoutExtension();

            if (class_exists($commandClass)) {
                $commands[] = $commandClass;
            }
        }

        // Register the commands with Artisan
        $this->commands($commands);
    }
}
