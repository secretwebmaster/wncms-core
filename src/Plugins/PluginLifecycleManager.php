<?php

namespace Wncms\Plugins;

use Illuminate\Support\Facades\File;
use RuntimeException;
use Throwable;
use Wncms\Models\Plugin;
use Wncms\Plugins\Contracts\PluginInterface;

class PluginLifecycleManager
{
    protected array $resolvedInstances = [];

    public function __construct(protected PluginManifestManager $manifestManager)
    {
    }

    public function run(Plugin $plugin, string $method): array
    {
        try {
            $instance = $this->resolveInstance($plugin);

            if (!$instance) {
                return [
                    'passed' => true,
                    'message' => 'plugin class not found; skipped lifecycle call',
                ];
            }

            if (!method_exists($instance, $method)) {
                return [
                    'passed' => false,
                    'message' => "plugin lifecycle method not found: {$method}",
                ];
            }

            $instance->{$method}();

            return [
                'passed' => true,
                'message' => '',
            ];
        } catch (Throwable $e) {
            return [
                'passed' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function resolveInstance(Plugin $plugin): ?PluginInterface
    {
        $cacheKey = (string) ($plugin->plugin_id ?? '');
        if ($cacheKey !== '' && isset($this->resolvedInstances[$cacheKey])) {
            return $this->resolvedInstances[$cacheKey];
        }

        $pluginRootPath = $this->resolvePluginRootPath($plugin);
        if (!$pluginRootPath || !File::isDirectory($pluginRootPath)) {
            return null;
        }

        $manifest = $this->readManifest($pluginRootPath);
        $entry = $this->resolveEntryFile($manifest);

        $entryPath = rtrim($pluginRootPath, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $entry);

        if (!File::exists($entryPath)) {
            return null;
        }

        $entryResult = require_once $entryPath;
        $instance = $this->resolveInstanceFromEntry($entryResult, $manifest, $entry);

        if (!$instance instanceof PluginInterface) {
            throw new RuntimeException('resolved plugin instance must implement ' . PluginInterface::class);
        }

        if ($instance instanceof AbstractPlugin) {
            $instance->setContext($plugin, $manifest, $pluginRootPath);
        }

        if ($cacheKey !== '') {
            $this->resolvedInstances[$cacheKey] = $instance;
        }

        return $instance;
    }

    public function resolvePluginRootPath(Plugin $plugin): ?string
    {
        $pluginsRoot = config('filesystems.disks.plugins.root', public_path('plugins'));
        $pluginPath = trim((string) $plugin->path);

        if ($pluginPath === '') {
            return null;
        }

        $pluginPath = str_replace('\\\\', '/', $pluginPath);
        $pluginPath = trim($pluginPath, '/');

        if (str_starts_with($pluginPath, 'plugins/')) {
            $pluginPath = substr($pluginPath, strlen('plugins/'));
        }

        if (str_ends_with($pluginPath, '.zip')) {
            $pluginPath = substr($pluginPath, 0, -4);
        }

        if ($pluginPath === '') {
            return null;
        }

        return rtrim($pluginsRoot, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $pluginPath);
    }

    protected function readManifest(string $pluginRootPath): array
    {
        $manifestPath = $pluginRootPath . DIRECTORY_SEPARATOR . 'plugin.json';
        $result = $this->manifestManager->readManifestPath($manifestPath);

        return $result['passed'] ? $result['manifest'] : [];
    }

    protected function resolveEntryFile(array $manifest): string
    {
        $entry = trim((string) ($manifest['entry'] ?? 'Plugin.php'));

        if ($entry === '' || str_contains($entry, '..')) {
            return 'Plugin.php';
        }

        return ltrim(str_replace('\\\\', '/', $entry), '/');
    }

    protected function resolveInstanceFromEntry($entryResult, array $manifest, string $entry): PluginInterface
    {
        if ($entryResult instanceof PluginInterface) {
            return $entryResult;
        }

        if (is_string($entryResult) && class_exists($entryResult)) {
            $instance = app()->make($entryResult);
            if ($instance instanceof PluginInterface) {
                return $instance;
            }
        }

        $className = trim((string) ($manifest['class'] ?? ''));
        if (!$className) {
            throw new RuntimeException("plugin.json class is required when {$entry} does not return a plugin instance");
        }

        if (!class_exists($className)) {
            throw new RuntimeException("plugin class [{$className}] not found in {$entry}");
        }

        $instance = app()->make($className);
        if (!$instance instanceof PluginInterface) {
            throw new RuntimeException("plugin class [{$className}] must implement " . PluginInterface::class);
        }

        return $instance;
    }
}
