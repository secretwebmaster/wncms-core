<?php

namespace Wncms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Wncms\Models\Plugin;
use Wncms\Plugins\PluginActivationCompatibilityValidator;
use Wncms\Plugins\PluginLifecycleManager;
use Wncms\Plugins\PluginManifestManager;

class ActivatePlugin extends Command
{
    protected $signature = 'wncms:activate-plugin {name}';

    protected $description = 'Activate a plugin by name, plugin_id, or directory name';

    public function handle()
    {
        if (!Schema::hasTable('plugins')) {
            $this->error('plugins table does not exist. Please run migrations first.');
            return Command::FAILURE;
        }

        $name = trim((string) $this->argument('name'));
        if ($name === '') {
            $this->error('Plugin name is required.');
            return Command::FAILURE;
        }

        $this->syncPluginsFromDirectory();
        $plugin = $this->findPlugin($name);

        if (!$plugin) {
            $this->error("Plugin [{$name}] not found in database or plugin directory.");
            return Command::FAILURE;
        }

        $sync = $this->syncSinglePluginFromManifest($plugin, true);
        if (!$sync['passed']) {
            $this->error($sync['message']);
            return Command::FAILURE;
        }

        $plugin = $plugin->fresh();
        $upgrade = app(PluginLifecycleManager::class)->upgradePlugin($plugin);
        if (!$upgrade['passed']) {
            $remark = '[UPGRADE_ERROR] ' . mb_substr((string) $upgrade['message'], 0, 300);
            $plugin->update(['remark' => $remark]);
            $this->error(__('wncms::word.plugin_upgrade_failed_with_reason', ['reason' => $upgrade['message']]));
            return Command::FAILURE;
        }

        $validation = app(PluginActivationCompatibilityValidator::class)->validate($plugin);
        if (!$validation['passed']) {
            $message = $this->resolveValidationMessage($validation);
            $plugin->update(['remark' => '[ACTIVATION_BLOCKED] ' . $message]);
            $this->error(__('wncms::word.plugin_activation_blocked_with_reason', ['reason' => $message]));
            return Command::FAILURE;
        }

        $lifecycle = app(PluginLifecycleManager::class)->run($plugin, 'activate');
        if (!$lifecycle['passed']) {
            $remark = '[LIFECYCLE_ERROR] ' . mb_substr((string) $lifecycle['message'], 0, 300);
            $plugin->update(['remark' => $remark]);
            $this->error("Plugin [{$plugin->name}] activate failed: {$lifecycle['message']}");
            return Command::FAILURE;
        }

        $plugin->update(['status' => 'active']);
        $this->info("Plugin [{$plugin->name}] activated successfully.");

        return Command::SUCCESS;
    }

    protected function findPlugin(string $name): ?Plugin
    {
        $folder = basename(str_replace('\\', '/', $name));

        return Plugin::query()
            ->where('plugin_id', $name)
            ->orWhere('name', $name)
            ->orWhere('path', $name)
            ->orWhere('path', $folder)
            ->first();
    }

    protected function syncPluginsFromDirectory(): void
    {
        $pluginsRoot = config('filesystems.disks.plugins.root', public_path('plugins'));
        $manifestManager = app(PluginManifestManager::class);

        if (!File::isDirectory($pluginsRoot)) {
            return;
        }

        foreach (File::directories($pluginsRoot) as $directory) {
            $folderName = basename($directory);
            $manifestPath = $directory . DIRECTORY_SEPARATOR . 'plugin.json';
            $validation = $manifestManager->readAndValidateManifestPath($manifestPath);
            $manifest = $validation['manifest'];
            $pluginId = $manifestManager->resolvePluginId($manifest, $folderName);
            $existingPlugin = Plugin::where('plugin_id', $pluginId)->first();
            if ($existingPlugin) {
                $currentRemark = (string) ($existingPlugin->remark ?? '');
                $remark = $validation['passed'] ? $currentRemark : '[MANIFEST_ERROR] ' . (string) $validation['message'];
                if ($validation['passed'] && str_starts_with($currentRemark, '[MANIFEST_ERROR]')) {
                    $remark = null;
                }

                $existingPlugin->forceFill([
                    'path' => $folderName,
                    'remark' => $remark,
                ])->save();
                continue;
            }

            $remark = $validation['passed'] ? null : '[MANIFEST_ERROR] ' . (string) $validation['message'];
            Plugin::create([
                'plugin_id' => $pluginId,
                'name' => $this->resolveTranslatableManifestField($manifest['name'] ?? null, Str::headline($folderName)),
                'description' => $manifestManager->resolveTranslatableField($manifest['description'] ?? null, ''),
                'author' => $manifestManager->resolveTranslatableField($manifest['author'] ?? null, ''),
                'version' => (string) ($manifest['version'] ?? '1.0.0'),
                'url' => (string) ($manifest['url'] ?? ''),
                'path' => $folderName,
                'status' => 'inactive',
                'remark' => $remark,
            ]);
        }
    }

    protected function resolveTranslatableManifestField($value, string $fallback = ''): string
    {
        return app(PluginManifestManager::class)->resolveTranslatableField($value, $fallback);
    }

    protected function resolveValidationMessage(array $validation): string
    {
        if (!empty($validation['message_key']) && is_string($validation['message_key'])) {
            return __(
                $validation['message_key'],
                is_array($validation['message_params'] ?? null) ? $validation['message_params'] : []
            );
        }

        return (string) ($validation['message'] ?? __('wncms::word.error'));
    }

    protected function syncSinglePluginFromManifest(Plugin $plugin, bool $preserveVersion): array
    {
        $pluginsRoot = config('filesystems.disks.plugins.root', public_path('plugins'));
        $pluginPath = trim((string) $plugin->path, '/\\');
        if ($pluginPath === '') {
            return [
                'passed' => false,
                'message' => __('wncms::word.plugin_manifest_sync_failed_with_reason', ['reason' => 'plugin path is empty']),
            ];
        }

        $manifestPath = rtrim($pluginsRoot, '/\\') . DIRECTORY_SEPARATOR . $pluginPath . DIRECTORY_SEPARATOR . 'plugin.json';
        $manifestManager = app(PluginManifestManager::class);
        $validation = $manifestManager->readAndValidateManifestPath($manifestPath);
        if (!$validation['passed']) {
            return [
                'passed' => false,
                'message' => __('wncms::word.plugin_manifest_sync_failed_with_reason', ['reason' => (string) $validation['message']]),
            ];
        }

        $manifest = $validation['manifest'];
        $version = $preserveVersion ? (string) $plugin->version : (string) ($manifest['version'] ?? (string) $plugin->version);

        $plugin->forceFill([
            'name' => $manifestManager->resolveTranslatableField($manifest['name'] ?? null, (string) $plugin->name),
            'description' => $manifestManager->resolveTranslatableField($manifest['description'] ?? null, (string) $plugin->description),
            'author' => $manifestManager->resolveTranslatableField($manifest['author'] ?? null, (string) $plugin->author),
            'url' => (string) ($manifest['url'] ?? (string) $plugin->url),
            'path' => $pluginPath,
            'version' => $version,
            'remark' => str_starts_with((string) $plugin->remark, '[MANIFEST_ERROR]') ? null : $plugin->remark,
        ])->save();

        return [
            'passed' => true,
            'message' => '',
        ];
    }

}
