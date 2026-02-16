<?php

namespace Wncms\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use ZipArchive;

class ThemeController 
{
    /**
     * List all theme folders from /public/themes.
     */
    public function index(Request $request)
    {
        $themes = wncms()->theme()->getThemeMetas(); // ['demo','starter']
        $invalidThemes = [];

        foreach($themes as $index => $theme) {
            if(empty($theme['id'])) {
                $invalidThemes[] = $theme;
                unset($themes[$index]);
            }
        }

        return wncms()->view('backend.themes.index', [
            'themes' => $themes,
            'invalidThemes' => $invalidThemes,
            'page_title' => wncms()->getModelWord('theme', 'management'),
        ]);
    }

    /**
     * Upload a new theme zip file.
     * Expected zip structure:
     *   demo/
     *     config.php
     *     functions.php
     *     views/
     *     assets/
     */
    public function upload(Request $request)
    {
        $request->validate([
            'theme_file' => 'required|file|mimes:zip',
        ],[
            'theme_file.mimes' => __('wncms::word.theme_upload_must_be_zip'),
            'theme_file.required' => __('wncms::word.theme_file_required'),
            'theme_file.file' => __('wncms::word.theme_file_must_be_file'),
        ]);

        $zipFile = $request->file('theme_file');
        $zip = new ZipArchive;

        if ($zip->open($zipFile->getPathname()) !== TRUE) {
            return back()->withErrors(['message' => 'Invalid ZIP file']);
        }

        // Extract to temporary location
        $tempPath = storage_path('app/theme_upload_' . time());
        mkdir($tempPath);
        $zip->extractTo($tempPath);
        $zip->close();

        // Detect themeId = folder name inside ZIP
        $folders = array_filter(glob($tempPath . '/*'), 'is_dir');

        if (empty($folders)) {
            File::deleteDirectory($tempPath);
            return back()->withErrors(['message' => 'ZIP must contain a theme folder']);
        }

        $themePath = array_values($folders)[0];
        $themeId = basename($themePath);

        // Target path
        $targetPath = public_path("themes/{$themeId}");

        // Replace existing theme folder
        if (File::exists($targetPath)) {
            File::deleteDirectory($targetPath);
        }

        // Move extracted theme to public/themes
        File::moveDirectory($themePath, $targetPath);
        File::deleteDirectory($tempPath);

        return back()->with([
            'status' => 'success',
            'message' => __('wncms::word.theme_uploaded_successfully'),
        ]);
    }

    /**
     * Delete a theme folder.
     * But only if NO website uses it.
     */
    public function delete(Request $request, $themeId)
    {
        // Check if used by any website
        $count = wncms()->getModelClass('website')::where('theme', $themeId)->count();
        if ($count > 0) {
            return back()->withErrors([
                'message' => __('wncms::word.theme_is_used_by_websites'),
            ]);
        }

        $path = public_path("themes/{$themeId}");
        if (File::exists($path)) {
            File::deleteDirectory($path);
        }

        return back()->with([
            'status' => 'success',
            'message' => __('wncms::word.theme_deleted_successfully'),
        ]);
    }

    // protected function validateThemeFiles(string $rootPath): array
    // {
    //     $configFiles = File::glob("{$rootPath}/config/theme/*.php");
    //     if (empty($configFiles)) {
    //         return ['passed' => false, 'message' => 'wncms::word.theme_missing_config'];
    //     }

    //     $configPath = $configFiles[0];
    //     $config = include $configPath;

    //     if (empty($config['info']['id'])) {
    //         return ['passed' => false, 'message' => 'wncms::word.theme_id_missing'];
    //     }

    //     $themeId = $config['info']['id'];

    //     $required = [
    //         "{$rootPath}/config/theme/{$themeId}.php",
    //         "{$rootPath}/resources/views/frontend/themes/{$themeId}/pages/home.blade.php",
    //     ];

    //     foreach ($required as $file) {
    //         if (!file_exists($file)) {
    //             return ['passed' => false, 'message' => 'wncms::word.theme_structure_invalid'];
    //         }
    //     }

    //     $configContent = file_get_contents("{$rootPath}/config/theme/{$themeId}.php");
    //     if (preg_match('/system\(|exec\(|shell_exec\(|eval\(/', $configContent)) {
    //         return ['passed' => false, 'message' => 'wncms::word.theme_security_violation'];
    //     }

    //     return [
    //         'passed' => true,
    //         'themeId' => $themeId,
    //         'config' => $config,
    //     ];
    // }
}
