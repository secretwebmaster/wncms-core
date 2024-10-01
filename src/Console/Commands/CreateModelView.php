<?php

namespace Wncms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CreateModelView extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wncms:create-model-view {model_name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Quickly create custom view files for new Eloquent model';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $package_view_path = wncms()->getPackageRootPath('resources/views');

        $view_files = [
            'index' => $package_view_path . '/backend/starters/index.blade.php',
            'create' => $package_view_path . '/backend/starters/create.blade.php',
            'edit' => $package_view_path . '/backend/starters/edit.blade.php',
            'form-items' => $package_view_path . '/backend/starters/form-items.blade.php',
        ];

        //namings
        $model_name = str()->snake($this->argument('model_name'));
        $className = str($model_name)->studly();
        $singulaer_camel = str($model_name)->camel()->singular();
        $plural_camel = str($model_name)->camel()->plural();
        $singulaer_snake = str($model_name)->snake()->singular();
        $plural_snake = str($model_name)->snake()->plural();

        //generate view files
        foreach ($view_files as $view_file) {
            $new_view_file = resource_path("views/backend/{$plural_snake}/") . basename($view_file);

            if (!File::exists($new_view_file)) {

                $directory = dirname($new_view_file);

                //make sure directory exists
                if (!File::isDirectory($directory)) {
  
                    File::makeDirectory($directory, 0755, true, true);
                    $this->info("created direcotry {$directory}");
                }

                //copy
                File::copy($view_file, $new_view_file);

                //replace starter content for model
                $file_content = File::get($new_view_file);

                // replace @inclde path
                $updated_content = str_replace('backend.starters.', "backend.{$plural_snake}.", $file_content);
   
                //replace starter
                $updated_content = str_replace('starter', $singulaer_snake, $updated_content);

                //replace model variable
                $updated_content = str_replace("\${$singulaer_snake}", "\${$singulaer_camel}", $updated_content);

                File::put($new_view_file, $updated_content);
                $this->info("copied starter file to {$new_view_file}");
            }else{
                $this->error("File {$new_view_file} already exists");
            }
        }

    }
}
