<?php

namespace Wncms\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    /**
     * Fetch view
     */
    public function view(string $name, array $params = [], ?string $fallbackView = null, ?string $fallbackRoute = null)
    {
        return wncms()->view($name, $params, $fallbackView, $fallbackRoute);
    }
}
