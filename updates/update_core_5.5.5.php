<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$thisVersion = '5.5.5';

// This is a test update to see if version is working
info("running update_{$thisVersion}.php");
try {
    // copy files

    // add migration
    // Artisan::call('migrate', [
    //     '--path' => 'vendor/secretwebmaster/wncms-core/database/migrations',
    //     '--force' => true,
    // ]);

    // migrate laravel-optionable table
    Artisan::call('migrate', ['--force' => true]);

    if (Schema::hasTable('extra_attributes')) {
        $records = DB::table('extra_attributes')->get();

        foreach ($records as $record) {
            try {
                $modelClass = $record->model_type;
                if (!class_exists($modelClass)) {
                    continue; // skip if model no longer exists
                }

                $model = $modelClass::find($record->model_id);
                if (! $model) {
                    continue; // skip if model record not found
                }

                $attributes = json_decode($record->model_attributes, true);
                if (is_array($attributes)) {
                    foreach ($attributes as $key => $value) {
                        $model->set_option($key, $value);
                    }
                }
            } catch (\Throwable $e) {
                info("Failed migrating extra_attributes ID {$record->id}: " . $e->getMessage());
            }
        }

        // Optionally drop old table after migration
        Schema::dropIfExists('extra_attributes');
        info("Migrated and removed old extra_attributes table.");
    }

    if (Schema::hasColumn('pages', 'options')) {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('options');
        });
    }

    if (Schema::hasColumn('advertisements', 'website_id')) {
        Schema::table('advertisements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('website_id');
        });
    }

    if (Schema::hasTable('advertisements')) {
        Schema::table('advertisements', function (Blueprint $table) {
            $table->string('contact')->nullable();
        });
    }

    // update existing database

    // add roles

    // add permissions
    Artisan::call('wncms:create-model-permission comment');

    uss('core_version', $thisVersion);
    info("completed update_{$thisVersion}.php");
} catch (Exception $e) {
    info("error when running update_{$thisVersion}.php");
    info("Error: " . $e->getMessage());
    return;
}
