<?php

namespace Wncms\Providers;

use Wncms\Models\Plugin;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class PluginServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Register services if needed
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if(wncms_is_installed()){
            $activePlugins = $this->getActivePlugins();

            foreach ($activePlugins as $plugin) {
                $this->loadPluginRoutes($plugin);
                $this->loadPluginEvent($plugin);
                $this->loadPluginFunction($plugin);
            }
        }
    }

    // Get active plugins
    public function getActivePlugins()
    {
        return Plugin::where('status', 'active')->get();
    }

    // Load the plugin routes
    public function loadPluginRoutes(Plugin $plugin)
    {
        $routeFile = app_path('Plugins/' . $plugin->path . '/routes/web.php');

        if (file_exists($routeFile)) {
            require $routeFile;
        }
    }

    // Load the plugin events
    public function loadPluginEvent(Plugin $plugin)
    {
        $eventFile = app_path('Plugins/' . $plugin->path . '/system/events.php');

        if (file_exists($eventFile)) {
            require $eventFile;
        }
    }

    // Load the plugin functions
    public function loadPluginFunction(Plugin $plugin)
    {
        $functionFile = app_path('Plugins/' . $plugin->path . '/system/functions.php');

        if (file_exists($functionFile)) {
            require $functionFile;
        }
    }
}
