<?php

namespace Wncms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Wncms\Models\Plugin;
use Wncms\Plugins\PluginManifestManager;

class VerifyPluginHooks extends Command
{
    protected $signature = 'wncms:verify-plugin-hooks';

    protected $description = 'Verify plugin manifests and hard-cut users hook migration gates before release';

    public function handle()
    {
        $this->info('Running WNCMS plugin/hook verification gates...');

        $failed = false;

        if (!$this->verifyPluginRoot()) {
            $failed = true;
        }

        if (!$this->verifyPluginManifests()) {
            $failed = true;
        }

        if (!$this->verifyLegacyUsersHooksRemoved()) {
            $failed = true;
        }

        if (!$this->verifyPluginTableState()) {
            $failed = true;
        }

        if ($failed) {
            $this->error('Verification failed. Block release until all gates are fixed.');
            return Command::FAILURE;
        }

        $this->info('All plugin/hook verification gates passed.');
        return Command::SUCCESS;
    }

    protected function verifyPluginRoot(): bool
    {
        $pluginsRoot = config('filesystems.disks.plugins.root', public_path('plugins'));

        if (!File::isDirectory($pluginsRoot)) {
            $this->error("Gate failed: plugin root directory not found: {$pluginsRoot}");
            return false;
        }

        $this->line("Gate passed: plugin root detected: {$pluginsRoot}");
        return true;
    }

    protected function verifyPluginManifests(): bool
    {
        $pluginsRoot = config('filesystems.disks.plugins.root', public_path('plugins'));
        $directories = File::isDirectory($pluginsRoot) ? File::directories($pluginsRoot) : [];
        $invalidPlugins = [];

        foreach ($directories as $directory) {
            $manifestPath = $directory . DIRECTORY_SEPARATOR . 'plugin.json';
            $folderName = basename($directory);
            $validation = $this->readAndValidateManifest($manifestPath);

            if (!$validation['passed']) {
                $invalidPlugins[] = "{$folderName}: {$validation['message']}";
            }
        }

        if (!empty($invalidPlugins)) {
            $this->error('Gate failed: broken plugin manifests found:');
            foreach ($invalidPlugins as $line) {
                $this->line("- {$line}");
            }
            return false;
        }

        $this->line('Gate passed: all plugin manifests are valid.');
        return true;
    }

    protected function verifyLegacyUsersHooksRemoved(): bool
    {
        $legacyHooks = [
            'wncms.frontend.users.dashboard',
            'wncms.frontend.users.show_login',
            'wncms.frontend.users.show_register',
            'wncms.frontend.users.register',
            'wncms.frontend.users.registered',
            'wncms.frontend.users.registered.credits',
            'wncms.frontend.users.registered.welcome_email',
            'wncms.frontend.users.auth',
        ];

        $targetFiles = [
            base_path('src/Http/Controllers/Frontend/UserController.php'),
            base_path('src/Http/Controllers/Backend/UserController.php'),
        ];

        $matched = [];

        foreach ($targetFiles as $targetFile) {
            if (!File::exists($targetFile)) {
                continue;
            }

            $content = File::get($targetFile);
            foreach ($legacyHooks as $legacyHook) {
                if (str_contains($content, "'" . $legacyHook . "'")) {
                    $matched[] = "{$legacyHook} in {$targetFile}";
                }
            }
        }

        if (!empty($matched)) {
            $this->error('Gate failed: legacy users hooks still exist:');
            foreach ($matched as $line) {
                $this->line("- {$line}");
            }
            return false;
        }

        $this->line('Gate passed: legacy users hooks removed from target controllers.');
        return true;
    }

    protected function verifyPluginTableState(): bool
    {
        if (!Schema::hasTable('plugins')) {
            $this->error('Gate failed: plugins table does not exist.');
            return false;
        }

        $brokenCount = Plugin::query()
            ->where('remark', 'like', '[MANIFEST_ERROR]%')
            ->orWhere('remark', 'like', '[LOAD_ERROR]%')
            ->count();

        if ($brokenCount > 0) {
            $this->error("Gate failed: {$brokenCount} plugin records are marked as broken in plugins table.");
            return false;
        }

        $this->line('Gate passed: no broken plugin record markers in plugins table.');
        return true;
    }

    protected function readAndValidateManifest(string $manifestPath): array
    {
        $read = app(PluginManifestManager::class)->readAndValidateManifestPath($manifestPath);

        return [
            'passed' => (bool) $read['passed'],
            'message' => (string) ($read['message'] ?? ''),
        ];
    }

}
