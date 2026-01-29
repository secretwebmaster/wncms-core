<?php

namespace Wncms\Console\Commands;

use Illuminate\Console\Command;
use Wncms\Services\Installer\InstallerManager;

class Install extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Required arguments:
     *   db_connection
     *   db_host
     *   db_port
     *   db_name
     *   db_user
     *   db_pass
     *
     * Optional options:
     *   --app_name=
     *   --app_url=
     *   --app_locale=
     *   --app_env=
     *   --app_debug=
     *   --log_level=
     *   --broadcast_connection=
     *   --cache_store=
     *   --session_driver=
     *   --queue_connection=
     *   --redis_host=
     *   --redis_password=
     *   --redis_port=
     *   --mail_driver=
     *   --mail_host=
     *   --mail_port=
     *   --mail_username=
     *   --mail_password=
     *   --mail_encryption=
     *   --pusher_app_id=
     *   --pusher_app_key=
     *   --pusher_app_secret=
     *   --multi_website
     *   --force_https
     */
    protected $signature = 'wncms:install
        {db_connection}
        {db_host}
        {db_port}
        {db_name}
        {db_user}
        {db_pass}
        {--app_name=}
        {--app_url=}
        {--app_locale=}
        {--app_env=}
        {--app_debug=}
        {--log_level=}
        {--broadcast_connection=}
        {--cache_store=}
        {--session_driver=}
        {--queue_connection=}
        {--redis_host=}
        {--redis_password=}
        {--redis_port=}
        {--mail_driver=}
        {--mail_host=}
        {--mail_port=}
        {--mail_username=}
        {--mail_password=}
        {--mail_encryption=}
        {--pusher_app_id=}
        {--pusher_app_key=}
        {--pusher_app_secret=}
        {--multi_website}
        {--force_https}
        {--site_name=}
        {--domain=}
        {--theme=default}';

    /**
     * Command description.
     */
    protected $description = 'Install WNCMS using terminal command';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting WNCMS installation...');

        // Step 1: Get arguments
        $dbConnection = $this->argument('db_connection');
        $dbHost = $this->argument('db_host');
        $dbPort = $this->argument('db_port');
        $dbName = $this->argument('db_name');
        $dbUser = $this->argument('db_user');
        $dbPass = $this->argument('db_pass');
        $this->info('Step 1: CLI arguments loaded');

        // Step 2: Validate required inputs
        $requiredFields = [
            'db_connection' => $dbConnection,
            'db_host' => $dbHost,
            'db_port' => $dbPort,
            'db_name' => $dbName,
            'db_user' => $dbUser,
            'db_pass' => $dbPass,
        ];
        foreach ($requiredFields as $key => $value) {
            if (empty($value)) {
                $this->error("Missing required parameter: {$key}");
                return Command::FAILURE;
            }
        }
        if (!is_numeric($dbPort)) {
            $this->error('Database port must be numeric');
            return Command::FAILURE;
        }
        $this->info('Step 2: Required parameters validated');

        // Step 3: Collect optional options
        $options = $this->options();
        $this->info('Step 3: CLI options collected');

        // Step 4: Map CLI input → InstallerManager input
        $input = [
            'database_connection' => $dbConnection,
            'database_hostname' => $dbHost,
            'database_port' => $dbPort,
            'database_name' => $dbName,
            'database_username' => $dbUser,
            'database_password' => $dbPass,

            'app_name' => $options['app_name'],
            'app_url' => $options['app_url'],
            'app_locale' => $options['app_locale'],
            'app_env' => $options['app_env'],
            'app_debug' => $options['app_debug'],
            'log_level' => $options['log_level'],
            'broadcast_connection' => $options['broadcast_connection'],
            'cache_store' => $options['cache_store'],
            'session_driver' => $options['session_driver'],
            'queue_connection' => $options['queue_connection'],
            'redis_host' => $options['redis_host'],
            'redis_password' => $options['redis_password'],
            'redis_port' => $options['redis_port'],
            'mail_driver' => $options['mail_driver'],
            'mail_host' => $options['mail_host'],
            'mail_port' => $options['mail_port'],
            'mail_username' => $options['mail_username'],
            'mail_password' => $options['mail_password'],
            'mail_encryption' => $options['mail_encryption'],
            'pusher_app_id' => $options['pusher_app_id'],
            'pusher_app_key' => $options['pusher_app_key'],
            'pusher_app_secret' => $options['pusher_app_secret'],

            'multi_website' => $options['multi_website'] ? '1' : null,
            'force_https' => $options['force_https'] ? '1' : null,

            'site_name' => $options['site_name'],
            'domain' => $options['domain'],
            'theme' => $options['theme'] ?: 'default',
        ];
        $this->info('Step 4: Installation input mapped');

        $installer = new InstallerManager;

        // Prepare and normalize input
        $input = $installer->normalizeInput($input);

        // Step 5.1: Test DB connection
        if (!$installer->checkDatabaseConnection($input)) {
            $this->error('Database connection failed');
            return Command::FAILURE;
        }
        $this->info('Step 5.1: Database connection verified');

        // Step 5.2: Write ENV
        $installer->writeEnvFile($input);
        $this->info('Step 5.2: .env file written');

        // Step 5.3: Generate app key
        $installer->generateAppKey();
        $this->info('Step 5.3: APP_KEY generated');

        // Step 5.4: Setup database (SQL import or migrate)
        $installer->runDatabaseSetup();
        $this->info('Step 5.4: Database migration and seeding completed');

        // Step 5.5: Publish assets
        $installer->publishAssets();
        $this->info('Step 5.5: Vendor assets published');

        // Step 5.6: Install custom language files
        $installer->installCustomLangFiles();
        $this->info('Step 5.6: Custom language files installed');

        // Step 5.7: Install custom route files
        $installer->installCustomRouteFiles();
        $this->info('Step 5.7: Custom route files installed');

        // Step 5.8: Update system settings (locale, multi-site, https)
        $installer->updateSystemSettings($input);
        $this->info('Step 5.8: System settings saved');

        // Step 5.9: Mark installed
        $installer->markInstalled();
        $this->info('Step 5.9: Installation marker created');

        // Step 5.10: Final cleanup
        $installer->finalize();
        $this->info('Step 5.10: Cache cleared and installation finalized');

        $this->info('WNCMS installation completed successfully.');

        // Step 6: Optional website creation
        if (!empty($input['domain'])) {

            $this->info('Step 6: Creating website model...');

            $siteName = $input['site_name'] ?: $input['domain'];
            $theme = $input['theme'] ?: 'default';

            $websiteModel = wncms()->getModelClass('website');

            $exists = $websiteModel::where('domain', $input['domain'])->first();
            if ($exists) {
                $this->info("Website {$input['domain']} already exists. Skipping creation.");
            } else {
                $website = $websiteModel::create([
                    'site_name' => $siteName,
                    'domain' => $input['domain'],
                    'theme' => $theme,
                ]);

                $this->info("Website created with name {$siteName}, domain {$input['domain']}, theme {$theme}");

                // Add default theme options
                $defaultOptions = config("theme.{$theme}.default");
                foreach ($defaultOptions ?? [] as $key => $value) {
                    $website->theme_options()->firstOrCreate(
                        [
                            'theme' => $theme,
                            'key' => $key,
                        ],
                        [
                            'value' => $value,
                        ]
                    );
                }

                $this->info("Default theme options added for {$theme}");
            }
        } else {
            $this->info('Step 6: No domain set. Website model creation skipped.');
        }

        $this->info("__        ___   _  ____ __  __ ____  ");
        $this->info("\ \      / / \ | |/ ___|  \/  / ___| ");
        $this->info(" \ \ /\ / /|  \| | |   | |\/| \___ \ ");
        $this->info("  \ V  V / | |\  | |___| |  | |___) |");
        $this->info("   \_/\_/  |_| \_|\____|_|  |_|____/ ");
        $this->info("\nWelcome to WNCMS! Your installation is complete. \n");
        $this->info('Default admin account:');
        $this->info('Email: admin@demo.com');
        $this->info('Password: wncms.cc');

        return Command::SUCCESS;
    }
}
