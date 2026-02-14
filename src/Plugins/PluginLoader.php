<?php

namespace Wncms\Plugins;

use RuntimeException;
use Throwable;
use Wncms\Models\Plugin;
use Wncms\Plugins\Contracts\PluginInterface;

class PluginLoader
{
    public function __construct(protected PluginLifecycleManager $lifecycleManager)
    {
    }

    public function load(string $pluginId): PluginInterface
    {
        $pluginId = trim($pluginId);
        if ($pluginId === '') {
            throw new RuntimeException('plugin id is required');
        }

        $plugin = Plugin::query()->where('plugin_id', $pluginId)->first();
        if (!$plugin) {
            throw new RuntimeException("plugin [{$pluginId}] not found");
        }

        $instance = $this->lifecycleManager->resolveInstance($plugin);
        if (!$instance) {
            throw new RuntimeException("plugin [{$pluginId}] instance could not be resolved");
        }

        return $instance;
    }

    public function loadAbstract(string $pluginId): AbstractPlugin
    {
        $instance = $this->load($pluginId);
        if (!$instance instanceof AbstractPlugin) {
            throw new RuntimeException("plugin [{$pluginId}] must extend " . AbstractPlugin::class);
        }

        return $instance;
    }

    public function tryLoad(string $pluginId): ?PluginInterface
    {
        try {
            return $this->load($pluginId);
        } catch (Throwable $e) {
            return null;
        }
    }

    public function tryLoadAbstract(string $pluginId): ?AbstractPlugin
    {
        $instance = $this->tryLoad($pluginId);

        return $instance instanceof AbstractPlugin ? $instance : null;
    }
}
