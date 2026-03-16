<?php

namespace Wncms\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreatePermission extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wncms:create-permission {permission_name} {role?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create one or more permissions and optionally assign them to one or more roles';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $permissionNames = $this->parseCsvArgument((string) $this->argument('permission_name'));
        $roleNames = $this->parseCsvArgument((string) ($this->argument('role') ?? ''));

        if (empty($permissionNames)) {
            $this->error('At least one permission name is required.');

            return self::FAILURE;
        }

        $permissions = [];
        foreach ($permissionNames as $permissionName) {
            $permissions[] = Permission::firstOrCreate(['name' => $permissionName]);
        }

        $roles = [];
        foreach ($roleNames as $roleName) {
            $roles[] = Role::firstOrCreate(['name' => $roleName]);
        }

        foreach ($roles as $role) {
            foreach ($permissions as $permission) {
                $role->givePermissionTo($permission);
            }
        }

        $this->info('Permissions ready: ' . implode(', ', $permissionNames));

        if (!empty($roleNames)) {
            $this->info('Assigned to roles: ' . implode(', ', $roleNames));
        }

        return self::SUCCESS;
    }

    /**
     * Parse a comma-separated argument into a unique trimmed string list.
     *
     * @param string $value
     * @return array<int, string>
     */
    protected function parseCsvArgument(string $value): array
    {
        $items = array_map('trim', explode(',', $value));
        $items = array_filter($items, static fn($item) => $item !== '');

        return array_values(array_unique($items));
    }
}
