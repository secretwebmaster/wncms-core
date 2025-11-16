<?php

namespace Wncms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Exception;
use Wncms\Services\Installer\InstallerManager;
use Illuminate\Support\Str;

class InstallWncmsCommand extends Command
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
     *   --locale=
     *   --environment=
     *   --debug=
     *   --log=
     *   --broadcast_driver=
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
        {--locale=}
        {--environment=}
        {--debug=}
        {--log=}
        {--broadcast_driver=}
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
        {--force_https}';

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

        // Step 2: Validate required inputs (unchanged)
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

        // Step 3: Collect optional options
        $options = $this->options();

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
            'app_locale' => $options['locale'],
            'environment' => $options['environment'],
            'debug' => $options['debug'],
            'log' => $options['log'],
            'broadcast_driver' => $options['broadcast_driver'],
            'cache_store' => $options['cache_store'],
            'session_driver' => $options['session_driver'],
            'queue_connection' => $options['queue_connection'],
            'redis_hostname' => $options['redis_host'],
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
        ];

        $installer = new InstallerManager;

        // Step 5: Install WNCMS using InstallerManager
        if (!$installer->checkDatabaseConnection($input)) {
            $this->error('Database connection failed');
            return Command::FAILURE;
        }

        $installer->writeEnvFile($input);
        $installer->generateAppKey();
        $installer->runDatabaseSetup();
        $installer->publishAssets();
        $installer->installCustomLangFiles();
        $installer->installCustomRouteFiles();
        $installer->updateSystemSettings($input);
        $installer->markInstalled();
        $installer->finalize();

        $this->info('WNCMS installation completed successfully.');
        return Command::SUCCESS;
    }
}
