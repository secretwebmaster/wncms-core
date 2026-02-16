<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Wncms\Models\Plugin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Wncms\Plugins\PluginActivationCompatibilityValidator;
use Wncms\Plugins\PluginLifecycleManager;
use Wncms\Plugins\PluginManifestManager;

class PluginController extends Controller
{
    public function index(Request $request)
    {
        $this->syncPluginsFromDirectory();

        $plugins = Plugin::query()
            ->when($request->show_broken, function ($q) {
                $q->where(function ($q2) {
                    $q2->where('remark', 'like', '[MANIFEST_ERROR]%')
                        ->orWhere('remark', 'like', '[LOAD_ERROR]%');
                });
            })
            ->orderBy('plugin_id')
            ->orderBy('id')
            ->get();

        $plugins = $plugins->map(function (Plugin $plugin) {
            $loadErrorDiagnostics = Plugin::extractLoadErrorDiagnostics((string) $plugin->remark);
            $versionStatus = $this->resolveVersionStatus($plugin);
            $plugin->required_plugins_display = '-';
            $plugin->last_load_error_display = $loadErrorDiagnostics['last_load_error'];
            $plugin->last_load_error_file_display = $loadErrorDiagnostics['source_file'];
            $plugin->available_version_display = $versionStatus['available_version'];
            $plugin->update_available = $versionStatus['update_available'];
            $plugin->upgrade_status_display = $versionStatus['status'];

            return $plugin;
        });

        $rawPlugins = $this->buildRawPluginsFromDirectory($request->boolean('show_broken'));
        // dd($rawPlugins);

        return $this->view('backend.plugins.index', [
            'page_title' => wncms()->getModelWord('plugin', 'management'),
            'rawPlugins' => $rawPlugins,
            'plugins' => $plugins,
        ]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'plugin_file' => 'required|mimes:zip',
        ]);

        $file = $request->file('plugin_file');
        $pluginName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        $filePath = $file->storeAs('plugins', $pluginName . '.zip');

        // Save metadata in the database
        Plugin::create([
            'plugin_id' => Str::slug($pluginName, '-'),
            'name' => $pluginName,
            'description' => 'Description of the plugin',
            'version' => '1.0',
            'status' => 'inactive',
            'path' => $pluginName,
        ]);

        return redirect()->route('plugins.index')->with('success', 'Plugin uploaded successfully!');
    }

    public function activate(Plugin $plugin)
    {
        $sync = $this->syncSinglePluginFromManifest($plugin, true);
        if (!$sync['passed']) {
            return redirect()->route('plugins.index')->withErrors(['message' => $sync['message']]);
        }

        $upgrade = app(PluginLifecycleManager::class)->upgradePlugin($plugin->fresh());
        if (!$upgrade['passed']) {
            $plugin->update(['remark' => '[UPGRADE_ERROR] ' . mb_substr((string) $upgrade['message'], 0, 300)]);
            return redirect()->route('plugins.index')->withErrors(['message' => __('wncms::word.plugin_upgrade_failed_with_reason', ['reason' => $upgrade['message']])]);
        }

        $plugin = $plugin->fresh();
        $validation = app(PluginActivationCompatibilityValidator::class)->validate($plugin);
        if (!$validation['passed']) {
            $message = $this->resolveValidationMessage($validation);
            $plugin->update(['remark' => '[ACTIVATION_BLOCKED] ' . $message]);
            return redirect()->route('plugins.index')->withErrors(['message' => __('wncms::word.plugin_activation_blocked_with_reason', ['reason' => $message])]);
        }

        $lifecycle = app(PluginLifecycleManager::class)->run($plugin, 'activate');
        if (!$lifecycle['passed']) {
            $plugin->update(['remark' => '[LIFECYCLE_ERROR] ' . mb_substr((string) $lifecycle['message'], 0, 300)]);
            return redirect()->route('plugins.index')->withErrors(['message' => 'Plugin activate failed: ' . $lifecycle['message']]);
        }

        $plugin->update(['status' => 'active']);

        return redirect()->route('plugins.index')->with('success', 'Plugin activated successfully!');
    }

    public function activate_raw(string $pluginId)
    {
        $plugin = $this->createPluginRecordFromDirectory($pluginId);
        if (!$plugin) {
            return redirect()->route('plugins.index')->withErrors(['message' => __('wncms::word.plugin_manifest_sync_failed_with_reason', ['reason' => 'plugin not found'])]);
        }

        return $this->activate($plugin);
    }

    public function upgrade(Plugin $plugin)
    {
        $sync = $this->syncSinglePluginFromManifest($plugin, true);
        if (!$sync['passed']) {
            return redirect()->route('plugins.index')->withErrors(['message' => $sync['message']]);
        }

        $upgrade = app(PluginLifecycleManager::class)->upgradePlugin($plugin->fresh());
        if (!$upgrade['passed']) {
            $plugin->update(['remark' => '[UPGRADE_ERROR] ' . mb_substr((string) $upgrade['message'], 0, 300)]);
            return redirect()->route('plugins.index')->withErrors(['message' => __('wncms::word.plugin_upgrade_failed_with_reason', ['reason' => $upgrade['message']])]);
        }

        $latestPlugin = $plugin->fresh();
        $syncAfterUpgrade = $this->syncSinglePluginFromManifest($latestPlugin, true);
        if (!$syncAfterUpgrade['passed']) {
            return redirect()->route('plugins.index')->withErrors(['message' => $syncAfterUpgrade['message']]);
        }

        if (!empty($upgrade['changed'])) {
            return redirect()->route('plugins.index')->with('message', __('wncms::word.plugin_upgrade_success_with_versions', [
                'from' => (string) ($upgrade['from_version'] ?? ''),
                'to' => (string) ($upgrade['to_version'] ?? ''),
            ]));
        }

        return redirect()->route('plugins.index')->with('message', __('wncms::word.plugin_upgrade_already_latest'));
    }

    public function deactivate(Plugin $plugin)
    {
        $dependentPlugins = $this->findActiveDependentPlugins($plugin);
        if (!empty($dependentPlugins)) {
            $dependentList = implode(', ', $dependentPlugins);
            return redirect()->route('plugins.index')->withErrors([
                'message' => __('wncms::word.plugin_deactivation_blocked_required_by', [
                    'plugin' => $this->formatPluginLabel($plugin),
                    'dependents' => $dependentList,
                ]),
            ]);
        }

        $lifecycle = app(PluginLifecycleManager::class)->run($plugin, 'deactivate');
        if (!$lifecycle['passed']) {
            $plugin->update(['remark' => '[LIFECYCLE_ERROR] ' . mb_substr((string) $lifecycle['message'], 0, 300)]);
            return redirect()->route('plugins.index')->withErrors(['message' => 'Plugin deactivate failed: ' . $lifecycle['message']]);
        }

        $plugin->update(['status' => 'inactive']);

        return redirect()->route('plugins.index')->with('success', 'Plugin deactivated successfully!');
    }

    public function delete(Plugin $plugin)
    {
        $lifecycle = app(PluginLifecycleManager::class)->run($plugin, 'delete');
        if (!$lifecycle['passed']) {
            $plugin->update(['remark' => '[LIFECYCLE_ERROR] ' . mb_substr((string) $lifecycle['message'], 0, 300)]);
            return redirect()->route('plugins.index')->withErrors(['message' => 'Plugin delete hook failed: ' . $lifecycle['message']]);
        }

        $plugin->delete();

        return redirect()->route('plugins.index')->with('success', 'Plugin deleted successfully!');
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
            if (!$existingPlugin) {
                continue;
            }

            $currentRemark = (string) ($existingPlugin->remark ?? '');
            $remark = $this->resolvePluginSyncRemark($validation, $currentRemark);
            $existingPlugin->forceFill([
                'path' => $folderName,
                'remark' => $remark,
            ])->save();
        }
    }

    protected function resolvePluginSyncRemark(array $validation, string $currentRemark): ?string
    {
        if (!$validation['passed']) {
            return '[MANIFEST_ERROR] ' . (string) ($validation['message'] ?? __('wncms::word.error'));
        }

        if (str_starts_with($currentRemark, '[MANIFEST_ERROR]')) {
            return null;
        }

        return $currentRemark !== '' ? $currentRemark : null;
    }

    protected function resolveVersionStatus(Plugin $plugin): array
    {
        $availableVersion = app(PluginLifecycleManager::class)->getManifestVersion($plugin);
        $installedVersion = ltrim(trim((string) ($plugin->version ?? '')), 'vV');

        if ($availableVersion === null || $installedVersion === '') {
            return [
                'available_version' => '-',
                'update_available' => false,
                'status' => __('wncms::word.unknown'),
            ];
        }

        return [
            'available_version' => $availableVersion,
            'update_available' => version_compare($availableVersion, $installedVersion, '>'),
            'status' => version_compare($availableVersion, $installedVersion, '>') ? __('wncms::word.update_available') : __('wncms::word.latest'),
        ];
    }

    protected function resolvePluginManifestPath(Plugin $plugin): ?string
    {
        $pluginsRoot = config('filesystems.disks.plugins.root', public_path('plugins'));
        $pluginPath = trim((string) $plugin->path, '/\\');

        if ($pluginPath === '') {
            return null;
        }

        return rtrim($pluginsRoot, '/\\') . DIRECTORY_SEPARATOR . $pluginPath . DIRECTORY_SEPARATOR . 'plugin.json';
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
        $manifestPath = $this->resolvePluginManifestPath($plugin);
        if (!$manifestPath) {
            return [
                'passed' => false,
                'message' => __('wncms::word.plugin_manifest_sync_failed_with_reason', ['reason' => 'plugin path is empty']),
            ];
        }

        $manifestManager = app(PluginManifestManager::class);
        $validation = $manifestManager->readAndValidateManifestPath($manifestPath);
        if (!$validation['passed']) {
            return [
                'passed' => false,
                'message' => __('wncms::word.plugin_manifest_sync_failed_with_reason', ['reason' => (string) $validation['message']]),
            ];
        }

        $manifest = $validation['manifest'];
        $path = trim((string) $plugin->path, '/\\');
        $version = $preserveVersion ? (string) $plugin->version : (string) ($manifest['version'] ?? (string) $plugin->version);

        $plugin->forceFill([
            'name' => $manifestManager->resolveTranslatableField($manifest['name'] ?? null, (string) $plugin->name),
            'description' => $manifestManager->resolveTranslatableField($manifest['description'] ?? null, (string) $plugin->description),
            'author' => $manifestManager->resolveTranslatableField($manifest['author'] ?? null, (string) $plugin->author),
            'url' => (string) ($manifest['url'] ?? (string) $plugin->url),
            'path' => $path,
            'version' => $version,
            'remark' => str_starts_with((string) $plugin->remark, '[MANIFEST_ERROR]') ? null : $plugin->remark,
        ])->save();

        return [
            'passed' => true,
            'message' => '',
        ];
    }

    protected function buildRawPluginsFromDirectory(bool $showBroken): \Illuminate\Support\Collection
    {
        $pluginsRoot = config('filesystems.disks.plugins.root', public_path('plugins'));
        $manifestManager = app(PluginManifestManager::class);
        $rawPlugins = collect();

        if (!File::isDirectory($pluginsRoot)) {
            return $rawPlugins;
        }

        foreach (File::directories($pluginsRoot) as $directory) {
            $folderName = basename($directory);
            $manifestPath = $directory . DIRECTORY_SEPARATOR . 'plugin.json';
            $validation = $manifestManager->readAndValidateManifestPath($manifestPath);
            $manifest = is_array($validation['manifest'] ?? null) ? $validation['manifest'] : [];
            $pluginId = $manifestManager->resolvePluginId($manifest, $folderName);

            if (Plugin::where('plugin_id', $pluginId)->exists()) {
                continue;
            }

            $isBroken = !$validation['passed'];
            if ($showBroken && !$isBroken) {
                continue;
            }

            $rawPlugins->push((object) [
                'id' => '-',
                'plugin_id' => $pluginId,
                'name' => $manifestManager->resolveTranslatableField($manifest['name'] ?? null, Str::headline($folderName)),
                'description' => $manifestManager->resolveTranslatableField($manifest['description'] ?? null, ''),
                'author' => $manifestManager->resolveTranslatableField($manifest['author'] ?? null, ''),
                'version' => (string) ($manifest['version'] ?? '1.0.0'),
                'available_version_display' => '-',
                'upgrade_status_display' => __('wncms::word.unknown'),
                'update_available' => false,
                'url' => (string) ($manifest['url'] ?? ''),
                'path' => $folderName,
                'status' => 'inactive',
                'required_plugins_display' => '-',
                'remark' => $isBroken ? '[MANIFEST_ERROR] ' . (string) ($validation['message'] ?? __('wncms::word.error')) : '-',
                'last_load_error_display' => '-',
                'last_load_error_file_display' => '-',
                'created_at' => null,
                'updated_at' => null,
            ]);
        }

        return $rawPlugins->sortBy('plugin_id')->values();
    }

    protected function createPluginRecordFromDirectory(string $pluginId): ?Plugin
    {
        $pluginId = trim($pluginId);
        if ($pluginId === '') {
            return null;
        }

        $pluginsRoot = config('filesystems.disks.plugins.root', public_path('plugins'));
        $manifestManager = app(PluginManifestManager::class);
        if (!File::isDirectory($pluginsRoot)) {
            return null;
        }

        foreach (File::directories($pluginsRoot) as $directory) {
            $folderName = basename($directory);
            $manifestPath = $directory . DIRECTORY_SEPARATOR . 'plugin.json';
            $validation = $manifestManager->readAndValidateManifestPath($manifestPath);
            $manifest = is_array($validation['manifest'] ?? null) ? $validation['manifest'] : [];
            $manifestPluginId = $manifestManager->resolvePluginId($manifest, $folderName);

            if ($manifestPluginId !== $pluginId && $folderName !== $pluginId) {
                continue;
            }

            if (!$validation['passed']) {
                return null;
            }

            return Plugin::updateOrCreate(
                ['plugin_id' => $manifestPluginId],
                [
                    'name' => $manifestManager->resolveTranslatableField($manifest['name'] ?? null, Str::headline($folderName)),
                    'description' => $manifestManager->resolveTranslatableField($manifest['description'] ?? null, ''),
                    'author' => $manifestManager->resolveTranslatableField($manifest['author'] ?? null, ''),
                    'version' => (string) ($manifest['version'] ?? '1.0.0'),
                    'url' => (string) ($manifest['url'] ?? ''),
                    'path' => $folderName,
                    'status' => 'inactive',
                    'remark' => null,
                ]
            );
        }

        return null;
    }

    protected function findActiveDependentPlugins(Plugin $plugin): array
    {
        $targetPluginId = (string) $plugin->plugin_id;
        if ($targetPluginId === '') {
            return [];
        }

        $activePlugins = Plugin::query()
            ->where('status', 'active')
            ->where('plugin_id', '!=', $targetPluginId)
            ->orderBy('plugin_id')
            ->get();

        $dependentPlugins = [];
        foreach ($activePlugins as $activePlugin) {
            $manifestPath = $this->resolvePluginManifestPath($activePlugin);
            if (!$manifestPath || !File::exists($manifestPath)) {
                continue;
            }

            $manifest = json_decode((string) File::get($manifestPath), true);
            if (!is_array($manifest)) {
                continue;
            }

            $dependencyIds = array_map(fn ($rule) => $rule['id'], $this->normalizeDependencyRules($manifest['dependencies'] ?? []));
            if (in_array($targetPluginId, $dependencyIds, true)) {
                $dependentPlugins[] = $this->formatPluginLabel($activePlugin);
            }
        }

        return $dependentPlugins;
    }

    protected function normalizeDependencyRules($dependencies): array
    {
        if (!is_array($dependencies)) {
            return [];
        }

        $rules = [];
        foreach ($dependencies as $key => $value) {
            if (is_int($key)) {
                if (is_string($value)) {
                    $dependencyId = trim($value);
                    if ($dependencyId !== '') {
                        $rules[] = ['id' => $dependencyId, 'version' => ''];
                    }
                    continue;
                }

                if (is_array($value)) {
                    $dependencyId = trim((string) ($value['id'] ?? ''));
                    if ($dependencyId !== '') {
                        $rules[] = [
                            'id' => $dependencyId,
                            'version' => trim((string) ($value['version'] ?? '')),
                        ];
                    }
                }

                continue;
            }

            $dependencyId = trim((string) $key);
            if ($dependencyId === '') {
                continue;
            }

            $rules[] = [
                'id' => $dependencyId,
                'version' => is_string($value) || is_numeric($value) ? trim((string) $value) : '',
            ];
        }

        return $rules;
    }

    protected function formatDependencyRulesForDisplay(array $rules): string
    {
        if (empty($rules)) {
            return '-';
        }

        $labels = array_map(function ($rule) {
            $dependencyId = (string) ($rule['id'] ?? '');
            $version = trim((string) ($rule['version'] ?? ''));
            return $version === '' ? $dependencyId : $dependencyId . ' (' . $version . ')';
        }, $rules);

        return implode(', ', array_filter($labels));
    }

    protected function formatPluginLabel(Plugin $plugin): string
    {
        $pluginId = (string) $plugin->plugin_id;
        $pluginName = trim((string) $plugin->name);

        if ($pluginName !== '' && $pluginName !== $pluginId) {
            return $pluginId . ' (' . $pluginName . ')';
        }

        return $pluginId;
    }
}
