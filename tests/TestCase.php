<?php

namespace Wncms\Tests;

use Wncms\Models\User;
use Wncms\Models\Website;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Wncms\Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Config;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadPackageConfig();

        // Verify the database connection is in-memory
        $connection = DB::connection();
        if ($connection->getDriverName() !== 'sqlite' || $connection->getDatabaseName() !== ':memory:') {
            throw new \Exception('Database is not configured for in-memory testing');
        }

        // load migrations and seed the database
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Run the migrations
        $this->artisan('migrate', ['--database' => 'sqlite'])->run();

        // run the seeder
        $this->seed(DatabaseSeeder::class);

        $this->installCMS();


    }

    // Load package service providers
    protected function getPackageProviders($app)
    {
        return [
            \Wncms\Providers\AppServiceProvider::class,
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

    protected function installCMS()
    {
        // create admin user

        // create roles


        // create permissions
        $user = User::first();

        // create a website
        $user->websites()->firstOrCreate(
            ['site_name' => 'Test Website'],
            ['domain' => request()->getHost()]
        );
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
}
