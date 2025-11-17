<?php

namespace Wncms\Services\Installer;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Exception;
use Str;

class InstallerManager
{
    /**
     * Normalize and validate environment input.
     * Ensures all values are safe, non-empty (when required),
     * and boolean values become '1' or '0'.
     */
    public function normalizeInput(array $input): array
    {
        return [

            'database_connection' => $input['database_connection'] ?? 'mysql',
            'database_hostname' => $input['database_hostname'] ?? '127.0.0.1',
            'database_port' => is_numeric($input['database_port'] ?? null) ? $input['database_port'] : '3306',
            'database_name' => $input['database_name'] ?? 'wncms',
            'database_username' => $input['database_username'] ?? 'root',
            'database_password' => $input['database_password'] ?? '',

            'app_name' => $input['app_name'] ?? 'WNCMS',
            'app_url' => $input['app_url'] ?? 'http://localhost',
            'app_locale' => $input['app_locale'] ?? 'zh_TW',
            'app_env' => $input['app_env'] ?? 'production',

            'app_debug' => filter_var($input['app_debug'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false',
            'app_log_level' => $input['app_log_level'] ?? 'daily',

            'broadcast_driver' => $input['broadcast_driver'] ?? 'log',
            'cache_store' => $input['cache_store'] ?? 'file',
            'session_driver' => $input['session_driver'] ?? 'file',
            'queue_connection' => $input['queue_connection'] ?? 'sync',

            'redis_host' => $input['redis_host'] ?? '127.0.0.1',
            'redis_password' => $input['redis_password'] ?? '',
            'redis_port' => is_numeric($input['redis_port'] ?? null) ? $input['redis_port'] : '6379',

            'mail_driver' => $input['mail_driver'] ?? 'smtp',
            'mail_host' => $input['mail_host'] ?? 'localhost',
            'mail_port' => is_numeric($input['mail_port'] ?? null) ? $input['mail_port'] : '587',
            'mail_username' => $input['mail_username'] ?? '',
            'mail_password' => $input['mail_password'] ?? '',
            'mail_encryption' => $input['mail_encryption'] ?? 'tls',

            'pusher_app_id' => $input['pusher_app_id'] ?? '',
            'pusher_app_key' => $input['pusher_app_key'] ?? '',
            'pusher_app_secret' => $input['pusher_app_secret'] ?? '',

            'multi_website' => filter_var($input['multi_website'] ?? false, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
            'force_https' => filter_var($input['force_https'] ?? false, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',

            'site_name' => $input['site_name'] ?? 'WNCMS Site',
            'domain' => $input['domain'] ?? 'localhost',
            'theme' => $input['theme'] ?? 'default',
        ];
    }


    /**
     * Validate database connection using provided input.
     */
    public function checkDatabaseConnection(array $input): bool
    {
        $connection = $input['database_connection'];

        $settings = config("database.connections.$connection");

        config(['database.default' => $connection]);
        config([
            "database.connections.{$connection}" => array_merge($settings, [
                'driver' => $connection,
                'host' => $input['database_hostname'],
                'port' => $input['database_port'],
                'database' => $input['database_name'],
                'username' => $input['database_username'],
                'password' => $input['database_password'],
            ])
        ]);

        DB::purge();

        try {
            DB::connection()->getPdo();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Write the .env file with installer input data.
     */
    public function writeEnvFile(array $input): bool
    {
        $envFileData =
            'APP_NAME=\'' . ($input['app_name'] ?? '') . "'\n" .
            'APP_ENV=' . ($input['app_env'] ?? '') . "\n" .
            'APP_KEY=' . 'base64:' . base64_encode(Str::random(32)) . "\n" .
            'APP_DEBUG=' . ($input['app_debug'] ?? '') . "\n" .
            'DEBUGBAR_ENABLED=false' . "\n" .
            'CSS_DEBUG=false' . "\n" .
            'JS_DEBUG=false' . "\n" .
            'APP_VERSION=' . config('installer.version') . "\n" .
            'CUSTOM_VERSION=1' . "\n" .
            'APP_LOG_LEVEL=' . ($input['app_log_level'] ?? '') . "\n" .
            'APP_URL=' . ($input['app_url'] ?? '') . "\n\n" .

            'APP_MAINTENANCE_DRIVER=file' . "\n\n" .
            'BCRYPT_ROUNDS=12' . "\n\n" .

            'DB_CONNECTION=' . ($input['database_connection'] ?? '') . "\n" .
            'DB_HOST=' . ($input['database_hostname'] ?? '') . "\n" .
            'DB_PORT=' . ($input['database_port'] ?? '') . "\n" .
            'DB_DATABASE=' . ($input['database_name'] ?? '') . "\n" .
            'DB_USERNAME=' . ($input['database_username'] ?? '') . "\n" .
            'DB_PASSWORD=' . ($input['database_password'] ?? '') . "\n\n" .

            'BROADCAST_DRIVER=' . ($input['broadcast_driver'] ?? '') . "\n" .
            'CACHE_STORE=' . ($input['cache_store'] ?? '') . "\n" .

            'SESSION_DRIVER=' . ($input['session_driver'] ?? '') . "\n" .
            'QUEUE_CONNECTION=' . ($input['queue_connection'] ?? '') . "\n\n" .

            'REDIS_HOST=' . ($input['redis_host'] ?? '') . "\n" .
            'REDIS_PASSWORD=' . ($input['redis_password'] ?? '') . "\n" .
            'REDIS_PORT=' . ($input['redis_port'] ?? '') . "\n\n" .

            'MAIL_MAILER=' . ($input['mail_driver'] ?? '') . "\n" .
            'MAIL_HOST=' . ($input['mail_host'] ?? '') . "\n" .
            'MAIL_PORT=' . ($input['mail_port'] ?? '') . "\n" .
            'MAIL_USERNAME=' . ($input['mail_username'] ?? '') . "\n" .
            'MAIL_PASSWORD=' . ($input['mail_password'] ?? '') . "\n" .
            'MAIL_ENCRYPTION=' . ($input['mail_encryption'] ?? '') . "\n\n" .

            'PUSHER_APP_ID=' . ($input['pusher_app_id'] ?? '') . "\n" .
            'PUSHER_APP_KEY=' . ($input['pusher_app_key'] ?? '') . "\n" .
            'PUSHER_APP_SECRET=' . ($input['pusher_app_secret'] ?? '');

        try {
            file_put_contents(base_path('.env'), $envFileData);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Generate the APP_KEY (same as controller).
     */
    public function generateAppKey(): void
    {
        Artisan::call('key:generate', ['--force' => true]);
        info('generated key');
    }

    /**
     * Run SQL import if exists, otherwise migrate:fresh --seed.
     */
    public function runDatabaseSetup(): void
    {
        $sqlPath = base_path('vendor/secretwebmaster/wncms-core/resources/installer/wncms.sql');

        if (file_exists($sqlPath)) {
            try {
                info('Try to import SQL dump');
                DB::unprepared(file_get_contents($sqlPath));
                info('Imported SQL dump instead of running migrations.');

                Artisan::call('db:seed', [
                    '--force' => true,
                    '--class' => \Wncms\Database\Seeders\DatabaseSeeder::class,
                ]);
                info('Seeded database after schema import.');
                return;
            } catch (\Throwable $e) {
                info('SQL import failed: ' . $e->getMessage());
                info('Fallback to migrate:fresh due to SQL import failure.');
            }
        } else {
            info('SQL dump not found, running migrations + seeders.');
        }

        // fallback
        Artisan::call('migrate:fresh', [
            '--seed' => true,
            '--force' => true,
            '--seeder' => \Wncms\Database\Seeders\DatabaseSeeder::class,
        ]);

        info('Completed fallback migrations + seeders.');
    }

    /**
     * Publish required WNCMS assets.
     */
    public function publishAssets(): void
    {
        Artisan::call('vendor:publish', ['--tag' => 'wncms-core-assets']);
        Artisan::call('vendor:publish', ['--tag' => 'wncms-theme-assets']);

        info('assets published');
    }

    /**
     * Install custom language files (custom.php for every lang).
     */
    public function installCustomLangFiles(): void
    {
        $availableLanguages = array_diff(
            scandir(lang_path()),
            ['.', '..', '.gitkeep', '.gitignore']
        );

        $customContent = "<?php\n\n\$custom_words = [\n\n];\n\nreturn \$custom_words;";

        info('adding custom.php to each lang dir');

        foreach ($availableLanguages as $language) {
            $customFilePath = lang_path("{$language}/custom.php");

            if (!File::exists($customFilePath)) {
                File::put($customFilePath, $customContent);
                info("created custom.php in $language");
            } else {
                info("$language already has custom.php");
            }
        }
    }

    /**
     * Install custom route template files.
     */
    public function installCustomRouteFiles(): void
    {
        $customRouteFiles = [
            'custom_api',
            'custom_backend',
            'custom_frontend',
        ];

        $routeTemplate = "<?php\n\n";

        foreach ($customRouteFiles as $routeFileName) {
            $routeFilePath = base_path("routes/$routeFileName.php");

            if (!file_exists($routeFilePath)) {
                $result = file_put_contents($routeFilePath, $routeTemplate);

                if ($result !== false) {
                    info("Created missing route file: $routeFilePath");
                } else {
                    info("Failed to create route file: $routeFilePath");
                }
            }
        }
    }
    /**
     * Update WNCMS system settings in DB.
     * Includes: locale, multi_website, force_https, version.
     */
    public function updateSystemSettings(array $input): void
    {
        // default locale
        if (!empty($input['app_locale'])) {
            uss('app_locale', $input['app_locale']);
            info("Set default app locale to " . $input['app_locale']);
        }

        // multi website mode
        $multiWebsite = !empty($input['multi_website']) && $input['multi_website'] == '1';
        uss('multi_website', $multiWebsite);
        info("Set multi_website to " . ($multiWebsite ? 'true' : 'false'));

        // force https
        if (!empty($input['force_https'])) {
            uss('force_https', true);
            info("updated force_https to true");
        }

        // core version
        $version = config('installer.version');
        uss('core_version', $version);
    }

    /**
     * Mark WNCMS installation completed.
     */
    public function markInstalled(): string
    {
        $installedLogFile = storage_path('installed');
        $dateStamp = date('Y-m-d h:i:s');

        if (!file_exists($installedLogFile)) {
            $message = __('wncms::installer.installed.success_log_message') . $dateStamp . "\n";
            file_put_contents($installedLogFile, $message);
        } else {
            $message = __('wncms::installer.updater.log.success_message') . $dateStamp;
            file_put_contents($installedLogFile, $message . PHP_EOL, FILE_APPEND | LOCK_EX);
        }

        info('created installed file');
        info($message);

        return $message;
    }

    /**
     * Final cleanup after installation.
     */
    public function finalize(): void
    {
        cache()->flush();
        info('cache flushed after installation');
    }
}
