<?php

namespace Wncms\Services\Core;

use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

trait LocaleMethods
{
    public function getLocale(): string
    {
        if (!app()->bound('translator')) {
            return config('app.locale', 'en');
        }
        return LaravelLocalization::getCurrentLocale();
    }

    public function isDefaultLocale(): bool
    {
        if (!app()->bound('translator')) {
            return true;
        }
        return LaravelLocalization::getCurrentLocale() == LaravelLocalization::getDefaultLocale();
    }

    public function getLocaleName(): string
    {
        if (!app()->bound('translator')) {
            return config('app.locale', 'en');
        }
        return LaravelLocalization::getCurrentLocaleNative();
    }

    public function getLocaleList(): array
    {
        if (!app()->bound('translator')) {
            return [];
        }
        return LaravelLocalization::getSupportedLocales();
    }

    public function setDefaultLocale($locale)
    {
        if (!app()->bound('translator')) {
            return;
        }
        return LaravelLocalization::setDefaultLocale($locale);
    }

    /**
     * Enable or disable locale alias mapping at runtime.
     * Pass [] to fully disable mapping.
     */
    public function setLocalesMapping(array $mapping): void
    {
        if (!app()->bound('translator')) {
            return;
        }
        LaravelLocalization::setLocalesMapping($mapping);
    }
}
