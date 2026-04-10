<?php

namespace Wncms\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Wncms\Database\Seeders\DatabaseSeeder;
use Wncms\Models\User;
use Wncms\Models\Website;

abstract class TestCase extends BaseTestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('cache.default', 'array');
        $app['config']->set('cache.stores.array', [
            'driver' => 'array',
            'serialize' => false,
        ]);
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', __DIR__ . '/../database/testing.sqlite');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadPackageConfig();
        $this->setupAuthConfig();
        Config::set('wncms.testing_is_installed', true);

        $this->ensureSqliteDatabaseFileExists();
        $this->assertPreparedTestingDatabase();
        $this->ensureDefaultRolesExist();
        $this->ensureDefaultPermissionsExist();
        $this->ensureBaselineSeedData();

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

    protected function ensureDefaultRolesExist(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['superadmin', 'admin', 'manager', 'member', 'suspended'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }
    }

    protected function ensureDefaultPermissionsExist(): void
    {
        foreach ([
            'post_index',
            'post_create',
            'post_clone',
            'post_edit',
            'post_show',
            'post_delete',
            'post_bulk_sync_tags',
            'post_generate_demo_posts',
            'post_bulk_clone',
            'comment_create',
            'comment_edit',
            'comment_delete',
            'setting_index',
            'setting_edit',
            'website_index',
            'website_create',
            'website_edit',
            'website_show',
            'website_delete',
        ] as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }
    }

    protected function ensureBaselineSeedData(): void
    {
        if (User::count() === 0 || !DB::table('settings')->exists()) {
            $this->seed(DatabaseSeeder::class);
        }

        $admin = User::where('email', 'admin@demo.com')->first() ?: User::first();
        if (!$admin) {
            return;
        }

        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        if (!$admin->hasRole('superadmin')) {
            $admin->assignRole('superadmin');
        }

        $baselinePermissions = Permission::whereIn('name', [
            'post_index',
            'post_create',
            'post_clone',
            'post_edit',
            'post_show',
            'post_delete',
            'post_bulk_sync_tags',
            'post_generate_demo_posts',
            'post_bulk_clone',
            'comment_create',
            'comment_edit',
            'comment_delete',
            'setting_index',
            'setting_edit',
            'website_index',
            'website_create',
            'website_edit',
            'website_show',
            'website_delete',
        ])->get();

        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->syncPermissions($baselinePermissions);
        }

        $superadminRole = Role::where('name', 'superadmin')->first();
        if ($superadminRole) {
            $superadminRole->syncPermissions($baselinePermissions);
        }

        $admin->syncPermissions($baselinePermissions);

        $website = Website::first();
        if (!$website) {
            $website = Website::create([
                'user_id' => $admin->id,
                'domain' => 'localhost',
                'site_name' => 'Test Website',
                'theme' => 'default',
            ]);
        }

        if (!$admin->websites()->where('websites.id', $website->id)->exists()) {
            $admin->websites()->syncWithoutDetaching([$website->id]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    // Load package service providers
    protected function getPackageProviders($app)
    {
        return [
            \Laravel\Socialite\SocialiteServiceProvider::class,
            \Wncms\Tags\TagsServiceProvider::class,
            \Wncms\Providers\WncmsServiceProvider::class,
            \Wncms\Providers\EventServiceProvider::class,
            \Wncms\Providers\HelpersProvider::class,
            \Wncms\Providers\MailServiceProvider::class,
            \Wncms\Providers\ObserverServiceProvider::class,
            \Wncms\Providers\PluginServiceProvider::class,
            \Wncms\Providers\RouteServiceProvider::class,
            \Wncms\Providers\SettingsServiceProvider::class,
            \Mcamara\LaravelLocalization\LaravelLocalizationServiceProvider::class,
            \Wncms\Translatable\TranslatableServiceProvider::class,
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
