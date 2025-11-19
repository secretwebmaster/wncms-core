<?php

namespace Wncms\Services\Core;

trait VersionMethods
{
    public function getVersion(?string $debugType = null): string
    {
        $coreVersion = (string) gss('core_version');
        $appVersion  = env('APP_VERSION', '');
        $timestamp   = time();

        $versionSuffix = $appVersion !== '' ? '.' . $appVersion : '';

        $needTimestamp = false;

        if (!empty($debugType)) {
            $needTimestamp = match ($debugType) {
                'js'  => env('JS_DEBUG', false),
                'css' => env('CSS_DEBUG', false),
                default => false,
            };
        } else {
            $needTimestamp = env('APP_DEBUG', false);
        }

        return $coreVersion
            . $versionSuffix
            . ($needTimestamp ? '.' . $timestamp : '');
    }

    public function addVersion(?string $debugType = null): string
    {
        return '?v=' . $this->getVersion($debugType);
    }
}
