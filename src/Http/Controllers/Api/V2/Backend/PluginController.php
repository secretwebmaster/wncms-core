<?php

namespace Wncms\Http\Controllers\Api\V2\Backend;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Wncms\Models\Plugin;
use Wncms\Plugins\PluginManifestManager;

class PluginController extends ApiV2Controller
{
    public function index(Request $request)
    {
        try {
            $plugins = Plugin::query()
                ->when($request->boolean('show_broken'), function ($q) {
                    $q->where(function ($q2) {
                        $q2->where('remark', 'like', '[MANIFEST_ERROR]%')
                            ->orWhere('remark', 'like', '[LOAD_ERROR]%');
                    });
                })
                ->orderBy('plugin_id')
                ->orderBy('id')
                ->get()
                ->map(function (Plugin $plugin) {
                    $loadError = Plugin::extractLoadErrorDiagnostics((string) $plugin->remark);
                    return [
                        'id' => $plugin->id,
                        'plugin_id' => $plugin->plugin_id,
                        'name' => $plugin->name,
                        'description' => $plugin->description,
                        'author' => $plugin->author,
                        'version' => $plugin->version,
                        'status' => $plugin->status,
                        'path' => $plugin->path,
                        'url' => $plugin->url,
                        'remark' => $plugin->remark,
                        'last_load_error' => $loadError['last_load_error'],
                        'source_file' => $loadError['source_file'],
                    ];
                })
                ->values();

            $rawPlugins = $this->buildRawPluginsFromDirectory(
                $request->boolean('show_broken'),
                $plugins->pluck('plugin_id')->all(),
            );

            return $this->ok([
                'plugins' => $plugins,
                'raw_plugins' => $rawPlugins,
            ]);
        } catch (\Throwable $e) {
            return $this->fromThrowable($e);
        }
    }

    protected function buildRawPluginsFromDirectory(bool $showBroken, array $existingIds): array
    {
        $pluginsRoot = config('filesystems.disks.plugins.root', public_path('plugins'));
        $manifestManager = app(PluginManifestManager::class);
        if (!File::isDirectory($pluginsRoot)) {
            return [];
        }

        $raw = [];
        foreach (File::directories($pluginsRoot) as $directory) {
            $folderName = basename($directory);
            $manifestPath = $directory . DIRECTORY_SEPARATOR . 'plugin.json';
            $validation = $manifestManager->readAndValidateManifestPath($manifestPath);
            $manifest = is_array($validation['manifest'] ?? null) ? $validation['manifest'] : [];
            $pluginId = $manifestManager->resolvePluginId($manifest, $folderName);

            if (in_array($pluginId, $existingIds, true)) {
                continue;
            }

            $isBroken = !$validation['passed'];
            if ($showBroken && !$isBroken) {
                continue;
            }

            $raw[] = [
                'id' => null,
                'plugin_id' => $pluginId,
                'name' => $manifestManager->resolveTranslatableField($manifest['name'] ?? null, Str::headline($folderName)),
                'description' => $manifestManager->resolveTranslatableField($manifest['description'] ?? null, ''),
                'author' => $manifestManager->resolveTranslatableField($manifest['author'] ?? null, ''),
                'version' => (string) ($manifest['version'] ?? '1.0.0'),
                'status' => 'inactive',
                'path' => $folderName,
                'remark' => $isBroken
                    ? '[MANIFEST_ERROR] ' . (string) ($validation['message'] ?? __('wncms::word.error'))
                    : null,
                'raw' => true,
            ];
        }

        return $raw;
    }
}
