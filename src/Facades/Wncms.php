<?php

namespace Wncms\Facades;

use Illuminate\Support\Facades\Facade;

class Wncms extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'wncms';
    }
}
