<?php

namespace Wncms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CreateThemeFile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wncms:create-theme-file {themeName}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create blank theme files with starter templates';


    public function handle()
    {
        $themeName = $this->argument('themeName');
        
        if (empty($themeName)) {
            $this->error('Theme name is not set.');
            return;
        }
    
        // Define source and destination paths
        $configSource = config_path('theme/starter.php');
        $configDestination = config_path("theme/{$themeName}.php");
    
        $publicSource = public_path('theme/starter');
        $publicDestination = public_path("theme/{$themeName}");
    
        $viewsSource = resource_path("views/frontend/theme/starter");
        $viewsDestination = resource_path("views/frontend/theme/{$themeName}");
    
        $langsSource = base_path("lang/zh_TW/starter.php");
        $langsDestination = base_path("lang/zh_TW/{$themeName}.php");
    
        // Copy the files and directories
        if (File::exists($configSource)) {
            if(!File::exists($configDestination)){
                File::copy($configSource, $configDestination);
                $this->info("Config file copied to {$configDestination}");
            }else{
                $this->info("File already exists at {$configDestination}");
            }
        } else {
            $this->error("Config file not found at {$configSource}");
        }
    
        if (File::isDirectory($publicSource)) {
            if(!File::exists($publicDestination)){
                File::copyDirectory($publicSource, $publicDestination);
                $this->info("Public assets copied to {$publicDestination}");
            }else{
                $this->info("File already exists at {$publicDestination}");
            }
        } else {
            $this->error("Public assets directory not found at {$publicSource}");
        }
    
        if (File::isDirectory($viewsSource)) {
            if(!File::exists($viewsDestination)){
                File::copyDirectory($viewsSource, $viewsDestination);
                $this->info("Views copied to {$viewsDestination}");

                $files = File::allFiles($viewsDestination);
                foreach($files as $file){
                    $content = File::get($file);
                    $newContent = str_replace("starter", $themeName, $content);
                    File::put($file, $newContent);
                }
                $this->info("Replace starter strings to {$themeName}");

            }else{
                $this->info("File already exists at {$viewsDestination}");
            }
        } else {
            $this->error("Views directory not found at {$viewsSource}");
        }
    

        if (File::exists($langsSource)) {
            if(!File::exists($langsDestination)){
                File::copy($langsSource, $langsDestination);
                $this->info("Lang file copied to {$langsDestination}");
            }else{
                $this->info("File already exists at {$langsDestination}");
            }
        } else {
            $this->error("Lang file not found at {$langsSource}");
        }

        // TODO: replace starter file content to $themeName
    
        $this->info("Theme files for '{$themeName}' created successfully.");
    }
}
