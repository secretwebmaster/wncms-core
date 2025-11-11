<?php

namespace Wncms\Services\Core;

trait ViewMethods
{
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
}
