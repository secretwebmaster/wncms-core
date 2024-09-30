<?php

namespace Wncms\Providers;

use Exception;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Wncms\Exceptions\WncmsExceptionHandler;

class WncmsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Replace the default exception handler with your custom one
        $this->app->singleton(\Illuminate\Contracts\Debug\ExceptionHandler::class, WncmsExceptionHandler::class);

        // configs
        $this->mergeConfigFrom(__DIR__.'/../../config/activitylog.php', 'activitylog');
        $this->mergeConfigFrom(__DIR__.'/../../config/app.php', 'app');
        $this->mergeConfigFrom(__DIR__.'/../../config/auth.php', 'auth');
        $this->mergeConfigFrom(__DIR__.'/../../config/cache.php', 'cache');
        $this->mergeConfigFrom(__DIR__.'/../../config/database.php', 'database');
        $this->mergeConfigFrom(__DIR__.'/../../config/debugbar.php', 'debugbar');
        $this->mergeConfigFrom(__DIR__.'/../../config/filesystems.php', 'filesystems');
        $this->mergeConfigFrom(__DIR__.'/../../config/installer.php', 'installer');
        $this->mergeConfigFrom(__DIR__.'/../../config/laravellocalization.php', 'laravellocalization');
        $this->mergeConfigFrom(__DIR__.'/../../config/logging.php', 'logging');
        $this->mergeConfigFrom(__DIR__.'/../../config/mail.php', 'mail');
        $this->mergeConfigFrom(__DIR__.'/../../config/permission.php', 'permission');
        $this->mergeConfigFrom(__DIR__.'/../../config/queue.php', 'queue');
        $this->mergeConfigFrom(__DIR__.'/../../config/services.php', 'services');
        $this->mergeConfigFrom(__DIR__.'/../../config/session.php', 'session');
        $this->mergeConfigFrom(__DIR__.'/../../config/translatable.php', 'translatable');
        $this->mergeConfigFrom(__DIR__.'/../../config/wncms-system-settings.php', 'wncms-system-settings');
        $this->mergeConfigFrom(__DIR__.'/../../config/wncms-tags.php', 'wncms-tags');

        // Load the theme configurations
        $this->mergeConfigFrom(__DIR__.'/../../config/theme/default.php', 'theme.default');
        $this->mergeConfigFrom(__DIR__.'/../../config/theme/starter.php', 'theme.starter');

        $this->app->register(\Mcamara\LaravelLocalization\LaravelLocalizationServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // middleware
        $this->app['router']->aliasMiddleware('localize', \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes::class);
        $this->app['router']->aliasMiddleware('localizationRedirect', \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter::class);
        $this->app['router']->aliasMiddleware('localeSessionRedirect', \Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect::class);
        $this->app['router']->aliasMiddleware('localeCookieRedirect', \Mcamara\LaravelLocalization\Middleware\LocaleCookieRedirect::class);
        $this->app['router']->aliasMiddleware('localeViewPath', \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath::class);

        $this->app['router']->aliasMiddleware('is_installed', \Wncms\Http\Middleware\IsInstalled::class);
        $this->app['router']->aliasMiddleware('has_website', \Wncms\Http\Middleware\HasWebsite::class);
        $this->app['router']->aliasMiddleware('full_page_cache', \Wncms\Http\Middleware\FullPageCache::class);

        // routes
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/../../routes/api.php');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'wncms');

        // translation
        $this->loadTranslationsFrom(__DIR__.'/../../lang', 'wncms');

        // migrations
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        
        // publish
        // $this->publishes([
        //     __DIR__.'/../../config/activitylog.php' => config_path('activitylog.php'),
        //     __DIR__.'/../../config/app.php' => config_path('app.php'),
        //     __DIR__.'/../../config/auth.php' => config_path('auth.php'),
        //     __DIR__.'/../../config/cache.php' => config_path('cache.php'),
        //     __DIR__.'/../../config/database.php' => config_path('database.php'),
        //     __DIR__.'/../../config/debugbar.php' => config_path('debugbar.php'),
        //     __DIR__.'/../../config/filesystems.php' => config_path('filesystems.php'),
        //     __DIR__.'/../../config/installer.php' => config_path('installer.php'),
        //     __DIR__.'/../../config/laravellocalization.php' => config_path('laravellocalization.php'),
        //     __DIR__.'/../../config/logging.php' => config_path('logging.php'),
        //     __DIR__.'/../../config/mail.php' => config_path('mail.php'),
        //     __DIR__.'/../../config/permission.php' => config_path('permission.php'),
        //     __DIR__.'/../../config/queue.php' => config_path('queue.php'),
        //     __DIR__.'/../../config/services.php' => config_path('services.php'),
        //     __DIR__.'/../../config/session.php' => config_path('session.php'),
        //     __DIR__.'/../../config/translatable.php' => config_path('translatable.php'),
        //     __DIR__.'/../../config/wncms-system-settings.php' => config_path('wncms-system-settings.php'),
        //     __DIR__.'/../../config/wncms-tags.php' => config_path('wncms-tags.php'),

        //     // Publish theme configurations
        //     __DIR__.'/../../config/theme/default.php' => config_path('theme/default.php'),
        //     __DIR__.'/../../config/theme/starter.php' => config_path('theme/starter.php'),
        // ], 'config');

        // ! Commands
        // if ($this->app->runningInConsole()) {
        //     $this->commands([
        //         InstallCommand::class,
        //         NetworkCommand::class,
        //     ]);
        // }

        //! Asset
        // $this->publishes([
        //     __DIR__.'/../public' => public_path('vendor/courier'),
        // ], 'public');

        // !publishes
        // $this->publishes([
        //     __DIR__.'/../../config/courier.php' => config_path('courier.php'),
        // ]);

        // $this->publishesMigrations([
        //     __DIR__.'/../database/migrations' => database_path('migrations'),
        // ]);

        // $this->publishes([
        //     __DIR__.'/../lang' => $this->app->langPath('vendor/courier'),
        // ]);

        // $this->publishes([
        //     __DIR__.'/../resources/views' => resource_path('views/vendor/courier'),
        // ]);

        try{
            // info(request()->all());
            if(config('app.force_https') || gss('force_https') || request()->force_https){
                \URL::forceScheme('https');
            }

            $wncms = wncms();
            view()->share('wncms', $wncms);
            //檢查是否已安裝系統
            if (wncms_is_installed()) {
                $website = wncms()->website()->get();
                view()->share('website', $website);
            }else{
                // redirect to installation guide
            }
    
            // TODO: Allow to use theme paginator
            Paginator::useBootstrap();
        }catch(Exception $e){
            logger()->error($e);
        }

    }
}
