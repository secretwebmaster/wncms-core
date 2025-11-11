<?php

namespace Wncms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ZipArchive;

class PackThemeFile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wncms:pack-theme-file {themeName} {--output=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pack theme files into a zip archive';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $themeName = $this->argument('themeName');

        if (empty($themeName)) {
            $this->error('Theme name is not set.');
            return;
        }

        $outputDir = $this->option('output') ?: storage_path("app/backups/{$themeName}");
        $uuid = date("YmdHis");
        $zipFileName = "{$themeName}_{$uuid}.zip";
        $zipFilePath = "{$outputDir}/{$zipFileName}";

        if (!is_dir(dirname($zipFilePath))) {
            File::makeDirectory(dirname($zipFilePath), 0755, true, true);
        }

        // Collect lang files from all locales
        $themeLangFiles = collect(File::directories(lang_path()))
            ->map(function ($localePath) use ($themeName) {
                $locale = basename($localePath);
                $file = "{$localePath}/{$themeName}.php";
                return File::exists($file) ? $file : null;
            })
            ->filter()
            ->values()
            ->all();

        // Collect files/folders to be packed
        $themeFiles = array_merge([
            config_path("themes/{$themeName}.php"),
            public_path("themes/{$themeName}"),
            resource_path("views/frontend/themes/{$themeName}"),
        ], $themeLangFiles);

        $zip = new ZipArchive();
        $zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($themeFiles as $entry) {
            if (is_dir($entry)) {
                $this->addDirectoryToZip($zip, $entry);
            } elseif (is_file($entry)) {
                $this->addFileToZip($zip, $entry);
            }
        }

        $zip->close();

        $this->info("✅ Theme '{$themeName}' packed successfully!");
        $this->info("📁 Output: {$zipFilePath}");
        $this->info("📦 Size: " . number_format(filesize($zipFilePath) / 1024, 2) . ' KB');
        $this->info("📄 Files: " . $zip->numFiles);
    }

    protected function addFileToZip($zip, $file)
    {
        $relativePath = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file);
        $zip->addFile($file, $relativePath);
    }

    protected function addDirectoryToZip($zip, $directory)
    {
        $excludedFiles = ['.DS_Store'];

        $files = File::allFiles($directory);
        $relativePath = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $directory);

        foreach ($files as $file) {
            if (in_array($file->getFilename(), $excludedFiles)) {
                continue;
            }

            $relativeFilePath = str_replace($directory . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $zip->addFile($file->getPathname(), "{$relativePath}/{$relativeFilePath}");
        }
    }
}
