<?php

namespace Wncms\Services\Core;

use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

trait LocaleMethods
{
    public function getLocale(): string
    {
        return LaravelLocalization::getCurrentLocale();
    }

    public function isDefaultLocale(): bool
    {
        return LaravelLocalization::getCurrentLocale() == LaravelLocalization::getDefaultLocale();
    }

    public function getLocaleName(): string
    {
        return LaravelLocalization::getCurrentLocaleNative();
    }

    public function getLocaleList(): array
    {
        return LaravelLocalization::getSupportedLocales();
    }
}
