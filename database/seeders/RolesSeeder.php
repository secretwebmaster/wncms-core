<?php

namespace Wncms\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //create roles
        foreach ($this->default_roles() as $role_name) {
            Role::firstOrCreate(['name' => $role_name]);
        }

        //get admins
        $superadmin = Role::where('name', 'superadmin')->first();
        $admin = Role::where('name', 'admin')->first();

        //create and assign default permissions
        foreach($this->default_models() as $modelName){
            foreach($this->default_permission_suffixes() as $permissionSuffix){
                $permissionName = "{$modelName}_{$permissionSuffix}";
                $permission = Permission::firstOrCreate(['name' => $permissionName]);
                $superadmin->givePermissionTo($permission);
                $admin->givePermissionTo($permission);
            }
        }

        //create and assign special permissions
        foreach($this->special_permissions() as $permissionName){
            $permission = Permission::firstOrCreate(['name' => $permissionName]);
            $superadmin->givePermissionTo($permission);
            $admin->givePermissionTo($permission);
        }
    }

    public function default_roles()
    {
        return [
            'superadmin',
            'admin',
            'manager',
            'member',
            'suspended'
        ];
    }

    public function default_models()
    {
        return [
            'advertisement',
            'banner',
            'contact_form',
            'contact_form_option',
            'contact_form_submission',
            'menu',
            'page',
            'permission',
            'post',
            'search_keyword',
            'setting',
            'record',
            'role',
            'tag',
            'tag_keyword',
            'theme',
            'user',
            'website',
            'faq',
            'package',

            //TODO: add new models
        ];
    }

    public function default_permission_suffixes()
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

    public function special_permissions()
    {
        //TODO get model files and generate
        return [
            'analytics_index',
            'cache_clear',
            'cache_flush',
            'contact_form_submission_export',
            'post_bulk_clone',
            'post_bulk_set_websites',
            'post_bulk_sync_tags',
            'post_generate_demo_posts',
            'tag_create_type',
            'tag_import_csv',
            'upload_image',
            'upload_video',
            'user_api_show',
            'user_api_update',
            'user_profile_show',
            'user_profile_update',
            'user_record_show',
            'user_security_show',
            
            'theme_apply',
            'theme_upload',
            'theme_activate',
            'theme_deactivate',
            'theme_preview',

            'plugin_index',
            'plugin_upload',
            'plugin_activate',
            'plugin_deactivate',
            'plugin_delete',

            'credit_recharge',
        ];
        
    }
}
