<?php

namespace Wncms\Http\Controllers\Backend;

use Wncms\Http\Controllers\Controller;
use Wncms\Models\Plugin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
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
            $display = $this->resolveDisplayFieldsFromManifest($plugin);
            $plugin->display_name = $display['name'] ?: (string) $plugin->name;
            $plugin->display_description = $display['description'] ?: (string) $plugin->description;
            $plugin->display_author = $display['author'] ?: (string) $plugin->author;

            return $plugin;
        });

        return $this->view('backend.plugins.index', [
            'page_title' => wncms_model_word('plugin', 'management'),
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
        $lifecycle = app(PluginLifecycleManager::class)->run($plugin, 'activate');
        if (!$lifecycle['passed']) {
            $plugin->update(['remark' => '[LIFECYCLE_ERROR] ' . mb_substr((string) $lifecycle['message'], 0, 300)]);
            return redirect()->route('plugins.index')->withErrors(['message' => 'Plugin activate failed: ' . $lifecycle['message']]);
        }

        $plugin->update(['status' => 'active']);

        return redirect()->route('plugins.index')->with('success', 'Plugin activated successfully!');
    }

    public function deactivate(Plugin $plugin)
    {
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

    protected function resolveDisplayFieldsFromManifest(Plugin $plugin): array
    {
        $manifestPath = $this->resolvePluginManifestPath($plugin);
        if (!$manifestPath || !File::exists($manifestPath)) {
            return [
                'name' => (string) $plugin->name,
                'description' => (string) $plugin->description,
                'author' => (string) $plugin->author,
            ];
        }

        $manifest = json_decode((string) File::get($manifestPath), true);
        if (!is_array($manifest)) {
            return [
                'name' => (string) $plugin->name,
                'description' => (string) $plugin->description,
                'author' => (string) $plugin->author,
            ];
        }

        return [
            'name' => $this->resolveTranslatableManifestField($manifest['name'] ?? null, (string) $plugin->name),
            'description' => $this->resolveTranslatableManifestField($manifest['description'] ?? null, (string) $plugin->description),
            'author' => $this->resolveTranslatableManifestField($manifest['author'] ?? null, (string) $plugin->author),
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
}
