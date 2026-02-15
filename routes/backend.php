<?php

use Illuminate\Support\Facades\Route;
use Wncms\Http\Controllers\ThemeController;

use Wncms\Http\Controllers\Backend\AdvertisementController;
use Wncms\Http\Controllers\Backend\CacheController;
use Wncms\Http\Controllers\Backend\DashboardController;
use Wncms\Http\Controllers\Backend\MenuController;
use Wncms\Http\Controllers\Backend\ModelController;
use Wncms\Http\Controllers\Backend\PageController;
use Wncms\Http\Controllers\Backend\PageBuilderController;
use Wncms\Http\Controllers\Backend\PermissionController;
use Wncms\Http\Controllers\Backend\PluginController;
use Wncms\Http\Controllers\Backend\PostController;
use Wncms\Http\Controllers\Backend\RecordController;
use Wncms\Http\Controllers\Backend\RoleController;
use Wncms\Http\Controllers\Backend\SearchKeywordController;
use Wncms\Http\Controllers\Backend\SettingController;
use Wncms\Http\Controllers\Backend\TagController;
use Wncms\Http\Controllers\Backend\UpdateController;
use Wncms\Http\Controllers\Backend\UploadController;
use Wncms\Http\Controllers\Backend\UserController;
use Wncms\Http\Controllers\Backend\WebsiteController;
use Wncms\Http\Controllers\Backend\PackageController;
use Wncms\Http\Controllers\Backend\ChannelController;
use Wncms\Http\Controllers\Backend\ClickController;
use Wncms\Http\Controllers\Backend\CommentController;
use Wncms\Http\Controllers\Backend\LinkController;
use Wncms\Http\Controllers\Backend\ParameterController;
use Wncms\Http\Controllers\Backend\ToolController;

Route::prefix('panel')->middleware(['auth', 'is_installed', 'has_website'])->group(function () {

    //starter_model for model StarterModel
    // Route::get('starter_models', [StarterModelController::class, 'index'])->middleware('can:starter_model_index')->name('starter_models.index');
    // Route::get('starter_models/create', [StarterModelController::class, 'create'])->middleware('can:starter_model_create')->name('starter_models.create');
    // Route::get('starter_models/create/{id}', [StarterModelController::class, 'create'])->middleware('can:starter_model_clone')->name('starter_models.clone');
    // Route::get('starter_models/{id}/edit', [StarterModelController::class, 'edit'])->middleware('can:starter_model_edit')->name('starter_models.edit');
    // Route::post('starter_models/store', [StarterModelController::class, 'store'])->middleware('can:starter_model_create')->name('starter_models.store');
    // Route::patch('starter_models/{id}', [StarterModelController::class, 'update'])->middleware('can:starter_model_edit')->name('starter_models.update');
    // Route::delete('starter_models/{id}', [StarterModelController::class, 'destroy'])->middleware('can:starter_model_delete')->name('starter_models.destroy');
    // Route::post('starter_models/bulk_delete', [StarterModelController::class, 'bulk_delete'])->middleware('can:starter_model_bulk_delete')->name('starter_models.bulk_delete');

    Route::prefix('tools')->name('tools.')->group(function () {
        Route::get('/', [ToolController::class, 'index'])->name('index');
    });

    //advertisement
    Route::prefix('advertisements')->controller(AdvertisementController::class)->group(function () {
        Route::get('/', 'index')->middleware('can:advertisement_index')->name('advertisements.index');
        Route::get('/create', 'create')->middleware('can:advertisement_create')->name('advertisements.create');
        Route::get('/clone/{id}', 'create')->middleware('can:advertisement_clone')->name('advertisements.clone');
        Route::get('/create/{id}', 'create')->middleware('can:advertisement_clone')->name('advertisements.clone');
        Route::get('/{id}/edit', 'edit')->middleware('can:advertisement_edit')->name('advertisements.edit');
        Route::post('/store', 'store')->middleware('can:advertisement_create')->name('advertisements.store');
        Route::patch('/{id}', 'update')->middleware('can:advertisement_edit')->name('advertisements.update');
        Route::delete('/{id}', 'destroy')->middleware('can:advertisement_delete')->name('advertisements.destroy');
        Route::post('/bulk_delete', 'bulk_delete')->middleware('can:advertisement_bulk_delete')->name('advertisements.bulk_delete');
    });

    //banner
    // Route::prefix('banners')->controller(BannerController::class)->group(function () {
    //     Route::get('/', 'index')->middleware('can:banner_index')->name('banners.index');
    //     Route::get('/create', 'create')->middleware('can:banner_create')->name('banners.create');
    //     Route::get('/clone/{id}', 'create')->middleware('can:banner_clone')->name('banners.clone');
    //     Route::post('/clone', 'bulk_clone')->middleware('can:banner_clone')->name('banners.clone.bulk');
    //     Route::get('/{id}/edit', 'edit')->middleware('can:banner_edit')->name('banners.edit');
    //     Route::get('/{id}', 'show')->middleware('can:banner_show')->name('banners.show');
    //     Route::post('/store', 'store')->middleware('can:banner_create')->name('banners.store');
    //     Route::patch('/{id}', 'update')->middleware('can:banner_edit')->name('banners.update');
    //     Route::delete('/{id}', 'destroy')->middleware('can:banner_delete')->name('banners.destroy');
    //     Route::post('/bulk_delete', 'bulk_delete')->middleware('can:banner_bulk_delete')->name('banners.bulk_delete');
    // });

    //cache
    Route::prefix('cache')->controller(CacheController::class)->group(function () {
        Route::post('/flush', 'flush')->middleware('can:cache_flush')->name('cache.flush');
        Route::post('/flush/{tag}', 'flush')->middleware('can:cache_flush')->name('cache.flush.tag');
        Route::post('/clear/{key}', 'clear')->middleware('can:cache_clear')->name('cache.clear');
        Route::post('/clear/{tag}/{key}', 'clear')->middleware('can:cache_clear')->name('cache.clear.tag');
    });

    //channel
    Route::prefix('channels')->controller(ChannelController::class)->group(function () {
        Route::get('', 'index')->middleware('can:channel_index')->name('channels.index');
        Route::get('/create', 'create')->middleware('can:channel_create')->name('channels.create');
        Route::get('/create/{id}', 'create')->middleware('can:channel_clone')->name('channels.clone');
        Route::get('/{id}/edit', 'edit')->middleware('can:channel_edit')->name('channels.edit');
        Route::post('/store', 'store')->middleware('can:channel_create')->name('channels.store');
        Route::patch('/{id}', 'update')->middleware('can:channel_edit')->name('channels.update');
        Route::delete('/{id}', 'destroy')->middleware('can:channel_delete')->name('channels.destroy');
        Route::post('/bulk_delete', 'bulk_delete')->middleware('can:channel_bulk_delete')->name('channels.bulk_delete');
    });

    // comment
    Route::prefix('comments')->controller(CommentController::class)->group(function () {
        Route::post('/store', 'store')->middleware('can:comment_create')->name('comments.store');
        Route::delete('/{id}', 'destroy')->middleware('can:comment_delete')->name('comments.destroy');
    });

    //click
    Route::prefix('clicks')->controller(ClickController::class)->group(function () {
        Route::get('', 'index')->middleware('can:click_index')->name('clicks.index');
        Route::delete('/{id}', 'destroy')->middleware('can:click_delete')->name('clicks.destroy');
        Route::post('/bulk_delete', 'bulk_delete')->middleware('can:click_bulk_delete')->name('clicks.bulk_delete');
    });

    //dashboard
    Route::controller(DashboardController::class)->group(function () {
        Route::get('dashboard', 'show_dashboard')->name('dashboard');
        Route::post('switch_website', 'switch_website')->name('dashboard.switch_website');
    });

    //link
    Route::prefix('links')->controller(LinkController::class)->group(function () {
        Route::get('/', 'index')->middleware('can:link_index')->name('links.index');
        Route::get('create', 'create')->middleware('can:link_create')->name('links.create');
        Route::get('create/{id}', 'create')->where('id', '[0-9]+')->middleware('can:link_clone')->name('links.clone');
        Route::get('{id}/edit', 'edit')->where('id', '[0-9]+')->middleware('can:link_edit')->name('links.edit');
        Route::post('store', 'store')->middleware('can:link_create')->name('links.store');
        Route::patch('{id}', 'update')->where('id', '[0-9]+')->middleware('can:link_edit')->name('links.update');
        Route::delete('{id}', 'destroy')->where('id', '[0-9]+')->middleware('can:link_delete')->name('links.destroy');
        Route::post('bulk_delete', 'bulk_delete')->middleware('can:link_bulk_delete')->name('links.bulk_delete');
        Route::post('bulk_update', 'bulk_update')->middleware('can:link_edit')->name('links.bulk_update');
        Route::post('/bulk_sync_tags', 'bulk_sync_tags')->middleware('can:link_edit')->name('links.bulk_sync_tags');
    });

    //menu
    Route::prefix('menus')->controller(MenuController::class)->group(function () {
        Route::post('/edit_menu_item', 'edit_menu_item')->middleware('can:menu_edit')->name('menus.edit_menu_item');
        Route::post('/get_menu_item', 'get_menu_item')->middleware('can:menu_edit')->name('menus.get_menu_item');
        Route::get('/', 'index')->middleware('can:menu_index')->name('menus.index');
        Route::get('/create', 'create')->middleware('can:menu_create')->name('menus.create');
        Route::get('/{id}/edit', 'edit')->middleware('can:menu_edit')->name('menus.edit');
        Route::post('/store', 'store')->middleware('can:menu_create')->name('menus.store');
        Route::patch('/{id}', 'update')->middleware('can:menu_edit')->name('menus.update');
        Route::delete('/{id}', 'destroy')->middleware('can:menu_delete')->name('menus.destroy');
        Route::post('/clone', 'clone')->middleware('can:menu_create')->name('menus.clone');
    });

    //model
    Route::prefix('models')->controller(ModelController::class)->group(function () {
        Route::post('/update', 'update')->name('models.update');
        Route::post('/bulk_delete', 'bulk_delete')->name('models.bulk_delete');
        Route::post('/bulk_force_delete', 'bulk_force_delete')->name('models.bulk_force_delete');
    });

    //page
    Route::prefix('pages')->group(function () {

        Route::prefix('{page}/builder')->controller(PageBuilderController::class)->group(function () {
            Route::get('/editor', 'editor')->middleware('can:page_edit')->name('pages.builder.editor');
            Route::get('/load', 'load')->middleware('can:page_edit')->name('pages.builder.load');
            Route::post('/save', 'save')->middleware('can:page_edit')->name('pages.builder.save');
        });

        Route::controller(PageController::class)->group(function () {
            Route::get('/', 'index')->middleware('can:page_index')->name('pages.index');
            Route::post('/create_theme_pages', 'create_theme_pages')->middleware('can:page_create')->name('pages.create_theme_pages');
            Route::get('/create', 'create')->middleware('can:page_create')->name('pages.create');
            Route::get('/clone/{id}', 'create')->middleware('can:page_clone')->name('pages.clone');
            Route::get('/{id}/edit', 'edit')->middleware('can:page_edit')->name('pages.edit');
            Route::get('/{id}', 'show')->middleware('can:page_show')->name('pages.show');
            Route::post('/store', 'store')->middleware('can:page_create')->name('pages.store');
            Route::patch('/{id}', 'update')->middleware('can:page_edit')->name('pages.update');
            Route::delete('/{id}', 'destroy')->middleware('can:page_delete')->name('pages.destroy');
            Route::post('/templates', 'templates')->middleware('can:page_create')->name('pages.templates');
            Route::get('/{id}/editor', 'editor')->middleware('can:page_create')->name('pages.editor');
            Route::post('/widget', 'widget')->middleware('can:page_edit')->name('pages.widget');
            Route::post('/bulk_delete', 'bulk_delete')->middleware('can:page_bulk_delete')->name('pages.bulk_delete');
        });
    });

    // package
    Route::prefix('packages')->controller(PackageController::class)->group(function () {
        Route::get('/', 'index')->middleware('can:package_index')->name('packages.index');
        Route::post('/', 'check')->middleware('can:package_index')->name('packages.check');
        Route::post('/{key}/activate', 'activate')->middleware('can:package_edit')->name('packages.activate');
        Route::post('/{key}/deactivate', 'deactivate')->middleware('can:package_edit')->name('packages.deactivate');
        // Route::post('/add', 'add')->middleware('can:package_edit')->name('packages.add');
        // Route::post('/update', 'update')->middleware('can:package_edit')->name('packages.update');
        // Route::post('/remove', 'remove')->middleware('can:package_edit')->name('packages.remove');
    });

    //parameter
    Route::prefix('parameters')->controller(ParameterController::class)->group(function () {
        Route::get('', 'index')->middleware('can:parameter_index')->name('parameters.index');
        Route::get('/create', 'create')->middleware('can:parameter_create')->name('parameters.create');
        Route::get('/create/{id}', 'create')->middleware('can:parameter_clone')->name('parameters.clone');
        Route::get('/{id}/edit', 'edit')->middleware('can:parameter_edit')->name('parameters.edit');
        Route::post('/store', 'store')->middleware('can:parameter_create')->name('parameters.store');
        Route::patch('/{id}', 'update')->middleware('can:parameter_edit')->name('parameters.update');
        Route::delete('/{id}', 'destroy')->middleware('can:parameter_delete')->name('parameters.destroy');
        Route::post('/bulk_delete', 'bulk_delete')->middleware('can:parameter_bulk_delete')->name('parameters.bulk_delete');
    });

    //permission
    Route::prefix('permissions')->controller(PermissionController::class)->group(function () {
        Route::get('/', 'index')->middleware('can:permission_index')->name('permissions.index');
        Route::get('/create', 'create')->middleware('can:permission_create')->name('permissions.create');
        Route::get('/{id}/edit', 'edit')->middleware('can:permission_edit')->name('permissions.edit');
        Route::get('/{id}', 'show')->middleware('can:permission_show')->name('permissions.show');
        Route::post('/store', 'store')->middleware('can:permission_create')->name('permissions.store');
        Route::patch('/{id}', 'update')->middleware('can:permission_edit')->name('permissions.update');
        Route::delete('/{id}', 'destroy')->middleware('can:permission_delete')->name('permissions.destroy');
        Route::post('/bulk_assign_roles', 'bulk_assign_roles')->middleware('can:permission_edit')->name('permissions.bulk_assign_roles');
        Route::post('/bulk_remove_roles', 'bulk_remove_roles')->middleware('can:permission_edit')->name('permissions.bulk_remove_roles');
        Route::post('/bulk_delete', 'bulk_delete')->middleware('can:permission_bulk_delete')->name('permissions.bulk_delete');
    });

    //plugin
    Route::prefix('plugins')->controller(PluginController::class)->group(function () {
        Route::get('/', 'index')->middleware('can:plugin_index')->name('plugins.index');
        Route::post('/upload', 'upload')->middleware('can:plugin_upload')->name('plugins.upload');
        Route::post('/upgrade/{plugin}', 'upgrade')->middleware('can:plugin_activate')->name('plugins.upgrade');
        Route::post('/activate-raw/{pluginId}', 'activate_raw')->middleware('can:plugin_activate')->name('plugins.activate_raw');
        Route::post('/activate/{plugin}', 'activate')->middleware('can:plugin_activate')->name('plugins.activate');
        Route::post('/deactivate/{plugin}', 'deactivate')->middleware('can:plugin_deactivate')->name('plugins.deactivate');
        Route::post('/delete/{plugin}', 'delete')->middleware('can:plugin_delete')->name('plugins.delete');
    });

    //post
    Route::prefix('posts')->controller(PostController::class)->group(function () {
        Route::get('/restore/{id}', 'restore')->name('posts.restore');
        Route::get('/', 'index')->middleware('can:post_index')->name('posts.index');
        Route::get('/create', 'create')->middleware('can:post_create')->name('posts.create');
        Route::get('/create/{post}', 'create')->middleware('can:post_clone')->name('posts.clone');
        Route::get('/{id}/edit', 'edit')->middleware('can:post_edit')->name('posts.edit');
        Route::get('/{id}', 'show')->middleware('can:post_show')->name('posts.show');
        Route::post('/store', 'store')->middleware('can:post_create')->name('posts.store');
        Route::patch('/{id}', 'update')->middleware('can:post_edit')->name('posts.update');
        Route::delete('/{id}', 'destroy')->middleware('can:post_delete')->name('posts.destroy');
        Route::post('/bulk_sync_tags', 'bulk_sync_tags')->middleware('can:post_bulk_sync_tags')->name('posts.bulk_sync_tags');
        Route::post('/generate_demo_posts', 'generate_demo_posts')->middleware('can:post_generate_demo_posts')->name('posts.generate_demo_posts');
        // Route::post('/bulk_set_websites', 'bulk_set_websites')->middleware('can:post_bulk_set_websites')->name('posts.bulk_set_websites');
        Route::post('/bulk_clone', 'bulk_clone')->middleware('can:post_bulk_clone')->name('posts.bulk_clone');
    });

    //search_keyword
    Route::prefix('search_keywords')->controller(SearchKeywordController::class)->group(function () {
        Route::get('/', 'index')->middleware('can:search_keyword_index')->name('search_keywords.index');
        Route::get('/create', 'create')->middleware('can:search_keyword_create')->name('search_keywords.create');
        Route::get('/{id}/edit', 'edit')->middleware('can:search_keyword_edit')->name('search_keywords.edit');
        Route::post('/store', 'store')->middleware('can:search_keyword_create')->name('search_keywords.store');
        Route::patch('/{id}', 'update')->middleware('can:search_keyword_edit')->name('search_keywords.update');
        Route::post('/bulk_delete', 'bulk_delete')->middleware('can:search_keyword_bulk_delete')->name('search_keywords.bulk_delete');
        Route::delete('/{id}', 'destroy')->middleware('can:search_keyword_delete')->name('search_keywords.destroy');
    });

    //setting
    Route::prefix('settings')->controller(SettingController::class)->group(function () {
        Route::get('/', 'index')->middleware('can:setting_index')->name('settings.index');
        Route::put('/', 'update')->middleware('can:setting_edit')->name('settings.update');
        Route::post('/smtp/test', 'smtp_test')->middleware('can:setting_edit')->name('settings.smtp_test');
        Route::post('/quick/add', 'add_quick_link')->middleware('can:setting_edit')->name('settings.quick.add');
        Route::post('/quick/remove', 'remove_quick_link')->middleware('can:setting_edit')->name('settings.quick.remove');
    });

    //theme
    Route::prefix('themes')->controller(ThemeController::class)->group(function () {
        Route::get('/', 'index')->middleware('can:theme_index')->name('themes.index');
        Route::post('/upload', 'upload')->middleware('can:theme_upload')->name('themes.upload');
        Route::post('/delete/{themeId}', 'delete')->middleware('can:theme_delete')->name('themes.delete');
    });

    //record
    Route::prefix('records')->controller(RecordController::class)->group(function () {
        Route::get('/', 'index')->middleware('can:record_index')->name('records.index');
        Route::delete('/{id}', 'destroy')->middleware('can:record_delete')->name('records.destroy');
        Route::post('/bulk_delete', 'bulk_delete')->middleware('can:record_bulk_delete')->name('records.bulk_delete');
    });

    //role
    Route::prefix('roles')->controller(RoleController::class)->group(function () {
        Route::get('/', 'index')->middleware('can:role_index')->name('roles.index');
        Route::get('/create', 'create')->middleware('can:role_create')->name('roles.create');
        Route::get('/{role}/edit', 'edit')->middleware('can:role_edit')->name('roles.edit');
        Route::get('/{role}', 'show')->middleware('can:role_show')->name('roles.show');
        Route::post('/store', 'store')->middleware('can:role_create')->name('roles.store');
        Route::patch('/{role}', 'update')->middleware('can:role_edit')->name('roles.update');
        Route::delete('/{role}', 'destroy')->middleware('can:role_delete')->name('roles.destroy');
    });

    //tag
    Route::prefix('tags')->controller(TagController::class)->group(function () {
        Route::get('/', 'index')->middleware('can:tag_index')->name('tags.index');
        Route::get('/type/create', 'create_type')->middleware('can:tag_create_type')->name('tags.create_type');
        Route::post('/type/store', 'store_type')->middleware('can:tag_create_type')->name('tags.store_type');
        // Route::get('/type/{type?}', 'index')->middleware('can:tag_index')->name('tags.index.type');
        Route::get('/bulk_create', 'bulk_create')->middleware('can:tag_bulk_create')->name('tags.bulk_create');
        Route::post('/bulk_store', 'bulk_store')->middleware('can:tag_bulk_create')->name('tags.bulk_store');
        Route::post('/import_csv', 'import_csv')->middleware('can:tag_import_csv')->name('tags.import_csv');
        Route::get('/keywords', 'show_keyword_index')->middleware('can:tag_keyword_index')->name('tags.keywords.index');
        Route::post('/{id}/keywords/update', 'update_keyword')->middleware('can:tag_keyword_edit')->name('tags.keywords.update');
        Route::get('/create', 'create')->middleware('can:tag_create')->name('tags.create');
        Route::get('/{id}/edit', 'edit')->middleware('can:tag_edit')->name('tags.edit');
        Route::get('/{id}', 'show')->middleware('can:tag_show')->name('tags.show');
        Route::post('/store', 'store')->middleware('can:tag_create')->name('tags.store');
        Route::patch('/{id}', 'update')->middleware('can:tag_edit')->name('tags.update');
        Route::delete('/{id}', 'destroy')->middleware('can:tag_delete')->name('tags.destroy');
        Route::post('/bulk_delete', 'bulk_delete')->middleware('can:tag_bulk_delete')->name('tags.bulk_delete');
        Route::post('/bulk_set_parent', 'bulk_set_parent')->middleware('can:tag_edit')->name('tags.bulk_set_parent');
        Route::post('/get_languages', 'get_languages')->middleware('can:tag_edit')->name('tags.get_languages');
    });

    //upload
    Route::prefix('uploads')->controller(UploadController::class)->group(function () {
        Route::post('/image', 'upload_image')->middleware('can:upload_image')->name('uploads.image');
    });

    //update
    Route::prefix('updates')->controller(UpdateController::class)->group(function () {
        Route::get('/', 'index')->name('updates');
        Route::post('/check', 'check')->name('updates.check');
    });

    //user
    Route::prefix('users')->controller(UserController::class)->group(function () {
        //account
        Route::prefix('account')->group(function () {
            Route::get('/profile', 'show_user_profile')->middleware('can:user_profile_show')->name('users.account.profile.show');
            Route::post('/profile/update', 'update_user_profile')->middleware('can:user_profile_update')->name('users.account.profile.update');
            Route::get('/security', 'show_user_security')->middleware('can:user_security_show')->name('users.account.security.show');
            Route::get('/api', 'show_user_api')->middleware('can:user_api_show')->name('users.account.api.show');
            Route::post('/api/update', 'update_user_api')->middleware('can:user_api_update')->name('users.account.api.update');
            Route::get('/record', 'show_user_record')->middleware('can:user_record_show')->name('users.account.record.show');
            Route::post('/email/update', 'update_user_email')->middleware('can:user_profile_update')->name('users.account.email.update');
            Route::post('/password/update', 'update_user_password')->middleware('can:user_profile_update')->name('users.account.password.update');
        });

        Route::get('/', 'index')->middleware('can:user_index')->name('users.index');
        Route::get('/create', 'create')->middleware('can:user_create')->name('users.create');
        Route::get('/{id}/edit', 'edit')->middleware('can:user_edit')->name('users.edit');
        Route::get('/{id}', 'show')->middleware('can:user_show')->name('users.show');
        Route::post('/store', 'store')->middleware('can:user_create')->name('users.store');
        Route::patch('/{id}', 'update')->middleware('can:user_edit')->name('users.update');
        Route::delete('/{id}', 'destroy')->middleware('can:user_delete')->name('users.destroy');
        Route::post('/bulk_delete', 'bulk_delete')->middleware('can:user_bulk_delete')->name('users.bulk_delete');
    });

    //website
    Route::prefix('websites')->controller(WebsiteController::class)->group(function () {
        Route::get('/', 'index')->middleware('can:website_index')->name('websites.index');
        Route::get('/create', 'create')->middleware('can:website_create')->name('websites.create');
        Route::get('/{id}/edit', 'edit')->middleware('can:website_edit')->name('websites.edit');
        Route::get('/{id}', 'show')->middleware('can:website_show')->name('websites.show');
        Route::post('/store', 'store')->middleware('can:website_create')->name('websites.store');
        Route::patch('/{id}', 'update')->middleware('can:website_edit')->name('websites.update');
        Route::delete('/{id}', 'destroy')->middleware('can:website_delete')->name('websites.destroy');
        Route::get('/{id}/options', 'editThemeOptions')->middleware('can:website_edit')->name('websites.theme.options');
        Route::put('/{id}/options/update', 'updateThemeOptions')->middleware('can:website_edit')->name('websites.theme.options.update');
        Route::post('/{id}/options/clone', 'cloneThemeOptions')->middleware('can:website_edit')->name('websites.theme.clone');
        Route::post('/{id}/options/import_default_option', 'importDefaultOption')->middleware('can:website_edit')->name('websites.theme.import_default_option');
    });

    //custom backend route
    if (file_exists(base_path('routes/custom_backend.php'))) {
        include base_path('routes/custom_backend.php');
    }
});
