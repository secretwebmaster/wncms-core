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
use Wncms\Http\Controllers\Api\V2\Backend\LinkController as ApiV2LinkController;
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
            'controller' => ApiV2LinkController::class,
            'permissions' => [
                'index' => 'link_index',
                'show' => 'link_edit',
                'store' => 'link_create',
                'update' => 'link_edit',
                'destroy' => 'link_delete',
                'bulk_delete' => 'link_bulk_delete',
            ],
            'enable_bulk_delete' => false,
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
        ['name' => 'links.bulk_update', 'method' => 'post', 'uri' => 'links/bulk_update', 'controller' => ApiV2LinkController::class, 'action' => 'bulkUpdate', 'permission' => 'link_edit'],
        ['name' => 'links.bulk_sync_tags', 'method' => 'post', 'uri' => 'links/bulk_sync_tags', 'controller' => ApiV2LinkController::class, 'action' => 'bulkSyncTags', 'permission' => 'link_edit'],
    ],

    'coverage' => [
        'reference_domain' => 'links',
        'domains' => [
            'websites' => [
                'label' => 'Websites',
                'backend_routes' => [
                    'websites.index',
                    'websites.create',
                    'websites.edit',
                    'websites.show',
                    'websites.store',
                    'websites.update',
                    'websites.destroy',
                    'websites.theme.options',
                    'websites.theme.options.update',
                    'websites.theme.clone',
                    'websites.theme.import_default_option',
                ],
                'api_v2_resources' => ['websites'],
                'api_v2_actions' => [
                    'websites.theme.options.update',
                    'websites.theme.clone',
                    'websites.theme.import_default_option',
                ],
                'cli_commands' => ['wncms:update-website'],
                'mcp_tools' => [],
                'docs' => ['documentations/manual/api/endpoints/websites.md'],
                'tests' => ['tests/Feature/DashboardControllerTest.php'],
            ],
            'users' => [
                'label' => 'Users',
                'backend_routes' => [
                    'users.index',
                    'users.create',
                    'users.edit',
                    'users.show',
                    'users.store',
                    'users.update',
                    'users.destroy',
                    'users.bulk_delete',
                    'users.account.profile.show',
                    'users.account.profile.update',
                    'users.account.security.show',
                    'users.account.api.show',
                    'users.account.api.update',
                    'users.account.record.show',
                    'users.account.email.update',
                    'users.account.password.update',
                ],
                'api_v2_resources' => ['users'],
                'api_v2_actions' => [
                    'users.manage.store',
                    'users.manage.update',
                    'users.manage.destroy',
                    'users.manage.bulk_delete',
                    'users.account.api.update',
                    'users.account.email.update',
                    'users.account.password.update',
                    'users.account.profile.update',
                ],
                'cli_commands' => [],
                'mcp_tools' => [],
                'docs' => [
                    'documentations/manual/api/endpoints/users.md',
                    'documentations/manual/user/dashboard/users.md',
                    'documentations/manual/developer/event/users.md',
                ],
                'tests' => [
                    'tests/Feature/FrontendRegistrationSettingTest.php',
                    'tests/Feature/GoogleLoginTest.php',
                ],
            ],
            'posts' => [
                'label' => 'Posts',
                'backend_routes' => [
                    'posts.index',
                    'posts.create',
                    'posts.clone',
                    'posts.edit',
                    'posts.show',
                    'posts.store',
                    'posts.update',
                    'posts.destroy',
                    'posts.restore',
                    'posts.bulk_sync_tags',
                    'posts.generate_demo_posts',
                    'posts.bulk_clone',
                ],
                'api_v2_resources' => ['posts'],
                'api_v2_actions' => [
                    'posts.meta',
                    'posts.restore',
                    'posts.bulk_delete',
                    'posts.delete_post',
                    'posts.translations',
                    'posts.bulk_sync_tags',
                    'posts.generate_demo_posts',
                    'posts.bulk_clone',
                ],
                'cli_commands' => ['wncms:import-demo'],
                'mcp_tools' => [],
                'docs' => [
                    'documentations/manual/api/endpoints/posts.md',
                    'documentations/manual/user/dashboard/posts.md',
                    'documentations/manual/developer/manager/post-manager.md',
                    'documentations/manual/developer/event/posts.md',
                ],
                'tests' => [
                    'tests/Feature/PostControllerTest.php',
                    'tests/Feature/PostTest.php',
                ],
            ],
            'pages' => [
                'label' => 'Pages',
                'backend_routes' => [
                    'pages.index',
                    'pages.create',
                    'pages.clone',
                    'pages.edit',
                    'pages.show',
                    'pages.store',
                    'pages.update',
                    'pages.destroy',
                    'pages.builder.editor',
                    'pages.builder.load',
                    'pages.builder.save',
                    'pages.create_theme_pages',
                    'pages.templates',
                    'pages.editor',
                    'pages.widget',
                    'pages.bulk_delete',
                ],
                'api_v2_resources' => ['pages'],
                'api_v2_actions' => [
                    'pages.builder.load',
                    'pages.builder.save',
                    'pages.create_theme_pages',
                    'pages.templates',
                    'pages.widget',
                    'pages.bulk_delete',
                ],
                'cli_commands' => [],
                'mcp_tools' => [],
                'docs' => [
                    'documentations/manual/api/endpoints/pages.md',
                    'documentations/manual/user/dashboard/pages.md',
                ],
                'tests' => [],
            ],
            'tags' => [
                'label' => 'Tags',
                'backend_routes' => [
                    'tags.index',
                    'tags.create_type',
                    'tags.store_type',
                    'tags.bulk_create',
                    'tags.bulk_store',
                    'tags.import_csv',
                    'tags.keywords.index',
                    'tags.keywords.update',
                    'tags.create',
                    'tags.edit',
                    'tags.show',
                    'tags.store',
                    'tags.update',
                    'tags.destroy',
                    'tags.bulk_delete',
                    'tags.bulk_set_parent',
                    'tags.get_languages',
                ],
                'api_v2_resources' => ['tags'],
                'api_v2_actions' => [
                    'tags.create_type',
                    'tags.store_type',
                    'tags.bulk_create',
                    'tags.bulk_store',
                    'tags.import_csv',
                    'tags.keywords.update',
                    'tags.bulk_set_parent',
                    'tags.get_languages',
                ],
                'cli_commands' => [],
                'mcp_tools' => [],
                'docs' => [
                    'documentations/manual/api/endpoints/tags.md',
                    'documentations/manual/user/dashboard/tags.md',
                ],
                'tests' => [],
            ],
            'menus' => [
                'label' => 'Menus',
                'backend_routes' => [
                    'menus.index',
                    'menus.create',
                    'menus.edit',
                    'menus.store',
                    'menus.update',
                    'menus.destroy',
                    'menus.edit_menu_item',
                    'menus.get_menu_item',
                    'menus.search_source_items',
                    'menus.clone',
                ],
                'api_v2_resources' => ['menus'],
                'api_v2_actions' => [
                    'menus.edit_menu_item',
                    'menus.get_menu_item',
                    'menus.search_source_items',
                    'menus.clone',
                ],
                'cli_commands' => [],
                'mcp_tools' => [],
                'docs' => [
                    'documentations/manual/api/endpoints/menus.md',
                    'documentations/manual/developer/event/menus.md',
                ],
                'tests' => [
                    'tests/Feature/MenuSourceControllerTest.php',
                    'tests/Unit/MenuManagerSourceResolutionTest.php',
                ],
            ],
            'links' => [
                'label' => 'Links',
                'reference' => true,
                'surface_statuses' => [
                    'api_v2' => 'Partial',
                    'mcp' => 'Complete',
                    'docs' => 'Complete',
                    'tests' => 'Complete',
                ],
                'backend_routes' => [
                    'links.index',
                    'links.create',
                    'links.clone',
                    'links.edit',
                    'links.store',
                    'links.update',
                    'links.destroy',
                    'links.bulk_delete',
                    'links.bulk_update',
                    'links.bulk_sync_tags',
                ],
                'api_v2_resources' => ['links'],
                'api_v2_actions' => [
                    'links.bulk_update',
                    'links.bulk_sync_tags',
                ],
                'cli_commands' => [
                    'wncms:links:list',
                    'wncms:links:inspect',
                    'wncms:links:create',
                    'wncms:links:update',
                    'wncms:links:delete',
                    'wncms:links:bulk-update',
                    'wncms:links:bulk-sync-tags',
                ],
                'mcp_tools' => [
                    'wncms-links-list',
                    'wncms-links-inspect',
                ],
                'docs' => [
                    'documentations/manual/api/endpoints/links.md',
                    'documentations/manual/user/dashboard/links.md',
                    'documentations/manual/developer/manager/link-manager.md',
                    'documentations/manual/developer/event/links.md',
                    'documentations/manual/developer/command/overview.md',
                    'documentations/manual/developer/mcp/overview.md',
                    'documentations/manual/zh-CN/developer/mcp/overview.md',
                    'documentations/manual/zh-TW/developer/mcp/overview.md',
                ],
                'tests' => [
                    'tests/Feature/LinkHookIntegrationTest.php',
                    'tests/Feature/LinkAutomationCommandTest.php',
                    'tests/Feature/LinkApiV2ControllerTest.php',
                    'tests/Feature/Mcp/LinksToolsTest.php',
                ],
            ],
            'settings' => [
                'label' => 'Settings',
                'backend_routes' => [
                    'settings.index',
                    'settings.update',
                    'settings.smtp_test',
                    'settings.google_test',
                    'settings.quick.add',
                    'settings.quick.remove',
                ],
                'api_v2_actions' => [
                    'settings.update',
                    'settings.smtp_test',
                    'settings.google_test',
                    'settings.quick.add',
                    'settings.quick.remove',
                ],
                'cli_commands' => ['wncms:setting-update'],
                'mcp_tools' => [],
                'docs' => [
                    'documentations/manual/user/dashboard/settings.md',
                    'documentations/manual/developer/event/settings.md',
                ],
                'tests' => [
                    'tests/Feature/ApiAuthSettingsTest.php',
                    'tests/Unit/SessionLifetimeSystemSettingTest.php',
                ],
            ],
            'plugins' => [
                'label' => 'Plugins',
                'backend_routes' => [
                    'plugins.index',
                    'plugins.upload',
                    'plugins.upgrade',
                    'plugins.activate_raw',
                    'plugins.activate',
                    'plugins.deactivate',
                    'plugins.delete',
                ],
                'api_v2_resources' => ['plugins'],
                'api_v2_actions' => [
                    'plugins.upload',
                    'plugins.upgrade',
                    'plugins.activate_raw',
                    'plugins.activate',
                    'plugins.deactivate',
                    'plugins.delete',
                ],
                'cli_commands' => [
                    'wncms:activate-plugin',
                    'wncms:verify-plugin-hooks',
                ],
                'mcp_tools' => [],
                'docs' => [
                    'documentations/manual/developer/plugin/overview.md',
                    'documentations/manual/developer/plugin/create-a-basic-plugin.md',
                ],
                'tests' => [
                    'tests/Unit/PluginActivationCompatibilityValidatorTest.php',
                    'tests/Unit/PluginLifecycleResolutionTest.php',
                    'tests/Unit/PluginLifecycleUpgradeMapTest.php',
                    'tests/Unit/PluginLoadErrorDiagnosticsTest.php',
                ],
            ],
            'themes' => [
                'label' => 'Themes',
                'backend_routes' => [
                    'themes.index',
                    'themes.upload',
                    'themes.delete',
                ],
                'api_v2_resources' => ['themes'],
                'api_v2_actions' => [
                    'themes.upload',
                    'themes.delete',
                ],
                'cli_commands' => [
                    'wncms:create-theme',
                    'wncms:install-default-theme',
                    'wncms:pack-theme-file',
                    'wncms:remove_theme_file',
                ],
                'mcp_tools' => [],
                'docs' => [
                    'documentations/manual/developer/theme/theme-structure.md',
                    'documentations/manual/developer/theme/config.md',
                    'documentations/manual/developer/event/themes.md',
                ],
                'tests' => [],
            ],
            'updates' => [
                'label' => 'Updates',
                'backend_routes' => [
                    'updates',
                    'updates.check',
                ],
                'api_v2_actions' => ['updates.check'],
                'cli_commands' => ['wncms:update'],
                'mcp_tools' => [],
                'docs' => ['documentations/manual/api/endpoints/updates.md'],
                'tests' => [],
            ],
            'tools' => [
                'label' => 'Tools',
                'backend_routes' => [
                    'tools.index',
                    'tools.install_default_theme',
                    'tools.rerun_core_update',
                ],
                'api_v2_actions' => [
                    'install_default_theme',
                    'rerun_core_update',
                ],
                'cli_commands' => ['wncms:install-default-theme'],
                'mcp_tools' => [],
                'docs' => ['documentations/manual/developer/event/tools.md'],
                'tests' => ['tests/Feature/ToolHookIntegrationTest.php'],
            ],
            'comments' => [
                'label' => 'Comments',
                'backend_routes' => [
                    'comments.users',
                    'comments.store',
                    'comments.update',
                    'comments.destroy',
                ],
                'api_v2_resources' => ['comments'],
                'api_v2_actions' => [
                    'comments.users',
                    'comments.update_post',
                    'comments.delete_post',
                ],
                'cli_commands' => [],
                'mcp_tools' => [],
                'docs' => [],
                'tests' => ['tests/Feature/CommentControllerTest.php'],
            ],
            'advertisements' => [
                'label' => 'Advertisements',
                'backend_routes' => [
                    'advertisements.index',
                    'advertisements.create',
                    'advertisements.clone',
                    'advertisements.edit',
                    'advertisements.store',
                    'advertisements.update',
                    'advertisements.destroy',
                    'advertisements.bulk_delete',
                ],
                'api_v2_resources' => ['advertisements'],
                'api_v2_actions' => [
                    'advertisements.manage.update',
                    'advertisements.manage.destroy',
                ],
                'cli_commands' => [],
                'mcp_tools' => [],
                'docs' => [],
                'tests' => [],
            ],
            'search_keywords' => [
                'label' => 'Search keywords',
                'backend_routes' => [
                    'search_keywords.index',
                    'search_keywords.create',
                    'search_keywords.edit',
                    'search_keywords.store',
                    'search_keywords.update',
                    'search_keywords.bulk_delete',
                    'search_keywords.destroy',
                ],
                'api_v2_resources' => ['search_keywords'],
                'cli_commands' => [],
                'mcp_tools' => [],
                'docs' => [],
                'tests' => [],
            ],
            'channels' => [
                'label' => 'Channels',
                'backend_routes' => [
                    'channels.index',
                    'channels.create',
                    'channels.clone',
                    'channels.edit',
                    'channels.store',
                    'channels.update',
                    'channels.destroy',
                    'channels.bulk_delete',
                ],
                'api_v2_resources' => ['channels'],
                'cli_commands' => [],
                'mcp_tools' => [],
                'docs' => [],
                'tests' => [],
            ],
            'clicks' => [
                'label' => 'Clicks',
                'backend_routes' => [
                    'clicks.index',
                    'clicks.summary',
                    'clicks.destroy',
                    'clicks.bulk_delete',
                ],
                'api_v2_resources' => ['clicks'],
                'api_v2_actions' => [
                    'clicks.destroy',
                    'clicks.bulk_delete',
                    'clicks.summary',
                ],
                'cli_commands' => [],
                'mcp_tools' => [],
                'docs' => [],
                'tests' => [],
            ],
            'parameters' => [
                'label' => 'Parameters',
                'backend_routes' => [
                    'parameters.index',
                    'parameters.create',
                    'parameters.clone',
                    'parameters.edit',
                    'parameters.store',
                    'parameters.update',
                    'parameters.destroy',
                    'parameters.bulk_delete',
                ],
                'api_v2_resources' => ['parameters'],
                'cli_commands' => [],
                'mcp_tools' => [],
                'docs' => [],
                'tests' => [],
            ],
            'api_v2_backend_resources' => [
                'label' => 'API v2 backend resources',
                'backend_routes' => null,
                'api_v2_resources' => [
                    'advertisements',
                    'channels',
                    'comments',
                    'clicks',
                    'links',
                    'menus',
                    'pages',
                    'packages',
                    'parameters',
                    'permissions',
                    'plugins',
                    'posts',
                    'roles',
                    'search_keywords',
                    'tags',
                    'themes',
                    'users',
                    'websites',
                ],
                'api_v2_actions' => [
                    'auth.login',
                    'auth.logout',
                    'auth.me',
                    'i18n.ui',
                    'translations',
                ],
                'cli_commands' => ['wncms:check-backend-api-v2-parity'],
                'mcp_tools' => 'Needs design',
                'docs' => [
                    'documentations/manual/developer/automation/overview.md',
                    'documentations/manual/developer/command/overview.md',
                ],
                'tests' => [
                    'tests/Feature/ApiAuthSettingsTest.php',
                    'tests/Feature/ModelControllerAuthorizationTest.php',
                ],
            ],
        ],
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
