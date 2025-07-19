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
        $singulaer_camel = str($model_name)->camel()->singular();
        $plural_camel = str($model_name)->camel()->plural();
        $singulaer_snake = str($model_name)->snake()->singular();
        $plural_snake = str($model_name)->snake()->plural();

        //! make model
        Artisan::call("make:model {$className}");
        $this->info(trim(Artisan::output()));
        
        // make migration
        $migrationName = "create_{$plural_snake}_table";
        $migrationPath = "database/migrations";
        Artisan::call("make:migration $migrationName --path={$migrationPath}");
        $this->info(trim(Artisan::output()));

        // make controller
        Artisan::call("make:controller Backend/{$className}Controller --resource --model={$className}");
        $this->info(trim(Artisan::output()));
        
        // update controller file content
        $controller_file = app_path("Http/Controllers/Backend/{$className}Controller.php");
        if (File::exists($controller_file)) {

            // get file content
            $file_content = File::get($controller_file);
            // replace view name
            $updatedContent = str_replace("backend.{$plural_camel}.", "backend.{$plural_snake}.", $file_content);
            // replace route name
            $updatedContent = str_replace("route('{$plural_camel}.", "route('{$plural_snake}.", $updatedContent);
            // replace tag
            $updatedContent = str_replace("->tags(['{$plural_camel}'])", "->tags(['{$plural_snake}'])", $updatedContent);
            // replace model word
            $updatedContent = str_replace("wncms_model_word('{$singulaer_camel}'", " wncms_model_word('{$singulaer_snake}'", $updatedContent);

            File::put($controller_file, $updatedContent);
            $this->info("Controller {$controller_file} has been updated");

        }else{
            $this->error("Controller {$controller_file} is not found");
        }

        // create model view
        Artisan::call("wncms:create-model-view {$singulaer_snake}");
        $this->info(trim(Artisan::output()));

        // add model permission
        Artisan::call("wncms:create-model-permission {$singulaer_snake}");
        $this->info(trim(Artisan::output()));

        // append route to backend
        if ($this->confirm('This will append new routes to custom_backend.php file, are you sure?')) {
            
            // get controller file
            $customBackendFile = base_path("routes/custom_backend.php");

            // if exist
            if (!File::exists($customBackendFile)) {
                $this->info("route file {$customBackendFile} is not found");
                File::put($customBackendFile, "<?php\n\n// Custom backend routes\n");
                $this->info("Route file {$customBackendFile} was not found, so it has been created.");
            }

            // Append routes
            $contentToAppend = <<<EOT
            \n\n// starter_model for model StarterModel
            Route::get('starter_models', [StarterModelController::class, 'index'])->middleware('can:starter_model_index')->name('starter_models.index');
            Route::get('starter_models/create', [StarterModelController::class, 'create'])->middleware('can:starter_model_create')->name('starter_models.create');
            Route::get('starter_models/create/{id}', [StarterModelController::class, 'create'])->middleware('can:starter_model_clone')->name('starter_models.clone');
            Route::get('starter_models/{id}/edit', [StarterModelController::class, 'edit'])->middleware('can:starter_model_edit')->name('starter_models.edit');
            Route::post('starter_models/store', [StarterModelController::class, 'store'])->middleware('can:starter_model_create')->name('starter_models.store');
            Route::patch('starter_models/{id}', [StarterModelController::class, 'update'])->middleware('can:starter_model_edit')->name('starter_models.update');
            Route::delete('starter_models/{id}', [StarterModelController::class, 'destroy'])->middleware('can:starter_model_delete')->name('starter_models.destroy');
            Route::post('starter_models/bulk_delete', [StarterModelController::class, 'bulk_delete'])->middleware('can:starter_model_bulk_delete')->name('starter_models.bulk_delete');
            EOT;
            
            $contentToAppend = str_replace("starter_model", $singulaer_snake, $contentToAppend);
            // $contentToAppend = str_replace("starterModel", $singulaer_camel, $contentToAppend);
            $contentToAppend = str_replace("StarterModel", $className, $contentToAppend);
            File::append($customBackendFile, $contentToAppend);
            
            // Prepend use statements
            $useStatement = "use App\\Http\\Controllers\\Backend\\{$className}Controller;";
            $this->prependUseStatement($customBackendFile, $useStatement);
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
        $fileContents = file_get_contents($filePath);

        if (strpos($fileContents, $useStatement) === false) {
            $phpTagPos = strpos($fileContents, '<?php');

            if ($phpTagPos !== false) {

                // Find the end of the PHP tag and any subsequent newlines
                $phpTagEndPos = $phpTagPos + 5;
                $beforeInsert = substr($fileContents, 0, $phpTagEndPos);
                $afterInsert = substr($fileContents, $phpTagEndPos);

                // Remove extra newlines from the beginning of the file after <?php tag
                $afterInsert = preg_replace('/^\s+/', '', $afterInsert);

                // Combine parts: PHP tag, use statement, and the rest of the file content
                $modifiedContents = $beforeInsert . "\n\n" . $useStatement . "\n" . $afterInsert;

                //$modifiedContents = substr($fileContents, 0, $phpTagPos + 5) . "\n\n" . $useStatement . "" . substr($fileContents, $phpTagPos + 5);
                File::put($filePath, $modifiedContents);
                $this->info("Prepended {$useStatement} to {$filePath}");
            } else {
                $this->info("No <?php tag found in the file.");
            }
        } else {
            $this->info("Use statement for {$useStatement} already exists.");
        }
    }
}
