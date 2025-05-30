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
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class WncmsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register the facade
        $this->app->singleton('wncms', fn($app) => new \Wncms\Services\Wncms);
        $this->app->singleton('plan-manager', fn($app) => new \Wncms\Services\Managers\PlanManager);
        $this->app->singleton('order-manager', fn($app) => new \Wncms\Services\Managers\OrderManager);
        $this->app->singleton('macroable-models', fn($app) => new \Wncms\Services\MacroableModels\MacroableModels);

        AliasLoader::getInstance()->alias('Wncms', \Wncms\Facades\Wncms::class);
        AliasLoader::getInstance()->alias('PlanManger', \Wncms\Facades\PlanManager::class);
        AliasLoader::getInstance()->alias('OrderManager', \Wncms\Facades\OrderManager::class);


        // Replace the default exception handler with your custom one
        $this->app->singleton(\Illuminate\Contracts\Debug\ExceptionHandler::class, WncmsExceptionHandler::class);

        // configs
        $this->mergeConfigFrom(__DIR__ . '/../../config/activitylog.php', 'activitylog');
        $this->mergeConfigFrom(__DIR__ . '/../../config/app.php', 'app');
        $this->mergeConfigFrom(__DIR__ . '/../../config/auth.php', 'auth');
        $this->mergeConfigFrom(__DIR__ . '/../../config/cache.php', 'cache');
        $this->mergeConfigFrom(__DIR__ . '/../../config/database.php', 'database');
        $this->mergeConfigFrom(__DIR__ . '/../../config/debugbar.php', 'debugbar');
        $this->mergeConfigFrom(__DIR__ . '/../../config/filesystems.php', 'filesystems');
        $this->mergeConfigFrom(__DIR__ . '/../../config/installer.php', 'installer');
        $this->mergeConfigFrom(__DIR__ . '/../../config/laravellocalization.php', 'laravellocalization');
        $this->mergeConfigFrom(__DIR__ . '/../../config/logging.php', 'logging');
        $this->mergeConfigFrom(__DIR__ . '/../../config/mail.php', 'mail');
        $this->mergeConfigFrom(__DIR__ . '/../../config/permission.php', 'permission');
        $this->mergeConfigFrom(__DIR__ . '/../../config/queue.php', 'queue');
        $this->mergeConfigFrom(__DIR__ . '/../../config/services.php', 'services');
        $this->mergeConfigFrom(__DIR__ . '/../../config/session.php', 'session');
        $this->mergeConfigFrom(__DIR__ . '/../../config/translatable.php', 'translatable');
        $this->mergeConfigFrom(__DIR__ . '/../../config/wncms-system-settings.php', 'wncms-system-settings');
        $this->mergeConfigFrom(__DIR__ . '/../../config/wncms-tags.php', 'wncms-tags');
        $this->mergeConfigFrom(__DIR__ . '/../../config/wncms.php', 'wncms');
        
        // Load the theme configurations
        $this->mergeConfigFrom(__DIR__ . '/../../config/theme/default.php', 'theme.default');
        $this->mergeConfigFrom(__DIR__ . '/../../config/theme/starter.php', 'theme.starter');

        $this->app->register(\Mcamara\LaravelLocalization\LaravelLocalizationServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        config('app.debug') ? error_reporting(E_ALL) : error_reporting(0);

        // middleware
        $this->app['router']->aliasMiddleware('localize', \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes::class);
        $this->app['router']->aliasMiddleware('localizationRedirect', \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter::class);
        $this->app['router']->aliasMiddleware('localeSessionRedirect', \Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect::class);
        $this->app['router']->aliasMiddleware('localeCookieRedirect', \Mcamara\LaravelLocalization\Middleware\LocaleCookieRedirect::class);
        $this->app['router']->aliasMiddleware('localeViewPath', \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath::class);

        $this->app['router']->aliasMiddleware('is_installed', \Wncms\Http\Middleware\IsInstalled::class);
        $this->app['router']->aliasMiddleware('has_website', \Wncms\Http\Middleware\HasWebsite::class);
        $this->app['router']->aliasMiddleware('full_page_cache', \Wncms\Http\Middleware\FullPageCache::class);

        // Modify VerifyCsrfToken after it's resolved
        $this->app->resolving(VerifyCsrfToken::class, function ($csrfMiddleware) {
            $csrfMiddleware->except('panel/uploads/image');
            $csrfMiddleware->except('install/*');
        });
        
        // routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'wncms');

        // translation
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'wncms');

        // migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // commands
        if ($this->app->runningInConsole()) {
            $this->loadCommands();
            $this->publishFiles();
        }

        // init app
        try {
            // force https
            if (config('app.force_https') || gss('force_https') || request()->force_https) {
                \URL::forceScheme('https');
            }

            $wncms = wncms();
            view()->share('wncms', $wncms);

            //檢查是否已安裝系統
            if (wncms_is_installed()) {

                // override the config with the settings from the database
                config([
                    'multi_website' => gss('multi_website', config('wncms.multi_website', false)),
                    'filesystems.disks.public.url' => url('/storage'),
                ]);

                View::share('website', Wncms::website()->get());

                View::composer('*', function ($view) {
                    if (Route::currentRouteName() && str_starts_with(Route::currentRouteName(), 'frontend.')) {
                        $view->with('user', auth()->user());
                    }
                });
            } else {
                // redirect to installation guide
            }

            // dd(config('app.locale'));

            // TODO: Allow to use theme paginator
            Paginator::useBootstrap();
        } catch (Exception $e) {
            logger()->error($e);
        }
    }

    /**
     * Load all commands in the Console/Commands directory
     */
    protected function loadCommands()
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

    /**
     * Publish files
     */
    protected function publishFiles()
    {
        // publish system config
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
            // __DIR__ . '/../../config/wncms-system-settings.php' => config_path('wncms-system-settings.php'),
            __DIR__ . '/../../config/wncms-tags.php' => config_path('wncms-tags.php'),
        ], 'wncms-system-config');

        // publish theme config
        $this->publishes([
            __DIR__ . '/../../config/theme/default.php' => config_path('theme/default.php'),
            __DIR__ . '/../../config/theme/starter.php' => config_path('theme/starter.php'),
        ], 'wncms-theme-config');

        // core assets
        $this->publishes([
            __DIR__ . '/../../resources/core-assets' => public_path('wncms'),
            __DIR__ . '/../../resources/stubs' => base_path('stubs'),
        ], 'wncms-core-assets');

        // theme assets
        $this->publishes([
            __DIR__ . '/../../resources/theme-assets' => public_path('theme'),
            __DIR__ . '/../../resources/views/frontend' => resource_path('views/frontend'),
            __DIR__ . '/../../resources/views/errors' => resource_path('views/errors'),
            __DIR__ . '/../../resources/views/layouts/error.blade.php' => resource_path('views/layouts/error.blade.php'),
        ], 'wncms-theme-assets');

        // info('Wncms assets published');
    }
}
