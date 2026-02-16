<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

$thisVersion = '6.1.9';

info("running update_{$thisVersion}.php");

try {
    // Keep database in sync before permission backfill.
    Artisan::call('migrate', [
        '--path' => 'vendor/secretwebmaster/wncms-core/database/migrations',
        '--force' => true,
    ]);

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
