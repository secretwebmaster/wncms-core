<?php

namespace Wncms\Services\Core;

trait ViewMethods
{
    protected array $viewWidgets = [];

    public function view(string $name, array $params = [], ?string $fallbackView = null, ?string $fallbackRoute = null)
    {
        if (view()->exists($name)) {
            return view($name, $params);
        }

        $defaultView = 'wncms::' . $name;
        if (view()->exists($defaultView)) {
            return view($defaultView, $params);
        }

        if ($fallbackView && view()->exists($fallbackView)) {
            return view($fallbackView, $params);
        }

        if ($fallbackRoute && route($fallbackRoute, [], false)) {
            return redirect()->route($fallbackRoute);
        }

        wncms()->log("View not found: {$name}");
        abort(404, "View [{$name}] not found.");
    }

    // /**
    //  * Register a dashboard widget.
    //  *
    //  * @param string $view
    //  * @param array $data
    //  * @return void
    //  */
    // public function registerDashboardWidget(string $view, array $data = []): void
    // {
    //     $this->dashboardWidgets[] = [
    //         'view' => $view,
    //         'data' => $data,
    //     ];
    // }

    // /**
    //  * Get registered dashboard widgets.
    //  *
    //  * @return array
    //  */
    // public function getDashboardWidgets(): array
    // {
    //     return $this->dashboardWidgets;
    // }

    /**
     * Register a view widget to a specific injection key.
     *
     * @param string $key
     * @param string $view
     * @param array $data
     * @return void
     */
    public function registerViewWidget(string $key, string $view, array $data = []): void
    {
        if (! isset($this->viewWidgets[$key])) {
            $this->viewWidgets[$key] = [];
        }

        $this->viewWidgets[$key][] = [
            'view' => $view,
            'data' => $data,
        ];
    }

    /**
     * Get all widgets registered under a specific key.
     *
     * @param string $key
     * @return array
     */
    public function getViewWidgets(string $key): array
    {
        return $this->viewWidgets[$key] ?? [];
    }

    /**
     * Remove all widgets for a specific key.
     */
    public function clearViewWidgets(string $key): void
    {
        unset($this->viewWidgets[$key]);
    }
}
