<?php

namespace Wncms\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;
use Throwable;
use Wncms\Models\Plugin;
use Wncms\Plugins\PluginLifecycleManager;
use Wncms\Plugins\PluginManifestManager;

class PluginServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register services if needed
    }

    public function boot()
    {
        if (!$this->shouldLoadPlugins()) {
            return;
        }

        $activePlugins = $this->getOrderedActivePlugins();
        $pluginLifecycleManager = app(PluginLifecycleManager::class);
        $manifestManager = app(PluginManifestManager::class);

        foreach ($activePlugins as $plugin) {
            $validation = $this->validatePluginManifest($plugin, $manifestManager);
            if (!$validation['passed']) {
                logger()->warning('Plugin manifest validation failed', [
                    'plugin_id' => $plugin->plugin_id,
                    'path' => $plugin->path,
                    'message' => $validation['message'],
                ]);
                $this->markPluginLoadFailure($plugin, $validation['message']);
                continue;
            }

            try {
                $this->loadPluginTranslations($plugin);
                $this->loadPluginRoutes($plugin);
                $this->loadPluginEvent($plugin);
                $this->loadPluginFunction($plugin);
                $lifecycleResult = $pluginLifecycleManager->run($plugin, 'init');
                if (!$lifecycleResult['passed']) {
                    throw new \RuntimeException($lifecycleResult['message']);
                }
                $this->clearPluginLoadFailure($plugin);
                logger()->debug('Plugin loaded successfully', [
                    'plugin_id' => $plugin->plugin_id,
                    'path' => $plugin->path,
                ]);
            } catch (Throwable $e) {
                logger()->error('Plugin load failed', [
                    'plugin_id' => $plugin->plugin_id,
                    'path' => $plugin->path,
                    'error' => $e->getMessage(),
                ]);
                $this->markPluginLoadFailure($plugin, $e->getMessage());
            }
        }
    }

    protected function shouldLoadPlugins(): bool
    {
        if (!function_exists('wncms_is_installed') || !wncms_is_installed()) {
            return false;
        }

        if (!Schema::hasTable('plugins')) {
            return false;
        }

        if (app()->runningInConsole()) {
            $cmd = $_SERVER['argv'][1] ?? '';
            if (str_starts_with($cmd, 'migrate') || $cmd === 'db:seed') {
                return false;
            }
        }

        return true;
    }

    public function getActivePlugins()
    {
        return Plugin::where('status', 'active')
            ->orderBy('plugin_id')
            ->orderBy('id')
            ->get();
    }

    public function getOrderedActivePlugins()
    {
        $plugins = $this->getActivePlugins();
        $manifestMap = [];

        foreach ($plugins as $plugin) {
            $manifestPath = $this->resolvePluginPath($plugin, 'plugin.json');
            $manifest = [];
            if ($manifestPath && File::exists($manifestPath)) {
                $decoded = json_decode((string) File::get($manifestPath), true);
                if (is_array($decoded)) {
                    $manifest = $decoded;
                }
            }

            $manifestMap[(string) $plugin->plugin_id] = $manifest;
        }

        $pluginsArray = $plugins->all();
        usort($pluginsArray, function ($a, $b) use ($manifestMap) {
            $aId = (string) $a->plugin_id;
            $bId = (string) $b->plugin_id;

            $aDependsOnB = $this->pluginDependsOn($aId, $bId, $manifestMap);
            $bDependsOnA = $this->pluginDependsOn($bId, $aId, $manifestMap);

            if ($aDependsOnB && !$bDependsOnA) {
                return 1;
            }

            if ($bDependsOnA && !$aDependsOnB) {
                return -1;
            }

            $aPriority = $this->getPluginPriority($manifestMap[$aId] ?? []);
            $bPriority = $this->getPluginPriority($manifestMap[$bId] ?? []);

            if ($aPriority !== $bPriority) {
                return $aPriority <=> $bPriority;
            }

            return $aId <=> $bId;
        });

        return collect($pluginsArray);
    }

    public function loadPluginRoutes(Plugin $plugin)
    {
        $routeFile = $this->resolvePluginPath($plugin, 'routes/web.php');

        if ($routeFile && file_exists($routeFile)) {
            require $routeFile;
        }
    }

    public function loadPluginEvent(Plugin $plugin)
    {
        $eventFile = $this->resolvePluginPath($plugin, 'system/events.php');

        if ($eventFile && file_exists($eventFile)) {
            require $eventFile;
        }
    }

    public function loadPluginFunction(Plugin $plugin)
    {
        $functionFile = $this->resolvePluginPath($plugin, 'system/functions.php');

        if ($functionFile && file_exists($functionFile)) {
            require $functionFile;
        }
    }

    public function loadPluginTranslations(Plugin $plugin): void
    {
        $langPath = $this->resolvePluginPath($plugin, 'lang');

        if (!$langPath || !is_dir($langPath)) {
            return;
        }

        $namespace = trim((string) $plugin->plugin_id);
        if ($namespace === '') {
            return;
        }

        $this->loadTranslationsFrom($langPath, $namespace);
    }

    protected function resolvePluginPath(Plugin $plugin, string $relativePath): ?string
    {
        $pluginsRoot = config('filesystems.disks.plugins.root', public_path('plugins'));
        $pluginPath = trim((string) $plugin->path);

        if ($pluginPath === '') {
            return null;
        }

        $pluginPath = str_replace('\\', '/', $pluginPath);
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

        $pluginRootPath = rtrim($pluginsRoot, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $pluginPath);

        return $pluginRootPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    }

    protected function validatePluginManifest(Plugin $plugin, PluginManifestManager $manifestManager): array
    {
        $manifestPath = $this->resolvePluginPath($plugin, 'plugin.json');
        if (!$manifestPath) {
            return [
                'passed' => false,
                'message' => 'plugin.json not found',
            ];
        }

        $read = $manifestManager->readAndValidateManifestPath($manifestPath);
        if (!$read['passed']) {
            return [
                'passed' => false,
                'message' => (string) $read['message'],
            ];
        }

        $manifest = $read['manifest'];

        if (!empty($plugin->plugin_id) && (string) $manifest['id'] !== (string) $plugin->plugin_id) {
            return [
                'passed' => false,
                'message' => 'plugin.json id does not match plugin_id',
            ];
        }

        return [
            'passed' => true,
            'message' => '',
        ];
    }

    protected function pluginDependsOn(string $pluginId, string $targetPluginId, array $manifestMap, array $visited = []): bool
    {
        if (isset($visited[$pluginId])) {
            return false;
        }

        $visited[$pluginId] = true;
        $manifest = $manifestMap[$pluginId] ?? [];
        $dependencies = $this->normalizeDependencies($manifest['dependencies'] ?? []);

        if (in_array($targetPluginId, $dependencies, true)) {
            return true;
        }

        foreach ($dependencies as $dependencyId) {
            if ($this->pluginDependsOn($dependencyId, $targetPluginId, $manifestMap, $visited)) {
                return true;
            }
        }

        return false;
    }

    protected function normalizeDependencies($dependencies): array
    {
        if (!is_array($dependencies)) {
            return [];
        }

        return array_values(array_filter(array_map(function ($item) {
            if (!is_string($item)) {
                return null;
            }

            $id = trim($item);
            return $id === '' ? null : $id;
        }, $dependencies)));
    }

    protected function getPluginPriority(array $manifest): int
    {
        if (!isset($manifest['priority']) || !is_numeric($manifest['priority'])) {
            return 100;
        }

        return (int) $manifest['priority'];
    }

    protected function markPluginLoadFailure(Plugin $plugin, string $message): void
    {
        $errorMessage = '[LOAD_ERROR] ' . now()->toDateTimeString() . ' ' . mb_substr($message, 0, 350);
        $plugin->update(['remark' => $errorMessage]);
    }

    protected function clearPluginLoadFailure(Plugin $plugin): void
    {
        $remark = (string) $plugin->remark;
        if (str_starts_with($remark, '[LOAD_ERROR]')) {
            $plugin->update(['remark' => null]);
        }
    }
}
