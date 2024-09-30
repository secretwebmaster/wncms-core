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
        $superadmin = Role::firstOrCreate(['name' => 'superadmin']);
        $admin = Role::firstOrCreate(['name' => 'admin']);

        $modelName = $this->argument('model_name');

        foreach($this->getDefaultPermissionTypes() as $permissionType){
            $permission = Permission::firstOrCreate(['name' => "{$modelName}_{$permissionType}"]);
            $superadmin->givePermissionTo($permission);
            $admin->givePermissionTo($permission);
        }
        $this->info("Added permisions to {$modelName}");
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
