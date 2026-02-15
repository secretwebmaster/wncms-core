<?php

namespace Wncms\Plugins;

use Illuminate\Support\Facades\File;
use ReflectionClass;
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

    public function getManifestVersion(Plugin $plugin): ?string
    {
        $pluginRootPath = $this->resolvePluginRootPath($plugin);
        if (!$pluginRootPath || !File::isDirectory($pluginRootPath)) {
            return null;
        }

        $manifest = $this->readManifest($pluginRootPath);
        $manifestVersion = $this->normalizeVersion((string) ($manifest['version'] ?? ''));

        return $manifestVersion === '' ? null : $manifestVersion;
    }

    public function upgradePlugin(Plugin $plugin): array
    {
        try {
            $instance = $this->resolveInstance($plugin);
            if (!$instance) {
                return [
                    'passed' => false,
                    'changed' => false,
                    'message' => 'plugin class not found',
                    'from_version' => $this->normalizeVersion((string) ($plugin->version ?? '')),
                    'to_version' => $this->normalizeVersion((string) ($plugin->version ?? '')),
                ];
            }

            $installedVersion = $this->normalizeVersion((string) ($plugin->version ?? ''));
            $availableVersion = $this->normalizeVersion((string) ($this->getManifestVersion($plugin) ?? ''));

            if ($installedVersion === '' || $availableVersion === '') {
                return [
                    'passed' => false,
                    'changed' => false,
                    'message' => 'plugin installed/available version is invalid',
                    'from_version' => $installedVersion,
                    'to_version' => $availableVersion,
                ];
            }

            if (version_compare($availableVersion, $installedVersion, '<=')) {
                return [
                    'passed' => true,
                    'changed' => false,
                    'message' => '',
                    'from_version' => $installedVersion,
                    'to_version' => $availableVersion,
                ];
            }

            $upgradeMap = $this->resolveUpgradeMap($instance);
            $steps = $this->resolveUpgradeSteps($upgradeMap, $installedVersion, $availableVersion);
            if (empty($steps)) {
                return [
                    'passed' => false,
                    'changed' => false,
                    'message' => 'no upgrade steps defined for target version',
                    'from_version' => $installedVersion,
                    'to_version' => $availableVersion,
                ];
            }

            $currentVersion = $installedVersion;
            foreach ($steps as $stepVersion => $relativeFilePath) {
                $this->runUpgradeStepFile($plugin, $instance, $relativeFilePath, [
                    'plugin' => $plugin,
                    'plugin_id' => (string) $plugin->plugin_id,
                    'installed_version' => $installedVersion,
                    'available_version' => $availableVersion,
                    'from_version' => $currentVersion,
                    'to_version' => $stepVersion,
                    'step_version' => $stepVersion,
                ]);

                $currentVersion = $stepVersion;
            }

            $plugin->forceFill([
                'version' => $availableVersion,
            ])->save();

            return [
                'passed' => true,
                'changed' => true,
                'message' => '',
                'from_version' => $installedVersion,
                'to_version' => $availableVersion,
            ];
        } catch (Throwable $e) {
            return [
                'passed' => false,
                'changed' => false,
                'message' => $e->getMessage(),
                'from_version' => $this->normalizeVersion((string) ($plugin->version ?? '')),
                'to_version' => $this->normalizeVersion((string) ($this->getManifestVersion($plugin) ?? '')),
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

    protected function normalizeVersion(string $version): string
    {
        $normalized = ltrim(trim($version), 'vV');

        if ($normalized === '') {
            return '';
        }

        if (!preg_match('/^\d+(?:\.\d+){1,2}(?:[-+][A-Za-z0-9\.-]+)?$/', $normalized)) {
            return '';
        }

        return $normalized;
    }

    protected function resolveUpgradeMap(PluginInterface $instance): array
    {
        $reflection = new ReflectionClass($instance);
        if (!$reflection->hasProperty('upgrades')) {
            return [];
        }

        $property = $reflection->getProperty('upgrades');
        $property->setAccessible(true);
        $value = $property->getValue($instance);

        if (!is_array($value)) {
            throw new RuntimeException('plugin upgrades definition must be an array');
        }

        $normalized = [];
        foreach ($value as $toVersion => $filePath) {
            $normalizedVersion = $this->normalizeVersion((string) $toVersion);
            if ($normalizedVersion === '') {
                throw new RuntimeException("invalid upgrade target version: {$toVersion}");
            }

            if (!is_string($filePath) && !is_numeric($filePath)) {
                throw new RuntimeException("invalid upgrade file for version {$normalizedVersion}");
            }

            $normalizedFilePath = trim((string) $filePath);
            if ($normalizedFilePath === '' || str_contains($normalizedFilePath, '..')) {
                throw new RuntimeException("invalid upgrade file path for version {$normalizedVersion}");
            }

            if (isset($normalized[$normalizedVersion])) {
                throw new RuntimeException("duplicate upgrade target version: {$normalizedVersion}");
            }

            $normalized[$normalizedVersion] = str_replace('\\\\', '/', ltrim($normalizedFilePath, '/'));
        }

        return $normalized;
    }

    protected function resolveUpgradeSteps(array $upgradeMap, string $installedVersion, string $availableVersion): array
    {
        if (empty($upgradeMap)) {
            return [];
        }

        uksort($upgradeMap, 'version_compare');

        $steps = [];
        foreach ($upgradeMap as $toVersion => $filePath) {
            if (version_compare($toVersion, $installedVersion, '<=') || version_compare($toVersion, $availableVersion, '>')) {
                continue;
            }

            $steps[$toVersion] = $filePath;
        }

        if (empty($steps)) {
            return [];
        }

        $lastStepVersion = array_key_last($steps);
        if (!is_string($lastStepVersion) || version_compare($lastStepVersion, $availableVersion, '!=')) {
            throw new RuntimeException("upgrade steps do not reach available version {$availableVersion}");
        }

        return $steps;
    }

    protected function runUpgradeStepFile(Plugin $plugin, PluginInterface $instance, string $relativeFilePath, array $context): void
    {
        $pluginRootPath = $this->resolvePluginRootPath($plugin);
        if (!$pluginRootPath) {
            throw new RuntimeException('plugin root path not found');
        }

        $normalizedRelativeFilePath = str_replace('\\\\', '/', ltrim($relativeFilePath, '/'));
        if (!str_starts_with($normalizedRelativeFilePath, 'upgrades/')) {
            $normalizedRelativeFilePath = 'upgrades/' . $normalizedRelativeFilePath;
        }

        $filePath = rtrim($pluginRootPath, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalizedRelativeFilePath);
        if (!File::exists($filePath)) {
            throw new RuntimeException("upgrade file not found: {$normalizedRelativeFilePath}");
        }

        $upgrade = require $filePath;
        if (is_callable($upgrade)) {
            $upgrade($context, $instance, $plugin);
            return;
        }

        if (is_array($upgrade) && array_key_exists('passed', $upgrade) && empty($upgrade['passed'])) {
            $message = trim((string) ($upgrade['message'] ?? 'upgrade step failed'));
            throw new RuntimeException($message);
        }

        if ($upgrade === false) {
            throw new RuntimeException("upgrade file returned false: {$normalizedRelativeFilePath}");
        }
    }
}
