<?php

namespace Wncms\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use ZipArchive;
use Wncms\Models\Website;

class ThemeController extends Controller
{
    public function index()
    {
        $themes = collect(glob(resource_path('views/frontend/themes/*')))
            ->filter(fn($theme) => is_dir($theme))
            ->map(function ($theme) {
                $themeId = basename($theme);
                $configPath = config_path("themes/{$themeId}.php");

                if (!file_exists($configPath)) {
                    return [
                        'id' => $themeId,
                        'name' => $themeId,
                        'isValid' => false,
                    ];
                }

                $config = include $configPath;
                $info = $config['info'] ?? [];

                foreach ($info as $key => $value) {
                    if (is_array($value)) {
                        $info[$key] = $value[app()->getLocale()] ?? collect($value)->first();
                    }
                }

                $info['id'] = $themeId;
                $info['isValid'] = true;
                return $info;
            });

        return view('wncms::backend.themes.index', [
            'themes' => $themes,
            'activatedThemeIds' => [],
            'page_title' => __('wncms::word.theme_list'),
        ]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'theme_file' => 'required|file|mimes:zip'
        ], [
            'theme_file.required' => __('wncms::word.please_select_a_theme_file'),
            'theme_file.mimes' => __('wncms::word.please_select_a_valid_theme_file'),
        ]);

        $file = $request->file('theme_file');
        $filename = $file->getClientOriginalName();
        $zipPath = storage_path("app/temp/theme/{$filename}");
        $extractPath = storage_path("app/temp/theme/" . pathinfo($filename, PATHINFO_FILENAME));

        $file->storeAs('temp/theme', $filename);

        $zip = new ZipArchive;
        if ($zip->open($zipPath) === TRUE) {
            $zip->extractTo($extractPath);
            $zip->close();
            unlink($zipPath);
        } else {
            return redirect()->back()->with('error_message', __('wncms::word.unable_to_unzip_theme_file'));
        }

        $rootPath = $this->getRootThemePath($extractPath);
        $validation = $this->validateThemeFiles($rootPath);

        if (!$validation['passed']) {
            return redirect()->back()->with('error_message', __($validation['message']));
        }

        $themeId = $validation['themeId'];
        $config = $validation['config'];

        $themeViewPath = resource_path("views/frontend/themes/{$themeId}");
        $configTargetPath = config_path("themes/{$themeId}.php");
        $assetsSource = "{$rootPath}/public/themes/{$themeId}";
        $assetsTarget = public_path("themes/{$themeId}");

        if (file_exists($configTargetPath)) {
            $existing = include $configTargetPath;
            $newVersion = $config['info']['version'] ?? '0.0.0';
            $currentVersion = $existing['info']['version'] ?? '0.0.0';

            if (version_compare($newVersion, $currentVersion, '<=')) {
                return redirect()->back()->with('error_message', __('wncms::word.the_same_theme_with_same_or_higher_version_exists'));
            }
        }

        File::ensureDirectoryExists(dirname($configTargetPath));
        File::copy("{$rootPath}/config/theme/{$themeId}.php", $configTargetPath);
        File::copyDirectory("{$rootPath}/resources/views/frontend/themes/{$themeId}", $themeViewPath);

        if (is_dir($assetsSource)) {
            File::copyDirectory($assetsSource, $assetsTarget);
        }

        // Copy language files if present
        $langSource = "{$rootPath}/lang";
        if (is_dir($langSource)) {
            foreach (File::directories($langSource) as $localeDir) {
                $locale = basename($localeDir);
                $targetLangPath = lang_path($locale);
                File::ensureDirectoryExists($targetLangPath);

                foreach (File::files($localeDir) as $file) {
                    File::copy($file->getPathname(), "{$targetLangPath}/" . $file->getFilename());
                }
            }
        }

        File::deleteDirectory($extractPath);

        return redirect()->route('themes.index')->with([
            'message' => __('wncms::word.theme_uploaded_successfully'),
            'themeId' => $themeId
        ]);
    }

    public function delete($themeId)
    {
        if (in_array($themeId, ['default', 'starter'])) {
            return redirect()->back()->with('error_message', __('wncms::word.cannot_delete_default_themes'));
        }

        if (Website::where('theme', $themeId)->exists()) {
            return redirect()->back()->with('error_message', __('wncms::word.theme_in_use_cannot_delete'));
        }

        File::delete(config_path("themes/{$themeId}.php"));
        File::deleteDirectory(resource_path("views/frontend/themes/{$themeId}"));
        File::deleteDirectory(public_path("themes/{$themeId}"));

        return redirect()->back()->with('message', __('wncms::word.theme_deleted_successfully'));
    }

    protected function getRootThemePath($extractPath)
    {
        $folders = collect(glob($extractPath . '/*'))->filter(fn($folder) => is_dir($folder));
        return $folders->count() === 1 ? $folders->first() : $extractPath;
    }

    protected function validateThemeFiles(string $rootPath): array
    {
        $configFiles = File::glob("{$rootPath}/config/theme/*.php");
        if (empty($configFiles)) {
            return ['passed' => false, 'message' => 'wncms::word.theme_missing_config'];
        }

        $configPath = $configFiles[0];
        $config = include $configPath;

        if (empty($config['info']['id'])) {
            return ['passed' => false, 'message' => 'wncms::word.theme_id_missing'];
        }

        $themeId = $config['info']['id'];

        $required = [
            "{$rootPath}/config/theme/{$themeId}.php",
            "{$rootPath}/resources/views/frontend/themes/{$themeId}/pages/home.blade.php",
        ];

        foreach ($required as $file) {
            if (!file_exists($file)) {
                return ['passed' => false, 'message' => 'wncms::word.theme_structure_invalid'];
            }
        }

        $configContent = file_get_contents("{$rootPath}/config/theme/{$themeId}.php");
        if (preg_match('/system\(|exec\(|shell_exec\(|eval\(/', $configContent)) {
            return ['passed' => false, 'message' => 'wncms::word.theme_security_violation'];
        }

        return [
            'passed' => true,
            'themeId' => $themeId,
            'config' => $config,
        ];
    }
}