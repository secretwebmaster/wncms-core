<?php

namespace Wncms\Services\Managers;

use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class LocaleManager
{
    /**
     * Magic method to handle dynamic method calls.
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        $localization = LaravelLocalization::getFacadeRoot();

        if (method_exists($localization, $method)) {
            return $localization->$method(...$args);
        }

        throw new \BadMethodCallException("Method {$method} does not exist on " . __CLASS__);
    }
}
