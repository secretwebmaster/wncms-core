<?php

namespace Wncms\Services\Core;

trait VersionMethods
{
    public function getVersion(?string $debugType = null): string
    {
        if (!empty($debugType)) {
            $appVersion = env('APP_VERSION') ? (string) env('APP_VERSION') : '';

            if ($debugType === 'js') {
                return gss('core_version') . $appVersion . (env('JS_DEBUG') ? '.' . time() : '');
            }

            if ($debugType === 'css') {
                return gss('core_version') . $appVersion . (env('CSS_DEBUG') ? '.' . time() : '');
            }
        }

        $appDebug = env('APP_DEBUG') ? time() : '';
        return gss('core_version') . $appDebug . env('APP_VERSION');
    }

    public function addVersion(?string $debugType = null): string
    {
        return '?v=' . $this->getVersion($debugType);
    }
}
