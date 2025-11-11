<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Services\Installer\DatabaseManager;
use Wncms\Services\Installer\PermissionChecker;
use Wncms\Services\Installer\RequirementChecker;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Validator;
use Str;

class InstallController extends Controller
{
    protected $requirementsChecker;
    protected $permissionChecker;
    protected $databaseManager;

    public function __construct()
    {
        $this->requirementsChecker = new RequirementChecker;
        $this->permissionChecker = new PermissionChecker;
        $this->databaseManager = new DatabaseManager;
    }

    /**
     * Step 1
     * Welcome page
     */
    public function welcome()
    {
        return view('wncms::install.welcome');
    }

    /**
     * Step 2
     * Check requirements
     */
    public function requirements()
    {
        $phpSupportInfo = $this->requirementsChecker->checkPHPversion(config('installer.core.minPhpVersion'));
        $requirements = $this->requirementsChecker->check(config('installer.requirements'));
        return view('wncms::install.requirements', compact('requirements', 'phpSupportInfo'));
    }

    /**
     * Step 3
     * Check permisions
     */
    public function permissions()
    {
        $permissions = $this->permissionChecker->check(config('installer.permissions'));
        return view('wncms::install.permissions', compact('permissions'));
    }

    /**
     * Step 4
     * Confirm installation
     * Then call install() when confirmed
     */
    public function wizard()
    {
        $languages = config('laravellocalization.supportedLocales');
        return view('wncms::install.wizard', [
            'languages' => $languages,
        ]);
    }

    /**
     * ! Start installation
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function install(Request $request)
    {
        // info($request->all());
        parse_str($request->formData, $input);

        // check env variables
        $rules = config('installer.environment.form.rules');
        $messages = [
            'environment_custom.required_if' => __('wncms::installer.environment.wizard.form.name_required'),
            'database_name.required' => __('wncms::word.database_name') . ' ' . __('wncms::word.required'),
            'database_username.required' => __('wncms::word.database_user') . ' ' . __('wncms::word.required'),
            'database_password.required' => __('wncms::word.database_password') . ' ' . __('wncms::word.required'),
        ];
        $validator = Validator::make($input, $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'message' => $validator->errors(),
            ]);
        }

        // connect database
        $connectedToDatabase = $this->checkDatabaseConnection($input);
        if (!$connectedToDatabase) {
            return response()->json([
                'status' => 'fail',
                'message' => __('wncms::installer.environment.wizard.form.db_connection_failed'),
            ]);
        }

        // create .env file
        $this->saveEnvFile($input);

        // generate key
        Artisan::call('key:generate', ['--force' => true]);
        info('generated key');

        // migrate
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
            } catch (\Throwable $e) {
                info('SQL import failed: ' . $e->getMessage());

                // fallback to migrations + seeders
                info('Fallback to migrate:fresh due to SQL import failure.');
                Artisan::call('migrate:fresh', [
                    '--seed' => true,
                    '--force' => true,
                    '--seeder' => \Wncms\Database\Seeders\DatabaseSeeder::class,
                ]);
                info('Completed fallback migrations + seeders.');
            }
        } else {
            // fallback to migrations + seeders
            info('SQL dump not found, running migrations + seeders.');
            Artisan::call('migrate:fresh', [
                '--seed' => true,
                '--force' => true,
                '--seeder' => \Wncms\Database\Seeders\DatabaseSeeder::class,
            ]);
            info('Completed migrations + seeders.');
        }

        Artisan::call('vendor:publish', ['--tag' => 'wncms-core-assets']);
        Artisan::call('vendor:publish', ['--tag' => 'wncms-theme-assets']);
        info('assets published');

        // install lang files
        $this->install_custom_lang_files();

        // install custom route files
        $this->install_custom_route_files();

        // set default locale
        if (!empty($input['app_locale'])) {
            uss('app_locale', $input['app_locale']);
            info("Set default app locale to " . $input['app_locale']);
        }

        // Set multi-website flag in system settings
        $multiWebsite = !empty($input['multi_website']) && $input['multi_website'] == '1';
        uss('multi_website', $multiWebsite);
        info("Set multi_website to " . ($multiWebsite ? 'true' : 'false'));

        // mark installed
        $message = $this->markInstalled();
        info('created installed file');
        info($message);

        // force https
        if (!empty($input['force_https'])) {
            uss('force_https', true);
            info("updated force_https to true");
        }

        // set version
        $version = config('installer.version');
        uss('core_version', $version);

        cache()->flush();

        return response()->json([
            'status' => 'success',
            'message' => __('wncms::word.started_installation'),
            'reload' => true,
        ]);
    }

    /**
     * Validate database connection with user credentials (Form Wizard).
     *
     * @param array $input Parse from $request
     * @return bool
     */
    private function checkDatabaseConnection($input)
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
     * Save the environment file.
     *
     * @param array $input
     * @return string
     */
    private function saveEnvFile($input)
    {

        $envFileData =
            'APP_NAME=\'' . ($input['app_name'] ?? '') . "'\n" .
            'APP_ENV=' . ($input['environment'] ?? '') . "\n" .
            'APP_KEY=' . 'base64:' . base64_encode(Str::random(32)) . "\n" .
            'APP_DEBUG=' . ($input['app_debug'] ?? '') . "\n" .
            'DEBUGBAR_ENABLED=' . "false" . "\n" .
            'CSS_DEBUG=' . "false" . "\n" .
            'JS_DEBUG=' . "false" . "\n" .
            'APP_VERSION=' . config('installer.version') . "\n" .
            'CUSTOM_VERSION=' . "1" . "\n" .
            'APP_LOG_LEVEL=' . ($input['app_log_level'] ?? '') . "\n" .
            'APP_URL=' . ($input['app_url'] ?? '') . "\n\n" .

            'APP_MAINTENANCE_DRIVER=' . "file" . "\n\n" .
            'BCRYPT_ROUNDS=' . "12" . "\n\n" .

            'DB_CONNECTION=' . ($input['database_connection'] ?? '') . "\n" .
            'DB_HOST=' . ($input['database_hostname'] ?? '') . "\n" .
            'DB_PORT=' . ($input['database_port'] ?? '') . "\n" .
            'DB_DATABASE=' . ($input['database_name'] ?? '') . "\n" .
            'DB_USERNAME=' . ($input['database_username'] ?? '') . "\n" .
            'DB_PASSWORD=' . ($input['database_password'] ?? '') . "\n\n" .

            'BROADCAST_DRIVER=' . ($input['broadcast_driver'] ?? '') . "\n" .
            'CACHE_STORE=' . ($input['cache_store'] ?? '') . "\n" .
            // 'CACHE_PREFIX=' . "" . "\n".

            'SESSION_DRIVER=' . ($input['session_driver'] ?? '') . "\n" .
            'QUEUE_CONNECTION=' . ($input['queue_connection'] ?? '') . "\n\n" .

            'REDIS_HOST=' . ($input['redis_hostname'] ?? '') . "\n" .
            'REDIS_PASSWORD=' . ($input['redis_password'] ?? '') . "\n" .
            'REDIS_PORT=' . ($input['redis_port'] ?? '') . "\n\n" .

            // 'MAIL_DRIVER=' . ($input['mail_driver']??'') . "\n".
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
            $results = __('wncms::installer.environment.success');
        } catch (Exception $e) {
            $results = __('wncms::installer.environment.errors');
        }

        return $results;
    }

    /**
     * Migrate and seed the database.
     *
     * @return \Illuminate\View\View
     */
    public function database()
    {
        $response = $this->databaseManager->migrateAndSeed();
        return redirect()->route('installer.final')->with(['message' => $response]);
    }

    /**
     * Check if installation is completed
     * @return \Illuminate\Http\Response
     */
    public function progress()
    {
        return response()->json([
            'status' => 'success',
            'message' => __('wncms::word.successfully_fetch'),
            'completed' => wncms_is_installed(),
        ]);
    }

    /**
     * Install custom lang files
     */
    public function install_custom_lang_files()
    {
        $availableLanguages = array_diff(scandir(lang_path()), ['.', '..', '.gitkeep', '.gitignore']);
        $customContent = "<?php\n\n\$custom_words = [\n\n];\n\nreturn \$custom_words;";
        info('adding custom.php to each lang dir');

        foreach ($availableLanguages as $language) {
            $customFilePath = lang_path("{$language}/custom.php");

            // Check if the custom.php file already exists
            if (!File::exists($customFilePath)) {
                File::put($customFilePath, $customContent);
                // Use system() to set permissions, ownership, and group
                // Process::run("chmod 0664 {$customFilePath}");
                // Process::run("chown www:www {$customFilePath}");

                info("created custom.php in $language");
            } else {
                info("$language already has custom.php");
                // Handle the case where the file already exists
            }
        }
    }

    /**
     * Install custom route files
     */
    public function install_custom_route_files()
    {
        $customRouteFiles = [
            'custom_api',
            'custom_backend',
            'custom_frontend',
        ];

        $routeTemplate = <<<'EOT'
        <?php

        EOT;

        foreach ($customRouteFiles as $routeFileName) {
            // Construct the route file path.
            $routeFilePath = base_path("routes/$routeFileName.php");

            // Check if the route file exists.
            if (!file_exists($routeFilePath)) {
                // Set file permissions to 'www' user and 'www' group.
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
     * Show when user try to install at installed status 
     */
    public function installed()
    {
        return view('wncms::errors.installed');
    }

    /**
     * Create an installed file in storage
     * This is used to determine if WNCMS is intalled.
     */
    private function markInstalled()
    {
        $installedLogFile = storage_path('installed');
        $dateStamp = date('Y-m-d h:i:s');

        if (! file_exists($installedLogFile)) {
            $message = __('wncms::installer.installed.success_log_message') . $dateStamp . "\n";
            file_put_contents($installedLogFile, $message);
        } else {
            $message = __('wncms::installer.updater.log.success_message') . $dateStamp;
            file_put_contents($installedLogFile, $message . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
        return $message;
    }
}
