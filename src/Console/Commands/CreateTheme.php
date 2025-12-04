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

        // starter path (inside vendor/secretwebmaster/wncms-core/resources/themes/starter)
        $starterPath = dirname(__DIR__, 3) . '/resources/themes/starter';

        if (!File::exists($starterPath)) {
            $this->error("Starter theme not found at {$starterPath}");
            return;
        }

        // target path (public/themes/{themeName})
        $targetPath = public_path("themes/{$themeName}");

        if (File::exists($targetPath)) {
            $this->error("Theme '{$themeName}' already exists at {$targetPath}");
            return;
        }

        // copy entire starter directory
        File::copyDirectory($starterPath, $targetPath);

        $this->info("Theme '{$themeName}' created successfully.");
        $this->info("Created theme directory: {$targetPath}");

        // list all created files and directories
        $all = File::allFiles($targetPath);
        $dirs = File::directories($targetPath);

        $this->info("Directories created:");
        foreach ($dirs as $d) {
            $this->info(" - {$d}");
        }

        $this->info("Files created:");
        foreach ($all as $file) {
            $this->info(" - {$file->getPathname()}");
        }
    }
}
