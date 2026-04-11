<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$thisVersion = '6.3.7';

info("running update_{$thisVersion}.php");

try {
    if (!Schema::hasTable('model_has_websites')) {
        Schema::create('model_has_websites', function (Blueprint $table) {
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->morphs('model');
            $table->primary(['website_id', 'model_id', 'model_type']);
        });
    }

    if (
        Schema::hasTable('advertisements')
        && Schema::hasTable('model_has_websites')
        && Schema::hasColumn('advertisements', 'website_id')
    ) {
        $rows = DB::table('advertisements')
            ->select('id', 'website_id')
            ->whereNotNull('website_id')
            ->get();

        if ($rows->isNotEmpty()) {
            $payload = $rows
                ->map(function ($row) {
                    return [
                        'website_id' => (int) $row->website_id,
                        'model_id' => (int) $row->id,
                        'model_type' => \Wncms\Models\Advertisement::class,
                    ];
                })
                ->all();

            DB::table('model_has_websites')->insertOrIgnore($payload);
            info('migrated advertisement website bindings to model_has_websites');
        } else {
            info('skip migration for advertisements.website_id because no non-null rows were found');
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver !== 'sqlite') {
            try {
                Schema::table('advertisements', function (Blueprint $table) {
                    $table->dropForeign(['website_id']);
                });
            } catch (\Throwable $e) {
                info('skip dropping advertisements.website_id foreign key: ' . $e->getMessage());
            }
        }

        if (Schema::hasColumn('advertisements', 'website_id')) {
            Schema::table('advertisements', function (Blueprint $table) {
                $table->dropColumn('website_id');
            });
        }
    } else {
        info('skip advertisements website_id migration because required table/column is missing');
    }

    if (gss('media_disk', null) === null) {
        uss('media_disk', env('MEDIA_DISK', 'media'));
        info('initialized media_disk system setting');
    }

    uss('core_version', $thisVersion);
    info("completed update_{$thisVersion}.php");
} catch (\Throwable $e) {
    info("error when running update_{$thisVersion}.php");
    info('Error: ' . $e->getMessage());
    return;
}
