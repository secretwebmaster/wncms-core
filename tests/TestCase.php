<?php

namespace Wncms\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

abstract class TestCase extends BaseTestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('cache.default', 'array');
        $app['config']->set('cache.stores.array', [
            'driver' => 'array',
            'serialize' => false,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadPackageConfig();
        $this->setupAuthConfig();
        Config::set('wncms.testing_is_installed', true);

        $this->ensureSqliteDatabaseFileExists();
        $this->assertPreparedTestingDatabase();

        // Verify the database connection is sqlite
        $connection = DB::connection();
        if ($connection->getDriverName() !== 'sqlite') {
            throw new \Exception('Database is not configured for sqlite testing');
        }
    }

    protected function ensureSqliteDatabaseFileExists(): void
    {
        $databasePath = database_path('testing.sqlite');
        $databaseDirectory = dirname($databasePath);
        if (!is_dir($databaseDirectory)) {
            mkdir($databaseDirectory, 0777, true);
        }
        if (!file_exists($databasePath)) {
            touch($databasePath);
        }
    }

    protected function assertPreparedTestingDatabase(): void
    {
        $requiredTables = ['migrations', 'users', 'roles', 'permissions', 'tags', 'translations', 'settings'];
        foreach ($requiredTables as $table) {
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                throw new \RuntimeException(
                    "Prepared testing database is missing table '{$table}'. Run: composer run test:prepare-db"
                );
            }
        }
    }

    // Load package service providers
    protected function getPackageProviders($app)
    {
        return [
            \Wncms\Providers\WncmsServiceProvider::class,
            \Wncms\Providers\EventServiceProvider::class,
            \Wncms\Providers\HelpersProvider::class,
            \Wncms\Providers\MailServiceProvider::class,
            \Wncms\Providers\ObserverServiceProvider::class,
            \Wncms\Providers\PluginServiceProvider::class,
            \Wncms\Providers\RouteServiceProvider::class,
            \Wncms\Providers\SettingsServiceProvider::class,
            \Mcamara\LaravelLocalization\LaravelLocalizationServiceProvider::class,
        ];
    }

    /**
     * Load package alias
     * @param  \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            'localize' => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes::class,
            'localizationRedirect' => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter::class,
            'localeSessionRedirect' => \Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect::class,
            'localeCookieRedirect' => \Mcamara\LaravelLocalization\Middleware\LocaleCookieRedirect::class,
            'localeViewPath' => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath::class,

            'is_installed' => \Wncms\Http\Middleware\IsInstalled::class,
            'has_website' => \Wncms\Http\Middleware\HasWebsite::class,
            'full_page_cache' => \Wncms\Http\Middleware\FullPageCache::class,

            'LaravelLocalization' => \Mcamara\LaravelLocalization\Facades\LaravelLocalization::class,
        ];
    }

    protected function loadPackageConfig()
    {
        // Path to your package's config directory
        $configPath = __DIR__ . '/../config';

        // Get all config files
        $configFiles = scandir($configPath);

        foreach ($configFiles as $file) {
            // Ignore non-PHP files and the special directories
            if (is_file($configPath . '/' . $file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                // Load the configuration file
                $config = require $configPath . '/' . $file;

                // Merge the configuration into the existing config
                Config::set(pathinfo($file, PATHINFO_FILENAME), $config);
            }
        }
    }

    protected function setupAuthConfig(): void
    {
        Config::set('auth.defaults.guard', 'web');
        Config::set('auth.guards.web', [
            'driver' => 'session',
            'provider' => 'users',
        ]);
        Config::set('auth.providers.users', [
            'driver' => 'eloquent',
            'model' => \Wncms\Models\User::class,
        ]);
    }
}
