<?php

use Wncms\Http\Controllers\Backend\CacheController;
use Wncms\Http\Controllers\Backend\DashboardController;
use Wncms\Http\Controllers\Backend\ClickController;
use Wncms\Http\Controllers\Backend\MenuController;
use Wncms\Http\Controllers\Backend\ModelController;
use Wncms\Http\Controllers\Backend\PackageController;
use Wncms\Http\Controllers\Backend\PageController;
use Wncms\Http\Controllers\Backend\PermissionController;
use Wncms\Http\Controllers\Backend\PluginController;
use Wncms\Http\Controllers\Backend\PostController;
use Wncms\Http\Controllers\Backend\RecordController;
use Wncms\Http\Controllers\Backend\SearchKeywordController;
use Wncms\Http\Controllers\Backend\SettingController;
use Wncms\Http\Controllers\Backend\TagController;
use Wncms\Http\Controllers\Backend\ToolController;
use Wncms\Http\Controllers\Backend\UpdateController;
use Wncms\Http\Controllers\Backend\UploadController;
use Wncms\Http\Controllers\Backend\UserController;
use Wncms\Http\Controllers\Backend\WebsiteController;
use Wncms\Http\Controllers\ThemeController;
use Wncms\Http\Controllers\Api\V2\Backend\AdvertisementController as ApiV2AdvertisementController;
use Wncms\Http\Controllers\Api\V2\Backend\CommentController as ApiV2CommentController;
use Wncms\Http\Controllers\Api\V2\Backend\ClickController as ApiV2ClickController;
use Wncms\Http\Controllers\Api\V2\Backend\PackageController as ApiV2PackageController;
use Wncms\Http\Controllers\Api\V2\Backend\PageBuilderController as ApiV2PageBuilderController;
use Wncms\Http\Controllers\Api\V2\Backend\PluginController as ApiV2PluginController;
use Wncms\Http\Controllers\Api\V2\Backend\PostController as ApiV2PostController;
use Wncms\Http\Controllers\Api\V2\Backend\RoleController as ApiV2RoleController;
use Wncms\Http\Controllers\Api\V2\Backend\ThemeController as ApiV2ThemeController;

return [
    'resources' => [
        'advertisements' => [
            'model_key' => 'advertisement',
            'controller' => ApiV2AdvertisementController::class,
            'permissions' => [
                'index' => 'advertisement_index',
                'show' => 'advertisement_edit',
                'store' => 'advertisement_create',
                'update' => 'advertisement_edit',
                'destroy' => 'advertisement_delete',
                'bulk_delete' => 'advertisement_bulk_delete',
            ],
        ],
        'channels' => [
            'model_key' => 'channel',
            'permissions' => [
                'index' => 'channel_index',
                'show' => 'channel_edit',
                'store' => 'channel_create',
                'update' => 'channel_edit',
                'destroy' => 'channel_delete',
                'bulk_delete' => 'channel_bulk_delete',
            ],
        ],
        'comments' => [
            'model_key' => 'comment',
            'controller' => ApiV2CommentController::class,
            'permissions' => [
                'index' => 'comment_edit',
                'show' => 'comment_edit',
                'store' => 'comment_create',
                'update' => 'comment_edit',
                'destroy' => 'comment_delete',
            ],
            'enable_bulk_delete' => false,
        ],
        'clicks' => [
            'model_key' => 'click',
            'permissions' => [
                'index' => 'click_index',
                'destroy' => 'click_delete',
                'bulk_delete' => 'click_bulk_delete',
            ],
            'enabled_actions' => ['index', 'destroy', 'bulk_delete'],
        ],
        'links' => [
            'model_key' => 'link',
            'permissions' => [
                'index' => 'link_index',
                'show' => 'link_edit',
                'store' => 'link_create',
                'update' => 'link_edit',
                'destroy' => 'link_delete',
                'bulk_delete' => 'link_bulk_delete',
            ],
        ],
        'menus' => [
            'model_key' => 'menu',
            'permissions' => [
                'index' => 'menu_index',
                'show' => 'menu_edit',
                'store' => 'menu_create',
                'update' => 'menu_edit',
                'destroy' => 'menu_delete',
            ],
            'enable_bulk_delete' => false,
        ],
        'pages' => [
            'model_key' => 'page',
            'permissions' => [
                'index' => 'page_index',
                'show' => 'page_show',
                'store' => 'page_create',
                'update' => 'page_edit',
                'destroy' => 'page_delete',
                'bulk_delete' => 'page_bulk_delete',
            ],
        ],
        'packages' => [
            'model_key' => 'package',
            'controller' => ApiV2PackageController::class,
            'permissions' => [
                'index' => 'package_index',
            ],
            'enabled_actions' => ['index'],
            'enable_bulk_delete' => false,
        ],
        'parameters' => [
            'model_key' => 'parameter',
            'permissions' => [
                'index' => 'parameter_index',
                'show' => 'parameter_edit',
                'store' => 'parameter_create',
                'update' => 'parameter_edit',
                'destroy' => 'parameter_delete',
                'bulk_delete' => 'parameter_bulk_delete',
            ],
        ],
        'permissions' => [
            'model_key' => 'permission',
            'permissions' => [
                'index' => 'permission_index',
                'show' => 'permission_show',
                'store' => 'permission_create',
                'update' => 'permission_edit',
                'destroy' => 'permission_delete',
                'bulk_delete' => 'permission_bulk_delete',
            ],
        ],
        'plugins' => [
            'model_key' => 'plugin',
            'controller' => ApiV2PluginController::class,
            'permissions' => [
                'index' => 'plugin_index',
            ],
            'enabled_actions' => ['index'],
            'enable_bulk_delete' => false,
        ],
        'posts' => [
            'model_key' => 'post',
            'controller' => ApiV2PostController::class,
            'permissions' => [
                'index' => 'post_index',
                'show' => 'post_show',
                'store' => 'post_create',
                'update' => 'post_edit',
                'destroy' => 'post_delete',
            ],
            'enable_bulk_delete' => false,
        ],
        'roles' => [
            'model_key' => 'role',
            'controller' => ApiV2RoleController::class,
            'permissions' => [
                'index' => 'role_index',
                'show' => 'role_show',
                'store' => 'role_create',
                'update' => 'role_edit',
                'destroy' => 'role_delete',
            ],
            'enable_bulk_delete' => false,
        ],
        'search_keywords' => [
            'model_key' => 'search_keyword',
            'permissions' => [
                'index' => 'search_keyword_index',
                'show' => 'search_keyword_edit',
                'store' => 'search_keyword_create',
                'update' => 'search_keyword_edit',
                'destroy' => 'search_keyword_delete',
                'bulk_delete' => 'search_keyword_bulk_delete',
            ],
        ],
        'tags' => [
            'model_key' => 'tag',
            'permissions' => [
                'index' => 'tag_index',
                'show' => 'tag_show',
                'store' => 'tag_create',
                'update' => 'tag_edit',
                'destroy' => 'tag_delete',
                'bulk_delete' => 'tag_bulk_delete',
            ],
        ],
        'themes' => [
            'model_key' => 'theme',
            'controller' => ApiV2ThemeController::class,
            'permissions' => [
                'index' => 'theme_index',
            ],
            'enabled_actions' => ['index'],
            'enable_bulk_delete' => false,
        ],
        'users' => [
            'model_key' => 'user',
            'permissions' => [
                'index' => 'user_index',
                'show' => 'user_show',
                'store' => 'user_create',
                'update' => 'user_edit',
                'destroy' => 'user_delete',
                'bulk_delete' => 'user_bulk_delete',
            ],
        ],
        'websites' => [
            'model_key' => 'website',
            'permissions' => [
                'index' => 'website_index',
                'show' => 'website_show',
                'store' => 'website_create',
                'update' => 'website_edit',
                'destroy' => 'website_delete',
            ],
            'enable_bulk_delete' => false,
        ],
    ],

    'actions' => [
        // Cache
        ['name' => 'cache.flush', 'method' => 'post', 'uri' => 'cache/flush', 'controller' => CacheController::class, 'action' => 'flush', 'permission' => 'cache_flush'],
        ['name' => 'cache.flush.tag', 'method' => 'post', 'uri' => 'cache/flush/{tag}', 'controller' => CacheController::class, 'action' => 'flush', 'permission' => 'cache_flush'],
        ['name' => 'cache.clear', 'method' => 'post', 'uri' => 'cache/clear/{key}', 'controller' => CacheController::class, 'action' => 'clear', 'permission' => 'cache_clear'],
        ['name' => 'cache.clear.tag', 'method' => 'post', 'uri' => 'cache/clear/{tag}/{key}', 'controller' => CacheController::class, 'action' => 'clear', 'permission' => 'cache_clear'],

        // Dashboard
        ['name' => 'dashboard.switch_website', 'method' => 'post', 'uri' => 'dashboard/switch_website', 'controller' => DashboardController::class, 'action' => 'switch_website'],

        // Tools
        ['name' => 'install_default_theme', 'method' => 'post', 'uri' => 'tools/install_default_theme', 'controller' => ToolController::class, 'action' => 'install_default_theme', 'permission' => 'theme_upload'],
        ['name' => 'rerun_core_update', 'method' => 'post', 'uri' => 'tools/rerun_core_update', 'controller' => ToolController::class, 'action' => 'rerun_core_update', 'permission' => 'setting_edit'],

        // Menus
        ['name' => 'menus.edit_menu_item', 'method' => 'post', 'uri' => 'menus/edit_menu_item', 'controller' => MenuController::class, 'action' => 'edit_menu_item', 'permission' => 'menu_edit'],
        ['name' => 'menus.get_menu_item', 'method' => 'post', 'uri' => 'menus/get_menu_item', 'controller' => MenuController::class, 'action' => 'get_menu_item', 'permission' => 'menu_edit'],
        ['name' => 'menus.search_source_items', 'method' => 'post', 'uri' => 'menus/search_source_items', 'controller' => MenuController::class, 'action' => 'search_source_items', 'permission' => 'menu_edit'],
        ['name' => 'menus.clone', 'method' => 'post', 'uri' => 'menus/clone', 'controller' => MenuController::class, 'action' => 'clone', 'permission' => 'menu_create'],

        // Models
        ['name' => 'models.update', 'method' => 'post', 'uri' => 'models/update', 'controller' => ModelController::class, 'action' => 'update'],
        ['name' => 'models.bulk_delete', 'method' => 'post', 'uri' => 'models/bulk_delete', 'controller' => ModelController::class, 'action' => 'bulk_delete'],
        ['name' => 'models.bulk_force_delete', 'method' => 'post', 'uri' => 'models/bulk_force_delete', 'controller' => ModelController::class, 'action' => 'bulk_force_delete'],

        // Page builder + extra page actions
        ['name' => 'pages.builder.load', 'method' => 'get', 'uri' => 'pages/{id}/builder/load', 'controller' => ApiV2PageBuilderController::class, 'action' => 'load', 'permission' => 'page_edit'],
        ['name' => 'pages.builder.save', 'method' => 'post', 'uri' => 'pages/{id}/builder/save', 'controller' => ApiV2PageBuilderController::class, 'action' => 'save', 'permission' => 'page_edit'],
        ['name' => 'pages.create_theme_pages', 'method' => 'post', 'uri' => 'pages/create_theme_pages', 'controller' => PageController::class, 'action' => 'create_theme_pages', 'permission' => 'page_create'],
        ['name' => 'pages.templates', 'method' => 'post', 'uri' => 'pages/templates', 'controller' => PageController::class, 'action' => 'templates', 'permission' => 'page_create'],
        ['name' => 'pages.widget', 'method' => 'post', 'uri' => 'pages/widget', 'controller' => PageController::class, 'action' => 'widget', 'permission' => 'page_edit'],
        ['name' => 'pages.bulk_delete', 'method' => 'post', 'uri' => 'pages/bulk_delete', 'controller' => PageController::class, 'action' => 'bulk_delete', 'permission' => 'page_bulk_delete'],

        // Packages
        ['name' => 'packages.check', 'method' => 'post', 'uri' => 'packages/check', 'controller' => PackageController::class, 'action' => 'check', 'permission' => 'package_index'],
        ['name' => 'packages.activate', 'method' => 'post', 'uri' => 'packages/{key}/activate', 'controller' => PackageController::class, 'action' => 'activate', 'permission' => 'package_edit'],
        ['name' => 'packages.deactivate', 'method' => 'post', 'uri' => 'packages/{key}/deactivate', 'controller' => PackageController::class, 'action' => 'deactivate', 'permission' => 'package_edit'],

        // Permissions
        ['name' => 'permissions.bulk_assign_roles', 'method' => 'post', 'uri' => 'permissions/bulk_assign_roles', 'controller' => PermissionController::class, 'action' => 'bulk_assign_roles', 'permission' => 'permission_edit'],
        ['name' => 'permissions.bulk_remove_roles', 'method' => 'post', 'uri' => 'permissions/bulk_remove_roles', 'controller' => PermissionController::class, 'action' => 'bulk_remove_roles', 'permission' => 'permission_edit'],
        ['name' => 'permissions.bulk_delete', 'method' => 'post', 'uri' => 'permissions/bulk_delete', 'controller' => PermissionController::class, 'action' => 'bulk_delete', 'permission' => 'permission_bulk_delete'],

        // Plugins
        ['name' => 'plugins.upload', 'method' => 'post', 'uri' => 'plugins/upload', 'controller' => PluginController::class, 'action' => 'upload', 'permission' => 'plugin_upload'],
        ['name' => 'plugins.upgrade', 'method' => 'post', 'uri' => 'plugins/upgrade/{plugin}', 'controller' => PluginController::class, 'action' => 'upgrade', 'permission' => 'plugin_activate'],
        ['name' => 'plugins.activate_raw', 'method' => 'post', 'uri' => 'plugins/activate-raw/{pluginId}', 'controller' => PluginController::class, 'action' => 'activate_raw', 'permission' => 'plugin_activate'],
        ['name' => 'plugins.activate', 'method' => 'post', 'uri' => 'plugins/activate/{plugin}', 'controller' => PluginController::class, 'action' => 'activate', 'permission' => 'plugin_activate'],
        ['name' => 'plugins.deactivate', 'method' => 'post', 'uri' => 'plugins/deactivate/{plugin}', 'controller' => PluginController::class, 'action' => 'deactivate', 'permission' => 'plugin_deactivate'],
        ['name' => 'plugins.delete', 'method' => 'post', 'uri' => 'plugins/delete/{plugin}', 'controller' => PluginController::class, 'action' => 'delete', 'permission' => 'plugin_delete'],

        // Posts
        ['name' => 'posts.meta', 'method' => 'get', 'uri' => 'posts/meta/load', 'controller' => ApiV2PostController::class, 'action' => 'meta', 'permission' => 'post_index'],
        ['name' => 'posts.restore', 'method' => 'post', 'uri' => 'posts/restore/{id}', 'controller' => ApiV2PostController::class, 'action' => 'restore'],
        ['name' => 'posts.bulk_delete', 'method' => 'post', 'uri' => 'posts/bulk_delete', 'controller' => ApiV2PostController::class, 'action' => 'bulkDelete', 'permission' => 'post_bulk_delete'],
        ['name' => 'posts.delete_post', 'method' => 'post', 'uri' => 'posts/{id}/delete', 'controller' => ApiV2PostController::class, 'action' => 'deleteViaPost', 'permission' => 'post_delete'],
        ['name' => 'posts.translations', 'method' => 'get', 'uri' => 'posts/{id}/translations', 'controller' => ApiV2PostController::class, 'action' => 'translations', 'permission' => 'post_show'],
        ['name' => 'posts.bulk_sync_tags', 'method' => 'post', 'uri' => 'posts/bulk_sync_tags', 'controller' => PostController::class, 'action' => 'bulk_sync_tags', 'permission' => 'post_bulk_sync_tags'],
        ['name' => 'posts.generate_demo_posts', 'method' => 'post', 'uri' => 'posts/generate_demo_posts', 'controller' => PostController::class, 'action' => 'generate_demo_posts', 'permission' => 'post_generate_demo_posts'],
        ['name' => 'posts.bulk_clone', 'method' => 'post', 'uri' => 'posts/bulk_clone', 'controller' => PostController::class, 'action' => 'bulk_clone', 'permission' => 'post_bulk_clone'],

        // Settings
        ['name' => 'settings.update', 'method' => 'put', 'uri' => 'settings', 'controller' => SettingController::class, 'action' => 'update', 'permission' => 'setting_edit'],
        ['name' => 'settings.smtp_test', 'method' => 'post', 'uri' => 'settings/smtp/test', 'controller' => SettingController::class, 'action' => 'smtp_test', 'permission' => 'setting_edit'],
        ['name' => 'settings.google_test', 'method' => 'get', 'uri' => 'settings/google/test', 'controller' => SettingController::class, 'action' => 'google_test', 'permission' => 'setting_edit'],
        ['name' => 'settings.quick.add', 'method' => 'post', 'uri' => 'settings/quick/add', 'controller' => SettingController::class, 'action' => 'add_quick_link', 'permission' => 'setting_edit'],
        ['name' => 'settings.quick.remove', 'method' => 'post', 'uri' => 'settings/quick/remove', 'controller' => SettingController::class, 'action' => 'remove_quick_link', 'permission' => 'setting_edit'],

        // Tags
        ['name' => 'tags.create_type', 'method' => 'get', 'uri' => 'tags/type/create', 'controller' => TagController::class, 'action' => 'create_type', 'permission' => 'tag_create_type'],
        ['name' => 'tags.store_type', 'method' => 'post', 'uri' => 'tags/type/store', 'controller' => TagController::class, 'action' => 'store_type', 'permission' => 'tag_create_type'],
        ['name' => 'tags.bulk_create', 'method' => 'get', 'uri' => 'tags/bulk_create', 'controller' => TagController::class, 'action' => 'bulk_create', 'permission' => 'tag_bulk_create'],
        ['name' => 'tags.bulk_store', 'method' => 'post', 'uri' => 'tags/bulk_store', 'controller' => TagController::class, 'action' => 'bulk_store', 'permission' => 'tag_bulk_create'],
        ['name' => 'tags.import_csv', 'method' => 'post', 'uri' => 'tags/import_csv', 'controller' => TagController::class, 'action' => 'import_csv', 'permission' => 'tag_import_csv'],
        ['name' => 'tags.keywords.update', 'method' => 'post', 'uri' => 'tags/{id}/keywords/update', 'controller' => TagController::class, 'action' => 'update_keyword', 'permission' => 'tag_keyword_edit'],
        ['name' => 'tags.bulk_set_parent', 'method' => 'post', 'uri' => 'tags/bulk_set_parent', 'controller' => TagController::class, 'action' => 'bulk_set_parent', 'permission' => 'tag_edit'],
        ['name' => 'tags.get_languages', 'method' => 'post', 'uri' => 'tags/get_languages', 'controller' => TagController::class, 'action' => 'get_languages', 'permission' => 'tag_edit'],

        // Themes / uploads / updates / records
        ['name' => 'themes.upload', 'method' => 'post', 'uri' => 'themes/upload', 'controller' => ThemeController::class, 'action' => 'upload', 'permission' => 'theme_upload'],
        ['name' => 'themes.delete', 'method' => 'post', 'uri' => 'themes/delete/{themeId}', 'controller' => ThemeController::class, 'action' => 'delete', 'permission' => 'theme_delete'],
        ['name' => 'advertisements.manage.update', 'method' => 'post', 'uri' => 'advertisements/{id}/manage/update', 'controller' => ApiV2AdvertisementController::class, 'action' => 'updateViaPost', 'permission' => 'advertisement_edit'],
        ['name' => 'advertisements.manage.destroy', 'method' => 'post', 'uri' => 'advertisements/{id}/manage/delete', 'controller' => ApiV2AdvertisementController::class, 'action' => 'destroyViaPost', 'permission' => 'advertisement_delete'],
        ['name' => 'uploads.image', 'method' => 'post', 'uri' => 'uploads/image', 'controller' => UploadController::class, 'action' => 'upload_image', 'permission' => 'upload_image'],
        ['name' => 'updates.check', 'method' => 'post', 'uri' => 'updates/check', 'controller' => UpdateController::class, 'action' => 'check'],
        ['name' => 'records.bulk_delete', 'method' => 'post', 'uri' => 'records/bulk_delete', 'controller' => RecordController::class, 'action' => 'bulk_delete', 'permission' => 'record_bulk_delete'],
        ['name' => 'records.destroy', 'method' => 'delete', 'uri' => 'records/{id}', 'controller' => RecordController::class, 'action' => 'destroy', 'permission' => 'record_delete'],
        ['name' => 'clicks.destroy', 'method' => 'delete', 'uri' => 'clicks/{id}', 'controller' => ClickController::class, 'action' => 'destroy', 'permission' => 'click_delete'],
        ['name' => 'clicks.bulk_delete', 'method' => 'post', 'uri' => 'clicks/bulk_delete', 'controller' => ClickController::class, 'action' => 'bulk_delete', 'permission' => 'click_bulk_delete'],
        ['name' => 'clicks.summary', 'method' => 'get', 'uri' => 'clicks/summary', 'controller' => ApiV2ClickController::class, 'action' => 'summary', 'permission' => 'click_index'],

        // User account actions
        ['name' => 'users.manage.store', 'method' => 'post', 'uri' => 'users/manage/store', 'controller' => UserController::class, 'action' => 'store', 'permission' => 'user_create'],
        ['name' => 'users.manage.update', 'method' => 'post', 'uri' => 'users/{id}/manage/update', 'controller' => UserController::class, 'action' => 'update', 'permission' => 'user_edit'],
        ['name' => 'users.manage.destroy', 'method' => 'post', 'uri' => 'users/{id}/manage/delete', 'controller' => UserController::class, 'action' => 'destroy', 'permission' => 'user_delete'],
        ['name' => 'users.manage.bulk_delete', 'method' => 'post', 'uri' => 'users/manage/bulk_delete', 'controller' => UserController::class, 'action' => 'bulk_delete', 'permission' => 'user_bulk_delete'],
        ['name' => 'users.account.api.update', 'method' => 'post', 'uri' => 'users/account/api/update', 'controller' => UserController::class, 'action' => 'update_user_api', 'permission' => 'user_api_update'],
        ['name' => 'users.account.email.update', 'method' => 'post', 'uri' => 'users/account/email/update', 'controller' => UserController::class, 'action' => 'update_user_email', 'permission' => 'user_profile_update'],
        ['name' => 'users.account.password.update', 'method' => 'post', 'uri' => 'users/account/password/update', 'controller' => UserController::class, 'action' => 'update_user_password', 'permission' => 'user_profile_update'],
        ['name' => 'users.account.profile.update', 'method' => 'post', 'uri' => 'users/account/profile/update', 'controller' => UserController::class, 'action' => 'update_user_profile', 'permission' => 'user_profile_update'],

        // Websites theme options
        ['name' => 'websites.theme.options.update', 'method' => 'put', 'uri' => 'websites/{id}/options/update', 'controller' => WebsiteController::class, 'action' => 'updateThemeOptions', 'permission' => 'website_edit'],
        ['name' => 'websites.theme.clone', 'method' => 'post', 'uri' => 'websites/{id}/options/clone', 'controller' => WebsiteController::class, 'action' => 'cloneThemeOptions', 'permission' => 'website_edit'],
        ['name' => 'websites.theme.import_default_option', 'method' => 'post', 'uri' => 'websites/{id}/options/import_default_option', 'controller' => WebsiteController::class, 'action' => 'importDefaultOption', 'permission' => 'website_edit'],

        // Comments extra actions
        ['name' => 'comments.users', 'method' => 'post', 'uri' => 'comments/users/search', 'controller' => \Wncms\Http\Controllers\Api\V2\Backend\CommentController::class, 'action' => 'searchUsers', 'permission' => 'comment_create'],
        ['name' => 'comments.update_post', 'method' => 'post', 'uri' => 'comments/{id}/update', 'controller' => \Wncms\Http\Controllers\Api\V2\Backend\CommentController::class, 'action' => 'updateViaPost', 'permission' => 'comment_edit'],
        ['name' => 'comments.delete_post', 'method' => 'post', 'uri' => 'comments/{id}/delete', 'controller' => \Wncms\Http\Controllers\Api\V2\Backend\CommentController::class, 'action' => 'destroyViaPost', 'permission' => 'comment_delete'],

        // Link extras
        ['name' => 'links.bulk_update', 'method' => 'post', 'uri' => 'links/bulk_update', 'controller' => \Wncms\Http\Controllers\Backend\LinkController::class, 'action' => 'bulk_update', 'permission' => 'link_edit'],
        ['name' => 'links.bulk_sync_tags', 'method' => 'post', 'uri' => 'links/bulk_sync_tags', 'controller' => \Wncms\Http\Controllers\Backend\LinkController::class, 'action' => 'bulk_sync_tags', 'permission' => 'link_edit'],
    ],

    'parity' => [
        'excluded_suffixes' => ['index', 'create', 'edit', 'show', 'editor'],
        'excluded_names' => [
            'tools.',
            'index',
            'dashboard',
            'updates',
            'clicks.summary',
            'pages.clone',
            'posts.clone',
            'advertisements.clone',
            'channels.clone',
            'parameters.clone',
            'links.clone',
            'roles.create',
            'tags.keywords.index',
            'users.account.profile.show',
            'users.account.security.show',
            'users.account.api.show',
            'users.account.record.show',
            'websites.theme.options',
            'pages.builder.load',
            'tags.create_type',
            'tags.bulk_create',
            'posts.restore',
            'settings.google_test',
            'themes.index',
            'records.index',
            'packages.index',
            'plugins.index',
            'updates.check',
        ],
    ],
];
