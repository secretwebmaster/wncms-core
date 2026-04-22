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
     *   --agent
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
        {--agent=}
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
        $this->setConsoleLocaleFromOption($this->option('app_locale'));
        $this->info($this->tr('install_cli_starting'));

        // Step 1: Get arguments
        $dbConnection = $this->argument('db_connection');
        $dbHost = $this->argument('db_host');
        $dbPort = $this->argument('db_port');
        $dbName = $this->argument('db_name');
        $dbUser = $this->argument('db_user');
        $dbPass = $this->argument('db_pass');
        $this->info($this->tr('install_cli_step_1_loaded'));

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
                $this->error($this->tr('install_cli_missing_required_parameter', ['key' => $key]));
                return Command::FAILURE;
            }
        }
        if (!is_numeric($dbPort)) {
            $this->error($this->tr('install_cli_database_port_must_be_numeric'));
            return Command::FAILURE;
        }
        $this->info($this->tr('install_cli_step_2_validated'));

        // Step 3: Collect optional options
        $options = $this->options();
        $publishAgentFiles = $this->shouldPublishAgentFiles($options['agent'] ?? null);
        $this->info($this->tr('install_cli_step_3_collected'));

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
        $this->info($this->tr('install_cli_step_4_mapped'));

        $installer = new InstallerManager;

        // Prepare and normalize input
        $input = $installer->normalizeInput($input);

        $result = $installer->runInstallation($input);

        if (!$result['passed']) {
            $this->error($this->tr('install_cli_database_connection_failed'));
            return Command::FAILURE;
        }
        $this->info($this->tr('install_cli_step_5_completed'));

        $this->info($this->tr('install_cli_installation_success'));

        // Step 6: Optional website creation
        if (!empty($input['domain'])) {

            $this->info($this->tr('install_cli_step_6_creating_website'));

            $siteName = $input['site_name'] ?: $input['domain'];
            $theme = $input['theme'] ?: 'default';

            $websiteModel = wncms()->getModelClass('website');

            $exists = $websiteModel::where('domain', $input['domain'])->first();
            if ($exists) {
                $this->info($this->tr('install_cli_website_already_exists_skipping', [
                    'domain' => $input['domain'],
                ]));
            } else {
                $website = $websiteModel::create([
                    'site_name' => $siteName,
                    'domain' => $input['domain'],
                    'theme' => $theme,
                ]);

                $this->info($this->tr('install_cli_website_created', [
                    'site_name' => $siteName,
                    'domain' => $input['domain'],
                    'theme' => $theme,
                ]));

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

                $this->info($this->tr('install_cli_default_theme_options_added', ['theme' => $theme]));
            }
        } else {
            $this->info($this->tr('install_cli_step_6_skipped_no_domain'));
        }

        if ($publishAgentFiles) {
            $this->info('Publishing wncms-agent-files...');

            $publishStatus = $this->call('vendor:publish', [
                '--tag' => 'wncms-agent-files',
            ]);

            if ($publishStatus !== Command::SUCCESS) {
                $this->error('Failed to publish wncms-agent-files.');
                return Command::FAILURE;
            }

            $this->info('wncms-agent-files published.');
        }

        $this->info("__        ___   _  ____ __  __ ____  ");
        $this->info("\ \      / / \ | |/ ___|  \/  / ___| ");
        $this->info(" \ \ /\ / /|  \| | |   | |\/| \___ \ ");
        $this->info("  \ V  V / | |\  | |___| |  | |___) |");
        $this->info("   \_/\_/  |_| \_|\____|_|  |_|____/ ");
        $this->info("\n" . $this->tr('install_cli_welcome_completed') . " \n");
        $frontendUrl = rtrim((string) ($input['app_url'] ?? 'http://localhost'), '/');
        $loginUrl = rtrim((string) ($input['app_url'] ?? 'http://localhost'), '/') . '/panel/login';
        $this->info($this->tr('install_cli_frontend_home', ['url' => $frontendUrl]));
        $this->info('');
        $this->info($this->tr('install_cli_default_admin_account'));
        $this->info($this->tr('install_cli_login_url', ['url' => $loginUrl]));
        $this->info($this->tr('install_cli_email'));
        $this->info($this->tr('install_cli_password'));

        return Command::SUCCESS;
    }

    protected function tr(string $key, array $replace = []): string
    {
        return __('wncms::word.' . $key, $replace);
    }

    protected function setConsoleLocaleFromOption($localeInput): void
    {
        app()->setLocale($this->resolveCliLocale($localeInput ? (string) $localeInput : null));
    }

    protected function resolveCliLocale(?string $localeInput): string
    {
        $supportedLocales = (array) config('laravellocalization.supportedLocales', []);
        $fallbackLocale = $this->normalizeLocaleKey((string) config('app.locale', 'en'));

        if (!isset($supportedLocales[$fallbackLocale])) {
            $fallbackLocale = array_key_first($supportedLocales) ?: 'en';
        }

        $rawLocale = trim((string) ($localeInput ?? ''));
        if ($rawLocale === '') {
            return $fallbackLocale;
        }

        $normalizedLocale = $this->normalizeLocaleKey($rawLocale);
        if (isset($supportedLocales[$normalizedLocale])) {
            return $normalizedLocale;
        }

        return $fallbackLocale;
    }

    protected function normalizeLocaleKey(string $locale): string
    {
        $locale = str_replace('-', '_', trim($locale));
        if ($locale === '') {
            return $locale;
        }

        $parts = explode('_', $locale);
        if (count($parts) === 1) {
            return strtolower($parts[0]);
        }

        $language = strtolower(array_shift($parts));
        $region = strtoupper(array_shift($parts));

        return $language . '_' . $region . (empty($parts) ? '' : '_' . implode('_', $parts));
    }

    protected function shouldPublishAgentFiles($agentOption): bool
    {
        if ($this->input->hasParameterOption('--agent') && ($agentOption === null || $agentOption === '')) {
            return true;
        }

        return $this->isTruthyOption($agentOption);
    }

    protected function isTruthyOption($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (int) $value === 1;
        }

        $normalized = strtolower(trim((string) $value));
        if ($normalized === '') {
            return false;
        }

        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }
}
