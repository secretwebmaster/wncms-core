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
        $package_view_path = wncms()->getPackageRootPath('../resources/views');

        $view_files = [
            $package_view_path . '/backend/starters/index.blade.php',
            $package_view_path . '/backend/starters/create.blade.php',
            $package_view_path . '/backend/starters/edit.blade.php',
            $package_view_path . '/backend/starters/form-items.blade.php',
        ];

        // namings
        $model_name = str()->snake($this->argument('model_name'));
        $singular_camel = str($model_name)->camel()->singular();
        $singular_snake = str($model_name)->snake()->singular();
        $plural_snake = str($model_name)->snake()->plural();

        foreach ($view_files as $source) {

            if (!File::exists($source)) {
                $this->error("Source view file not found: {$source}");
                continue;
            }

            $target = resource_path("views/backend/{$plural_snake}/" . basename($source));
            $directory = dirname($target);

            if (!File::isDirectory($directory)) {
                File::makeDirectory($directory, 0755, true);
                $this->info("created directory {$directory}");
            }

            if (File::exists($target)) {
                $this->error("File {$target} already exists");
                continue;
            }

            File::copy($source, $target);

            $content = File::get($target);

            $content = str_replace('backend.starters.', "backend.{$plural_snake}.", $content);
            $content = str_replace('starter', $singular_snake, $content);
            $content = str_replace('$' . $singular_snake, '$' . $singular_camel, $content);

            File::put($target, $content);

            $this->info("copied starter file to {$target}");
        }
    }
}
