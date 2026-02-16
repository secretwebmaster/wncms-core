<?php

namespace Wncms\Console\Commands;

use Artisan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CreateModel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wncms:create-model {model_name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Quickly create custom model class, view, migration, contoller, files for new Eloquent model';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // namings
        $model_name = str()->snake($this->argument('model_name'));
        $className = str($model_name)->studly();
        $singular_camel = str($model_name)->camel()->singular();
        $plural_camel = str($model_name)->camel()->plural();
        $singular_snake = str($model_name)->snake()->singular();
        $plural_snake = str($model_name)->snake()->plural();

        // make model
        $modelFile = app_path("Models/{$className}.php");

        if (File::exists($modelFile)) {
            $this->info("Model {$className} already exists, skipping make:model");
        } else {
            Artisan::call("make:model {$className}");
            $this->info(trim(Artisan::output()));
        }

        // make migration
        $migrationName = "create_{$plural_snake}_table";
        Artisan::call("make:migration {$migrationName} --path=database/migrations");
        $this->info(trim(Artisan::output()));

        // make controller
        Artisan::call("make:controller Backend/{$className}Controller --resource --model={$className}");
        $this->info(trim(Artisan::output()));

        // update controller content
        $controller_file = app_path("Http/Controllers/Backend/{$className}Controller.php");
        if (File::exists($controller_file)) {

            $content = File::get($controller_file);

            $content = str_replace("backend.{$plural_camel}.", "backend.{$plural_snake}.", $content);
            $content = str_replace("route('{$plural_camel}.", "route('{$plural_snake}.", $content);
            $content = str_replace("->tags(['{$plural_camel}'])", "->tags(['{$plural_snake}'])", $content);
            $content = str_replace("wncms()->getModelWord('{$singular_camel}'", "wncms()->getModelWord('{$singular_snake}'", $content);

            File::put($controller_file, $content);
            $this->info("Controller {$controller_file} has been updated");
        } else {
            $this->error("Controller {$controller_file} is not found");
        }

        // create model views
        Artisan::call("wncms:create-model-view {$singular_snake}");
        $this->info(trim(Artisan::output()));

        // create permissions
        Artisan::call("wncms:create-model-permission {$singular_snake}");
        $this->info(trim(Artisan::output()));

        // append backend routes
        if ($this->confirm('This will append new routes to custom_backend.php file, are you sure?')) {

            $customBackendFile = base_path('routes/custom_backend.php');

            if (!File::exists($customBackendFile)) {
                File::put($customBackendFile, "<?php\n\n// Custom backend routes\n");
                $this->info("Route file {$customBackendFile} created");
            }

            $routes = <<<EOT

// {$singular_snake} model routes
Route::get('{$plural_snake}', [{$className}Controller::class, 'index'])->middleware('can:{$singular_snake}_index')->name('{$plural_snake}.index');
Route::get('{$plural_snake}/create', [{$className}Controller::class, 'create'])->middleware('can:{$singular_snake}_create')->name('{$plural_snake}.create');
Route::get('{$plural_snake}/create/{id}', [{$className}Controller::class, 'create'])->middleware('can:{$singular_snake}_clone')->name('{$plural_snake}.clone');
Route::get('{$plural_snake}/{id}/edit', [{$className}Controller::class, 'edit'])->middleware('can:{$singular_snake}_edit')->name('{$plural_snake}.edit');
Route::post('{$plural_snake}/store', [{$className}Controller::class, 'store'])->middleware('can:{$singular_snake}_create')->name('{$plural_snake}.store');
Route::patch('{$plural_snake}/{id}', [{$className}Controller::class, 'update'])->middleware('can:{$singular_snake}_edit')->name('{$plural_snake}.update');
Route::delete('{$plural_snake}/{id}', [{$className}Controller::class, 'destroy'])->middleware('can:{$singular_snake}_delete')->name('{$plural_snake}.destroy');
Route::post('{$plural_snake}/bulk_delete', [{$className}Controller::class, 'bulk_delete'])->middleware('can:{$singular_snake}_bulk_delete')->name('{$plural_snake}.bulk_delete');
EOT;

            File::append($customBackendFile, $routes);

            $this->prependUseStatement(
                $customBackendFile,
                "use App\\Http\\Controllers\\Backend\\{$className}Controller;"
            );

            $this->info("Route file {$customBackendFile} has been updated");
        }
    }

    /**
     * Prepend a use statement to a PHP file if it doesn't already exist.
     *
     * @param string $filePath
     * @param string $useStatement
     */
    protected function prependUseStatement($filePath, $useStatement)
    {
        $content = File::get($filePath);

        if (str_contains($content, $useStatement)) {
            return;
        }

        $pos = strpos($content, '<?php');
        if ($pos === false) {
            return;
        }

        $insertPos = $pos + 5;
        $content = substr($content, 0, $insertPos)
            . "\n\n{$useStatement}\n"
            . ltrim(substr($content, $insertPos));

        File::put($filePath, $content);
    }
}
