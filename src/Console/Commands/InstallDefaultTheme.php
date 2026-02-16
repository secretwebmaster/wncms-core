<?php

namespace Wncms\Console\Commands;

use Illuminate\Console\Command;
use Wncms\Services\Installer\InstallerManager;

class InstallDefaultTheme extends Command
{
    protected $signature = 'wncms:install-default-theme {--force}';

    protected $description = 'Install default theme assets into public/themes';

    public function handle(): int
    {
        $installer = new InstallerManager;
        $result = $installer->installDefaultThemeAssets((bool) $this->option('force'));

        if (!empty($result['output'])) {
            $this->line($result['output']);
        }

        if (!$result['passed']) {
            $this->error(__('wncms::word.install_default_theme_failed'));
            return self::FAILURE;
        }

        $this->info(__('wncms::word.install_default_theme_success'));
        return self::SUCCESS;
    }
}
