<?php

namespace Wncms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class RemoveThemeFile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wncms:remove_theme_file {themeName}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove theme files but keep theme settings in database';


    public function handle()
    {
        $themeName = $this->argument('themeName');
        
        if (empty($themeName)) {
            $this->error('Theme name is not set.');
            return;
        }
    
        // Define source and destination paths
        $configDestination = config_path("theme/{$themeName}.php");
        $publicDestination = public_path("theme/{$themeName}");
        $viewsDestination = resource_path("views/frontend/theme/{$themeName}");
        $langsDestination = resource_path("lang/zh_TW/{$themeName}.php");

         // Remove the files and directories
         if (File::exists($configDestination)) {
            File::delete($configDestination);
            $this->info("Config file deleted: {$configDestination}");
        }

        if (File::exists($publicDestination)) {
            File::deleteDirectory($publicDestination);
            $this->info("Public assets deleted: {$publicDestination}");
        }

        if (File::exists($viewsDestination)) {
            File::deleteDirectory($viewsDestination);
            $this->info("Views deleted: {$viewsDestination}");
        }

        if (File::exists($langsDestination)) {
            File::delete($langsDestination);
            $this->info("Lang file deleted: {$langsDestination}");
        }
    
        $this->info("Theme files for '{$themeName}' removed successfully.");
    }
}
