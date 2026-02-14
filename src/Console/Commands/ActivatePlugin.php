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

            $currentStatus = Plugin::where('plugin_id', $pluginId)->value('status') ?: 'inactive';
            $remark = $validation['passed'] ? null : '[MANIFEST_ERROR] ' . $validation['message'];

            Plugin::updateOrCreate(
                ['plugin_id' => $pluginId],
                [
                    'name' => $this->resolveTranslatableManifestField($manifest['name'] ?? null, Str::headline($folderName)),
                    'description' => $manifestManager->resolveTranslatableField($manifest['description'] ?? null, ''),
                    'author' => $manifestManager->resolveTranslatableField($manifest['author'] ?? null, ''),
                    'version' => (string) ($manifest['version'] ?? '1.0.0'),
                    'url' => (string) ($manifest['url'] ?? ''),
                    'path' => $folderName,
                    'status' => $currentStatus,
                    'remark' => $remark,
                ]
            );
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
}
