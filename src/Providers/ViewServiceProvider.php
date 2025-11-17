<?php

namespace Wncms\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class ViewServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerViewWidgetDirective();
    }

    /**
     * Register @widget('key') directive
     */
    protected function registerViewWidgetDirective(): void
    {
        Blade::directive('widget', function ($expression) {
            return "<?php foreach(wncms()->getViewWidgets($expression) as \$__widget) {
                echo view(\$__widget['view'], \$__widget['data'])->render();
            } ?>";
        });
    }
}
