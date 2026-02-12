<?php

namespace Wncms\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Wncms\Models\Plugin;

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

        $activePlugins = $this->getActivePlugins();

        foreach ($activePlugins as $plugin) {
            $this->loadPluginRoutes($plugin);
            $this->loadPluginEvent($plugin);
            $this->loadPluginFunction($plugin);
        }
    }

    protected function shouldLoadPlugins(): bool
    {
        if (!function_exists('wncms_is_installed') || !wncms_is_installed()) {
            return false;
        }

        if (!Schema::hasTable('wn_plugins')) {
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
        return Plugin::where('status', 'active')->get();
    }

    public function loadPluginRoutes(Plugin $plugin)
    {
        $routeFile = app_path('Plugins/' . $plugin->path . '/routes/web.php');

        if (file_exists($routeFile)) {
            require $routeFile;
        }
    }

    public function loadPluginEvent(Plugin $plugin)
    {
        $eventFile = app_path('Plugins/' . $plugin->path . '/system/events.php');

        if (file_exists($eventFile)) {
            require $eventFile;
        }
    }

    public function loadPluginFunction(Plugin $plugin)
    {
        $functionFile = app_path('Plugins/' . $plugin->path . '/system/functions.php');

        if (file_exists($functionFile)) {
            require $functionFile;
        }
    }
}
