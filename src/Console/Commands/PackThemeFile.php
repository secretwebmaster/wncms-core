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
    protected $signature = 'wncms:pack-theme-file {themeName}';

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

        $themeFiles = [
            config_path("theme/{$themeName}.php"),
            public_path("theme/{$themeName}"),
            resource_path("views/frontend/theme/{$themeName}"),
            resource_path("lang/zh_TW/{$themeName}.php"),
        ];

        $uuid = date("YmdHis");
        $zipFileName = "{$themeName}_{$uuid}.zip";
        $zipFilePath = storage_path("app/backups/{$themeName}/{$zipFileName}");
        $zip = new ZipArchive();
        $zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // Create the output directory if it doesn't exist
        if (!is_dir(dirname($zipFilePath))) {
            File::makeDirectory(dirname($zipFilePath), 0755, true, true);
        }

        foreach ($themeFiles as $entry) {
            if (is_dir($entry)) {
                $this->addDirectoryToZip($zip, $entry);
            } elseif (is_file($entry)) {
                $this->addFileToZip($zip, $entry);
            }
        }

        // Close the zip archive
        $zip->close();

        $this->info("Theme files for '{$themeName}' packed successfully. Storing at /storage/app/backups/{$themeName} .");
    }

    protected function addFileToZip($zip, $file)
    {
        $relativePath = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file);
        $zip->addFile($file, $relativePath);
    }

    protected function addDirectoryToZip($zip, $directory)
    {
        $files = File::allFiles($directory);
        $relativePath = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $directory);

        foreach ($files as $file) {

            // Specify the list of filenames you want to exclude
            $relativeFilePath = str_replace($directory . DIRECTORY_SEPARATOR, '', $file->getPathname());
            
            // Check if the current file should be excluded
            $zip->addFile($file->getPathname(), "{$relativePath}/{$relativeFilePath}");

        }
    }
}
