<?php

namespace Wncms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CreateTheme extends Command
{
    protected $signature = 'wncms:create-theme {themeName}';
    protected $description = 'Create a new WNCMS theme with the latest directory structure';

    public function handle()
    {
        $themeName = $this->argument('themeName');

        if (empty($themeName)) {
            $this->error('Theme name is required.');
            return;
        }

        // get starter path 
        $starterPath = dirname(__DIR__, 3) . '/resources/themes/starter';
        if (!File::exists($starterPath)) {
            $this->error("Starter theme not found at {$starterPath}");
            return;
        }

        // get target path
        $targetPath = public_path("themes/{$themeName}");
        if (File::exists($targetPath)) {
            $this->error("Theme '{$themeName}' already exists at {$targetPath}");
            return;
        }

        // create theme directory
        File::copyDirectory($starterPath, $targetPath);

        // copy starter files
        $this->info("Theme '{$themeName}' created successfully.");
    }
}
