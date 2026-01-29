<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

$thisVersion = '6.1.7';

info("running update_{$thisVersion}.php");

try {
    $themeIds = ['starter', 'default'];

    // delete deprecated config files: /config/theme/{themeId}.php
    foreach ($themeIds as $themeId) {
        $configFile = base_path("config/theme/{$themeId}.php");
        if (File::exists($configFile)) {
            File::delete($configFile);
        }
    }

    // delete /config/theme if empty
    $themeConfigDir = base_path('config/theme');
    if (File::isDirectory($themeConfigDir)) {
        $hasFiles = count(File::files($themeConfigDir)) > 0;
        $hasDirs = count(File::directories($themeConfigDir)) > 0;

        if (!$hasFiles && !$hasDirs) {
            if (!rmdir($themeConfigDir)) {
                info("failed to remove empty directory: {$themeConfigDir}");
            }
        }
    }

    // delete old views for starter and default themes
    $realViewsBase = realpath(resource_path('views/frontend/theme')) ?: resource_path('views/frontend/theme');
    foreach ($themeIds as $themeId) {
        $viewDir = resource_path("views/frontend/theme/{$themeId}");
        if (File::isDirectory($viewDir)) {
            $realViewDir = realpath($viewDir) ?: $viewDir;

            // safety guard: only allow deletes inside resources/views/frontend/theme
            if (str_starts_with($realViewDir, $realViewsBase . DIRECTORY_SEPARATOR)) {
                File::deleteDirectory($viewDir);
            } else {
                info("skip deleting viewDir due to safety guard: {$viewDir}");
            }
        }
    }

    // delete old public assets for starter and default themes
    foreach ($themeIds as $themeId) {
        $assetDir = public_path("themes/{$themeId}");

        if (File::isDirectory($assetDir)) {
            $realAssetDir = realpath($assetDir) ?: $assetDir;
            $realThemesBase = realpath(public_path('themes')) ?: public_path('themes');

            // safety guard: only allow deletes inside /public/themes
            if (str_starts_with($realAssetDir, $realThemesBase . DIRECTORY_SEPARATOR)) {
                File::deleteDirectory($assetDir);
            } else {
                info("skip deleting assetDir due to safety guard: {$assetDir}");
            }
        }
    }

    // publish new assets
    $exitCode = Artisan::call('vendor:publish', [
        '--tag' => 'wncms-theme-assets',
        '--force' => true,
    ]);

    info("vendor:publish exitCode={$exitCode}");
    $output = trim(Artisan::output());
    if ($output !== '') {
        info("vendor:publish output:\n" . $output);
    }

    if ($exitCode !== 0) {
        info("vendor:publish failed, aborting update_{$thisVersion}.php");
        return;
    }

    uss('core_version', $thisVersion);
    info("completed update_{$thisVersion}.php");
} catch (\Throwable $e) {
    info("error when running update_{$thisVersion}.php");
    info("Error: " . $e->getMessage());
    return;
}
