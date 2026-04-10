<?php

namespace Wncms\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreateModelPermission extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wncms:create-model-permission {model_name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add default model permission to newly created model';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $guardName = $this->resolveGuardName();
        $superadmin = Role::firstOrCreate([
            'name' => 'superadmin',
            'guard_name' => $guardName,
        ]);
        $admin = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => $guardName,
        ]);

        $modelName = $this->argument('model_name');

        foreach($this->getDefaultPermissionTypes() as $permissionType){
            $permission = Permission::firstOrCreate([
                'name' => "{$modelName}_{$permissionType}",
                'guard_name' => $guardName,
            ]);
            $superadmin->givePermissionTo($permission);
            $admin->givePermissionTo($permission);
        }
        $this->info("Added permisions to {$modelName}");
    }

    protected function resolveGuardName(): string
    {
        $guard = trim((string) config('auth.defaults.guard'));

        return $guard !== '' ? $guard : 'web';
    }

    public function getDefaultPermissionTypes()
    {
        return [
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
    }
}
