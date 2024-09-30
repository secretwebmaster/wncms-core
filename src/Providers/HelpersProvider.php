<?php

namespace Wncms\Providers;

use Illuminate\Support\ServiceProvider;

class HelpersProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        if ($handle = opendir(__DIR__.'/../../helpers')) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != '.' && $entry != '..' && substr($entry, -4, 4) == '.php') {
                    require_once(__DIR__.'/../../helpers/' . $entry);
                }
            }
            closedir($handle);
        }
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
