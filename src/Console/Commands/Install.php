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

            'multi_website' => $options['multi_website'] ? '1' : '0',
            'force_https' => $options['force_https'] ? '1' : '0',

            'site_name' => $options['site_name'],
            'domain' => $options['domain'],
            'theme' => $options['theme'] ?: 'default',
        ];
        $this->info('Step 4: Installation input mapped');

        $installer = new InstallerManager;

        // Prepare and normalize input
        $input = $installer->normalizeInput($input);

        $result = $installer->runInstallation($input);

        if (!$result['passed']) {
            $this->error('Database connection failed');
            return Command::FAILURE;
        }
        $this->info('Step 5: Shared installation pipeline completed');

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
