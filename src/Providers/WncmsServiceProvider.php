<?php

namespace Wncms\Providers;

use Exception;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Wncms\Exceptions\WncmsExceptionHandler;
use Illuminate\Support\Facades\File;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Str;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Wncms\Api\V2\ApiContractRegistry;
use Wncms\Api\V2\ApiContractValidator;
use Wncms\Api\V2\ApiResponseFactory;
use Wncms\Api\V2\ApiV2ResponseFinalizer;
use Wncms\Api\V2\Contracts\AtomicOperationRepository;
use Wncms\Api\V2\Contracts\IdempotencyStore;
use Wncms\Api\V2\Contracts\OperationRepository;
use Wncms\Api\V2\IdempotencyService;
use Wncms\Api\V2\ModelPermissionResolver;
use Wncms\Api\V2\OperationService;
use Wncms\Api\V2\OperationValidator;
use Wncms\Api\V2\OpenApiDocumentBuilder;
use Wncms\Api\V2\ReplayResponseTrust;
use Wncms\Api\V2\Repositories\CacheIdempotencyStore;
use Wncms\Api\V2\Repositories\CacheOperationRepository;
use Wncms\Auth\Api\V2\AccessTokenService;
use Wncms\Auth\Api\V2\AuthSecurityConfig;
use Wncms\Auth\Api\V2\CredentialParser;
use Wncms\Auth\Api\V2\CsrfTokenService;
use Wncms\Auth\Api\V2\DummyPasswordHasher;
use Wncms\Auth\Api\V2\LoginThrottleService;
use Wncms\Auth\Api\V2\OriginPolicy;
use Wncms\Auth\Api\V2\RefreshTokenService;
use Wncms\Auth\Api\V2\RefreshTokenConsumer;
use Wncms\Auth\Api\V2\SessionService;
use Wncms\Auth\Api\V2\TokenHasher;
use Wncms\Auth\Api\V2\WebsiteScopeGuard;
use Wncms\Http\Middleware\ApiV2TokenAuth;
use Wncms\Http\Middleware\EnforceApiV2RefreshTransport;
use Wncms\Http\Middleware\RequireApiV2Ability;
use Wncms\Http\Middleware\RequireApiV2ModelPermission;
use Wncms\Http\Middleware\RequireApiV2Permission;
use Wncms\Http\Middleware\ResolveApiV2WebsiteScope;
use Wncms\Http\Middleware\ValidateApiV2RefreshCsrf;
use Wncms\Http\Middleware\ValidateApiV2RefreshOrigin;

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

        $this->registerApiV2ContractServices();

        // Laravel 13 compatibility (opt-in): allow cached object unserialization when needed.
        $this->loadCacheCompatibilitySettings();

        // Register service providers
        $this->registerServiceProviders();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (!defined('WNCMS_CORE_PATH')) {
            define('WNCMS_CORE_PATH', base_path('vendor/secretwebmaster/wncms-core/'));
        }
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
        $router->aliasMiddleware('frontend_auth', \Wncms\Http\Middleware\FrontendAuth::class);
        $router->aliasMiddleware('api_v2_request_id', \Wncms\Http\Middleware\AssignApiV2RequestId::class);
        $router->aliasMiddleware('api_v2_whitelist', \Wncms\Http\Middleware\ApiV2Whitelist::class);
        $router->aliasMiddleware('api_v2_has_website', \Wncms\Http\Middleware\ApiV2HasWebsite::class);
        $router->aliasMiddleware('api_v2_token_auth', ApiV2TokenAuth::class);
        $router->aliasMiddleware('api_v2_ability', RequireApiV2Ability::class);
        $router->aliasMiddleware('api_v2_permission', RequireApiV2Permission::class);
        $router->aliasMiddleware('api_v2_model_permission', RequireApiV2ModelPermission::class);
        $router->aliasMiddleware('api_v2_website_scope', ResolveApiV2WebsiteScope::class);
        $router->aliasMiddleware('api_v2_idempotency', \Wncms\Http\Middleware\EnforceApiV2Idempotency::class);
        $router->aliasMiddleware('api_v2_refresh_transport', EnforceApiV2RefreshTransport::class);
        $router->aliasMiddleware('api_v2_refresh_origin', ValidateApiV2RefreshOrigin::class);
        $router->aliasMiddleware('api_v2_refresh_csrf', ValidateApiV2RefreshCsrf::class);

        $kernel = $this->app->make(\Illuminate\Contracts\Http\Kernel::class);
        $kernel->prependToMiddlewarePriority(\Wncms\Http\Middleware\AssignApiV2RequestId::class);
        foreach ([
            EnforceApiV2RefreshTransport::class,
            ValidateApiV2RefreshOrigin::class,
            ValidateApiV2RefreshCsrf::class,
            ApiV2TokenAuth::class,
            RequireApiV2Ability::class,
            RequireApiV2Permission::class,
            RequireApiV2ModelPermission::class,
            ResolveApiV2WebsiteScope::class,
        ] as $middleware) {
            $kernel->appendToMiddlewarePriority($middleware);
        }

        // Exclude paths from CSRF check
        $this->app->resolving(PreventRequestForgery::class, function ($csrf) {
            $csrf->except('panel/uploads/image');
            $csrf->except('install/*');
        });

        // Core resources
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        // API routes are grouped and loaded once by RouteServiceProvider.
        $this->loadMcpRoutes();
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'wncms');
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'wncms');
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->registerModels();

        // Publish mappings must be available for both CLI and wizard-triggered Artisan::call().
        $this->loadPublishFiles();

        // Console-only command registration.
        if ($this->app->runningInConsole()) {
            $this->loadCommands();
        } else {
            $this->loadHttpCallableCommands();
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
            'wncms-api-v2',
            'wncms-backend-api-v2',
            'wncms-tags',
            'permission',
            'wncms',
        ];

        foreach ($configs as $config) {
            $this->mergeConfigFrom(__DIR__ . "/../../config/{$config}.php", $config);
        }
    }

    /**
     * Register API v2 contract services.
     *
     * @return void
     */
    protected function registerApiV2ContractServices(): void
    {
        $replayResponseTrust = ReplayResponseTrust::create();

        $this->app->bind(AuthSecurityConfig::class, static fn () => AuthSecurityConfig::fromRuntime());
        $this->app->singleton(TokenHasher::class);
        $this->app->singleton(CredentialParser::class);
        $this->app->bind(OriginPolicy::class);
        $this->app->singleton(CsrfTokenService::class);
        $this->app->singleton(DummyPasswordHasher::class);
        $this->app->singleton(AccessTokenService::class);
        $this->app->singleton(RefreshTokenService::class);
        $this->app->singleton(RefreshTokenConsumer::class);
        $this->app->singleton(SessionService::class);
        $this->app->singleton(LoginThrottleService::class);
        $this->app->singleton(WebsiteScopeGuard::class);
        $this->app->singleton(ModelPermissionResolver::class);

        $this->app->singleton(ApiV2ResponseFinalizer::class, function ($app) use ($replayResponseTrust) {
            return new ApiV2ResponseFinalizer(
                $app->make(ApiResponseFactory::class),
                $replayResponseTrust
            );
        });

        $this->app->singleton(IdempotencyStore::class, function ($app) {
            return new CacheIdempotencyStore(
                $app->make(CacheFactory::class),
                config('wncms-api-v2.idempotency.store'),
                $app->environment('production'),
                config('wncms-api-v2.idempotency.allowed_shared_store_classes')
            );
        });

        $this->app->bind(IdempotencyService::class, function ($app) use ($replayResponseTrust) {
            return new IdempotencyService(
                $app->make(IdempotencyStore::class),
                $app->make(ApiResponseFactory::class),
                $app->make(ApiV2ResponseFinalizer::class),
                $replayResponseTrust
            );
        });

        $this->app->singleton(OperationValidator::class);

        $this->app->singleton(AtomicOperationRepository::class, function ($app) {
            return new CacheOperationRepository(
                $app->make(CacheFactory::class),
                config('wncms-api-v2.operations.store'),
                $app->environment('production'),
                config('wncms-api-v2.operations.allowed_shared_store_classes'),
                (int) config('wncms-api-v2.operations.lock_seconds', 10),
                $app->make(OperationValidator::class)
            );
        });
        $this->app->alias(AtomicOperationRepository::class, OperationRepository::class);

        $this->app->bind(OperationService::class, function ($app) {
            return new OperationService(
                $app->make(AtomicOperationRepository::class),
                (int) config('wncms-api-v2.operations.ttl_seconds', 86400),
                $app->make(OperationValidator::class)
            );
        });

        $this->app->singleton(ApiContractRegistry::class, function ($app) {
            $registry = new ApiContractRegistry;

            foreach (config('wncms-api-v2.providers', []) as $providerClass) {
                $app->make($providerClass)->register($registry);
            }

            return $registry;
        });

        $this->app->bind(ApiContractValidator::class, function ($app) {
            return new ApiContractValidator(
                $app->make(ApiContractRegistry::class),
                $app['router']->getRoutes(),
                $app->make(OpenApiDocumentBuilder::class)->build(),
                (array) config('wncms-api-v2.contract.excluded_route_names', [])
            );
        });
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
        $this->app->register(\Laravel\Mcp\Server\McpServiceProvider::class);
        $this->app->register(\Mcamara\LaravelLocalization\LaravelLocalizationServiceProvider::class);
        $this->app->register(\Wncms\Providers\ViewServiceProvider::class);
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

        $this->loadSessionSettings();

        $this->loadMutationAuditSettings();

        $this->loadAuthSecuritySettings();

        // Runtime model website mode override from system settings
        $this->loadModelWebsiteModeSettings();
    }

    /**
     * Load the global mutation audit setting into runtime config.
     *
     * @return void
     */
    protected function loadMutationAuditSettings(): void
    {
        config([
            'wncms.mutation_audit.enabled' => (bool) gss('enable_mutation_audit', false),
        ]);
    }

    /**
     * Load validated authentication security settings into runtime configuration.
     *
     * @return void
     */
    protected function loadAuthSecuritySettings(): void
    {
        config([
            'wncms.auth_security' => AuthSecurityConfig::fromRuntime()->toArray(),
        ]);
    }

    protected function loadCacheCompatibilitySettings(): void
    {
        $compatSetting = config('wncms.cache.serializable_classes_compat', null);
        if ($compatSetting === false) {
            return;
        }

        $current = config('cache.serializable_classes');
        if ($current === true || is_array($current)) {
            return;
        }

        $configuredAllowList = config('wncms.cache.serializable_classes', []);
        if (is_array($configuredAllowList) && !empty($configuredAllowList)) {
            config(['cache.serializable_classes' => $configuredAllowList]);
            return;
        }

        config(['cache.serializable_classes' => true]);
    }

    protected function loadSessionSettings(): void
    {
        $fallbackLifetime = (int) config('session.lifetime', 120);
        $configuredLifetime = gss('session_lifetime', $fallbackLifetime);

        if (!is_numeric($configuredLifetime)) {
            config(['session.lifetime' => $fallbackLifetime]);
            return;
        }

        $resolvedLifetime = (int) $configuredLifetime;

        config([
            'session.lifetime' => $resolvedLifetime > 0 ? $resolvedLifetime : $fallbackLifetime,
        ]);
    }

    protected function loadModelWebsiteModeSettings(): void
    {
        $resolvedModes = [];

        foreach ((array) config('wncms.model_website_modes', []) as $modelKey => $mode) {
            $normalizedKey = Str::snake(Str::singular($modelKey));
            if (in_array($mode, ['global', 'single', 'multi'], true)) {
                $resolvedModes[$normalizedKey] = $mode;
            }
        }

        foreach ((array) config('wncms.models', []) as $modelKey => $configData) {
            $normalizedKey = Str::snake(Str::singular($modelKey));
            $mode = $configData['website_mode'] ?? null;
            if (in_array($mode, ['global', 'single', 'multi'], true)) {
                $resolvedModes[$normalizedKey] = $mode;
            }
        }

        if (function_exists('wncms_is_installed') && wncms_is_installed()) {
            $raw = gss('model_website_modes', '{}');
            $overrides = json_decode((string) $raw, true);
            if (is_array($overrides)) {
                foreach ($overrides as $modelKey => $mode) {
                    $normalizedKey = Str::snake(Str::singular($modelKey));
                    if (in_array($mode, ['global', 'single', 'multi'], true)) {
                        $resolvedModes[$normalizedKey] = $mode;
                    }
                }
            }
        }

        $models = (array) config('wncms.models', []);

        foreach ($resolvedModes as $modelKey => $mode) {
            if (isset($models[$modelKey])) {
                $models[$modelKey]['website_mode'] = $mode;
                continue;
            }

            try {
                $modelClass = wncms()->getModelClass($modelKey);
                if (class_exists($modelClass)) {
                    $models[$modelKey] = [
                        'class' => $modelClass,
                        'website_mode' => $mode,
                    ];
                }
            } catch (\Throwable $e) {
            }
        }

        config(['wncms.models' => $models]);
    }

    protected function loadTranslationSettings(): void
    {
        if (!function_exists('wncms_is_installed') || !wncms_is_installed()) {
            return;
        }

        // Ensure translator is bound before attempting to use translation features
        if (!$this->app->bound('translator')) {
            return;
        }

        if (!gss('enable_translation', true)) {
            return;
        }

        $baseSupportedLocales = (array) config('laravellocalization.supportedLocales', []);
        $configuredSupportedLocaleKeys = $this->parseLocaleSettingList(gss('supported_locales', ''));
        $resolvedSupportedLocales = $baseSupportedLocales;

        if (!empty($configuredSupportedLocaleKeys)) {
            $resolvedSupportedLocales = [];
            foreach ($configuredSupportedLocaleKeys as $localeKey) {
                if (isset($baseSupportedLocales[$localeKey])) {
                    $resolvedSupportedLocales[$localeKey] = $baseSupportedLocales[$localeKey];
                }
            }

            if (empty($resolvedSupportedLocales)) {
                $resolvedSupportedLocales = $baseSupportedLocales;
            }
        }

        $locale = gss('app_locale', config('app.locale'));
        $resolvedLocale = isset($resolvedSupportedLocales[$locale])
            ? $locale
            : (array_key_first($resolvedSupportedLocales) ?: config('app.locale', 'en'));

        $configuredLocalesOrder = $this->parseLocaleSettingList(gss('locales_order', ''));
        $resolvedLocalesOrder = [];
        if (!empty($configuredLocalesOrder)) {
            foreach ($configuredLocalesOrder as $localeKey) {
                if (isset($resolvedSupportedLocales[$localeKey])) {
                    $resolvedLocalesOrder[] = $localeKey;
                }
            }
        }

        $baseLocalesMapping = (array) config('laravellocalization.localesMapping', []);
        $resolvedLocalesMapping = [];
        foreach ($baseLocalesMapping as $localeKey => $alias) {
            if (isset($resolvedSupportedLocales[$localeKey])) {
                $resolvedLocalesMapping[$localeKey] = $alias;
            }
        }

        // override runtime config
        config([
            'app.locale' => $resolvedLocale,
            'laravellocalization.supportedLocales' => $resolvedSupportedLocales,
            'laravellocalization.localesOrder' => $resolvedLocalesOrder,
            'laravellocalization.hideDefaultLocaleInURL' => gss('hide_default_locale_in_url', config('laravellocalization.hideDefaultLocaleInURL', false)),
            'laravellocalization.useAcceptLanguageHeader' => gss('use_accept_language_header', config('laravellocalization.useAcceptLanguageHeader', false)),
            'laravellocalization.localesMapping' => gss('use_locales_mapping', false) ? $resolvedLocalesMapping : [],
        ]);

        // laravel-localization caches supported locales internally, so update the singleton too.
        LaravelLocalization::setSupportedLocales($resolvedSupportedLocales);

        wncms()->setDefaultLocale($resolvedLocale);

        wncms()->setLocalesMapping(gss('use_locales_mapping', false) ? $resolvedLocalesMapping : []);
    }

    protected function parseLocaleSettingList(mixed $rawValue): array
    {
        if (is_array($rawValue)) {
            $candidates = $rawValue;
        } else {
            $value = trim((string) $rawValue);

            if ($value === '') {
                return [];
            }

            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $candidates = $decoded;
            } else {
                $candidates = explode(',', $value);
            }
        }

        return collect($candidates)
            ->map(fn($item) => trim((string) $item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Setup shared view variables and composers.
     *
     * @return void
     */
    protected function loadGlobalVariables(): void
    {
        View::share('wncms', wncms());
        View::share('website', null);

        if (function_exists('wncms_is_installed') && wncms_is_installed()) {
            View::share('website', wncms()->website()->get());

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
            __DIR__ . '/../../resources/views/errors' => resource_path('views/errors'),
            __DIR__ . '/../../resources/views/layouts/error.blade.php' => resource_path('views/layouts/error.blade.php'),
        ], 'wncms-core-assets');

        // Dedicated stub publish tag for updates/installers.
        $this->publishes([
            __DIR__ . '/../../resources/stubs' => base_path('stubs'),
        ], 'wncms-stubs');

        // Agent files (AGENTS.md + skills) for host project root.
        $this->publishes([
            __DIR__ . '/../../resources/agent-files/AGENTS.md' => base_path('AGENTS.md'),
            __DIR__ . '/../../resources/agent-files/.github/skills' => base_path('.github/skills'),
        ], 'wncms-agent-files');

        // Theme assets (assets only)
        $themesPath = __DIR__ . '/../../resources/themes';

        foreach (glob($themesPath . '/*', GLOB_ONLYDIR) as $themeDir) {
            $themeId = basename($themeDir);

            if (!is_dir($themeDir . '/assets')) {
                continue;
            }

            $this->publishes([
                $themeDir . '/assets' => public_path('themes/' . $themeId . '/assets'),
            ], 'wncms-default-assets');
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
                $reflection = new \ReflectionClass($class);

                if ($reflection->isInstantiable()) {
                    $commandClasses[] = $class;
                }
            }
        }

        if (!empty($commandClasses)) {
            $this->commands($commandClasses);
        }
    }

    /**
     * Register commands that may be invoked from HTTP via Artisan::call().
     */
    protected function loadHttpCallableCommands(): void
    {
        $commandClasses = [
            \Wncms\Console\Commands\Update::class,
            \Wncms\Console\Commands\UpdateWebsite::class,
        ];

        $commandClasses = array_values(array_filter($commandClasses, static fn($class) => class_exists($class)));

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

    /**
     * Load the opt-in local MCP server registration.
     *
     * @return void
     */
    protected function loadMcpRoutes(): void
    {
        if (! (bool) config('wncms.mcp.enabled', false)) {
            return;
        }

        require __DIR__.'/../../routes/ai.php';
    }
}
