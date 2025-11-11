<?php

namespace Wncms\Services\Core;

trait PathMethods
{
    public function getPackagePath(string $path = ''): string|false
    {
        return realpath(__DIR__ . '/../' . $path);
    }

    public function getPackageRootPath(string $path = ''): string|false
    {
        return realpath(__DIR__ . '/../../' . $path);
    }
}
