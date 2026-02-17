<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

$thisVersion = '6.2.0';

info("running update_{$thisVersion}.php");

try {
    // Keep database in sync before permission backfill.
    Artisan::call('migrate', [
        '--path' => 'vendor/secretwebmaster/wncms-core/database/migrations',
        '--force' => true,
    ]);

    // Sync latest scaffold stubs into host project.
    Artisan::call('vendor:publish', [
        '--tag' => 'wncms-stubs',
        '--force' => true,
    ]);

    if (Schema::hasTable('tag_keywords')) {
        Schema::table('tag_keywords', function (Blueprint $table) {
            if (!Schema::hasColumn('tag_keywords', 'model_key')) {
                $table->string('model_key')->nullable()->after('tag_id');
            }

            if (!Schema::hasColumn('tag_keywords', 'binding_field')) {
                $table->string('binding_field')->nullable()->after('name');
            }
        });
    }

    $superadmin = Role::firstOrCreate(['name' => 'superadmin']);
    $admin = Role::firstOrCreate(['name' => 'admin']);

    $suffixes = [
        'list',
        'index',
        'show',
        'create',
        'clone',
        'bulk_create',
        'edit',
        'bulk_edit',
        'delete',
        'bulk_delete',
    ];

    DB::transaction(function () use ($suffixes, $superadmin, $admin) {
        foreach ($suffixes as $suffix) {
            $permission = Permission::firstOrCreate([
                'name' => "link_{$suffix}",
            ]);

            $superadmin->givePermissionTo($permission);
            $admin->givePermissionTo($permission);
        }
    });

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    uss('core_version', $thisVersion);
    info("completed update_{$thisVersion}.php");
} catch (\Throwable $e) {
    info("error when running update_{$thisVersion}.php");
    info('Error: ' . $e->getMessage());
    return;
}
