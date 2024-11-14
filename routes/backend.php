<?php

use Illuminate\Support\Facades\Route;
use Wncms\Http\Controllers\ThemeController;

use Wncms\Http\Controllers\Backend\AdvertisementController;
use Wncms\Http\Controllers\Backend\AnalyticsController;
use Wncms\Http\Controllers\Backend\BannerController;
use Wncms\Http\Controllers\Backend\ContactFormController;
use Wncms\Http\Controllers\Backend\ContactFormOptionController;
use Wncms\Http\Controllers\Backend\ContactFormSubmissionController;
use Wncms\Http\Controllers\Backend\CacheController;
use Wncms\Http\Controllers\Backend\DashboardController;
use Wncms\Http\Controllers\Backend\FaqController;
use Wncms\Http\Controllers\Backend\MenuController;
use Wncms\Http\Controllers\Backend\ModelController;
use Wncms\Http\Controllers\Backend\PageController;
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

Route::prefix('panel')->middleware(['auth'])->group(function () {

    //starter_model for model StarterModel
    // Route::get('starter_models', [StarterModelController::class, 'index'])->middleware('can:starter_model_index')->name('starter_models.index');
    // Route::get('starter_models/create', [StarterModelController::class, 'create'])->middleware('can:starter_model_create')->name('starter_models.create');
    // Route::get('starter_models/create/{starterModel}', [StarterModelController::class, 'create'])->middleware('can:starter_model_clone')->name('starter_models.clone');
    // Route::get('starter_models/{starterModel}/edit', [StarterModelController::class, 'edit'])->middleware('can:starter_model_edit')->name('starter_models.edit');
    // Route::post('starter_models/store', [StarterModelController::class, 'store'])->middleware('can:starter_model_create')->name('starter_models.store');
    // Route::patch('starter_models/{starterModel}', [StarterModelController::class, 'update'])->middleware('can:starter_model_edit')->name('starter_models.update');
    // Route::delete('starter_models/{starterModel}', [StarterModelController::class, 'destroy'])->middleware('can:starter_model_delete')->name('starter_models.destroy');
    // Route::post('starter_models/bulk_delete', [StarterModelController::class, 'bulk_delete'])->middleware('can:starter_model_bulk_delete')->name('starter_models.bulk_delete');


    //advertisement
    Route::prefix('advertisements')->controller(AdvertisementController::class)->group(function () {
        Route::get('/', 'index')->middleware('can:advertisement_index')->name('advertisements.index');
        Route::get('/create', 'create')->middleware('can:advertisement_create')->name('advertisements.create');
        Route::get('/clone/{advertisement}', 'clone')->middleware('can:advertisement_clone')->name('advertisements.clone');
        Route::get('/create/{advertisement}', 'create')->middleware('can:advertisement_clone')->name('advertisements.clone');
        Route::get('/{advertisement}/edit', 'edit')->middleware('can:advertisement_edit')->name('advertisements.edit');
        Route::post('/store', 'store')->middleware('can:advertisement_create')->name('advertisements.store');
        Route::patch('/{advertisement}', 'update')->middleware('can:advertisement_edit')->name('advertisements.update');
        Route::delete('/{advertisement}', 'destroy')->middleware('can:advertisement_delete')->name('advertisements.destroy');
        Route::post('/bulk_delete', 'bulk_delete')->middleware('can:advertisement_bulk_delete')->name('advertisements.bulk_delete');
    });


    //Analytics
    Route::prefix('analytics')->controller(AnalyticsController::class)->group(function () {
        Route::get('/', 'index')->middleware('can:analytics_index')->name('analytics.index');
        Route::get('/traffic', 'show_traffic')->middleware('can:analytics_index')->name('analytics.traffic');
        Route::get('/click', 'show_click')->middleware('can:analytics_index')->name('analytics.click');
    });

    //banner
    Route::prefix('banners')->controller(BannerController::class)->group(function () {
        Route::get('/', 'index')->middleware('can:banner_index')->name('banners.index');
        Route::get('/create', 'create')->middleware('can:banner_create')->name('banners.create');
        Route::get('/clone/{banner}', 'create')->middleware('can:banner_clone')->name('banners.clone');
        Route::post('/clone', 'bulk_clone')->middleware('can:banner_clone')->name('banners.clone.bulk');
        Route::get('/{banner}/edit', 'edit')->middleware('can:banner_edit')->name('banners.edit');
        Route::get('/{banner}', 'show')->middleware('can:banner_show')->name('banners.show');
        Route::post('/store', 'store')->middleware('can:banner_create')->name('banners.store');
        Route::patch('/{banner}', 'update')->middleware('can:banner_edit')->name('banners.update');
        Route::delete('/{banner}', 'destroy')->middleware('can:banner_delete')->name('banners.destroy');
        Route::post('/bulk_delete', 'bulk_delete')->middleware('can:banner_bulk_delete')->name('banners.bulk_delete');
    });

    //cache
    Route::prefix('cache')->controller(CacheController::class)->group(function () {
        Route::post('/flush', 'flush')->middleware('can:cache_flush')->name('cache.flush');
        Route::post('/flush/{tag}', 'flush')->middleware('can:cache_flush')->name('cache.flush.tag');
        Route::post('/clear/{key}', 'clear')->middleware('can:cache_clear')->name('cache.clear');
        Route::post('/clear/{tag}/{key}', 'clear')->middleware('can:cache_clear')->name('cache.clear.tag');
    });

    //contact_form
    Route::prefix('contact_forms')->controller(ContactFormController::class)->group(function () {
        Route::get('/', 'index')->middleware('can:contact_form_index')->name('contact_forms.index');
        Route::get('/create', 'create')->middleware('can:contact_form_create')->name('contact_forms.create');
        Route::get('/{contact_form}/edit', 'edit')->middleware('can:contact_form_edit')->name('contact_forms.edit');
        Route::get('/{contact_form}', 'show')->middleware('can:contact_form_show')->name('contact_forms.show');
        Route::post('/store', 'store')->middleware('can:contact_form_create')->name('contact_forms.store');
        Route::patch('/{contact_form}', 'update')->middleware('can:contact_form_edit')->name('contact_forms.update');
        Route::delete('/{contact_form}', 'destroy')->middleware('can:contact_form_delete')->name('contact_forms.destroy');
    });

    //contact_form_option
    Route::prefix('contact_form_options')->controller(ContactFormOptionController::class)->group(function () {
        Route::get('/', 'index')->middleware('can:contact_form_option_index')->name('contact_form_options.index');
        Route::get('/create', 'create')->middleware('can:contact_form_option_create')->name('contact_form_options.create');
        Route::get('/{contact_form_option}/edit', 'edit')->middleware('can:contact_form_option_edit')->name('contact_form_options.edit');
        Route::get('/{contact_form_option}', 'show')->middleware('can:contact_form_option_show')->name('contact_form_options.show');
        Route::post('/store', 'store')->middleware('can:contact_form_option_create')->name('contact_form_options.store');
        Route::patch('/{contact_form_option}', 'update')->middleware('can:contact_form_option_edit')->name('contact_form_options.update');
        Route::delete('/{contact_form_option}', 'destroy')->middleware('can:contact_form_option_delete')->name('contact_form_options.destroy');
    });

    //contact_form_submission
    Route::prefix('contact_form_submissions')->controller(ContactFormSubmissionController::class)->group(function () {
        Route::get('/', 'index')->middleware('can:contact_form_submission_index')->name('contact_form_submissions.index');
        Route::get('/{contact_form_submission}', 'show')->middleware('can:contact_form_submission_show')->name('contact_form_submissions.show');
        Route::delete('/{contact_form_submission}', 'destroy')->middleware('can:contact_form_submission_delete')->name('contact_form_submissions.destroy');
        Route::post('/bulk_delete', 'bulk_delete')->middleware('can:contact_form_submission_bulk_delete')->name('contact_form_submissions.bulk_delete');
        Route::get('/export/{type}', 'export')->middleware('can:contact_form_submission_export')->name('contact_form_submissions.export');
    });

    //dashboard
    Route::controller(DashboardController::class)->group(function () {
        Route::get('dashboard', 'show_dashboard')->name('dashboard');
        Route::post('switch_website', 'switch_website')->name('dashboard.switch_website');
    });

    //faq
    Route::prefix('faqs')->controller(FaqController::class)->group(function () {
        Route::get('/', 'index')->middleware('can:faq_index')->name('faqs.index');
        Route::get('/create', 'create')->middleware('can:faq_create')->name('faqs.create');
        Route::get('/create/{faq}', 'create')->middleware('can:faq_clone')->name('faqs.clone');
        Route::get('/{faq}/edit', 'edit')->middleware('can:faq_edit')->name('faqs.edit');
        Route::post('/store', 'store')->middleware('can:faq_create')->name('faqs.store');
        Route::patch('/{faq}', 'update')->middleware('can:faq_edit')->name('faqs.update');
        Route::delete('/{faq}', 'destroy')->middleware('can:faq_delete')->name('faqs.destroy');
        Route::post('/bulk_delete', 'bulk_delete')->middleware('can:faq_bulk_delete')->name('faqs.bulk_delete');
    });

    //menu
    Route::prefix('menus')->controller(MenuController::class)->group(function () {
        Route::post('/edit_menu_item', 'edit_menu_item')->middleware('can:menu_edit')->name('menus.edit_menu_item');
        Route::post('/get_menu_item', 'get_menu_item')->middleware('can:menu_edit')->name('menus.get_menu_item');
        Route::get('/', 'index')->middleware('can:menu_index')->name('menus.index');
        Route::get('/create', 'create')->middleware('can:menu_create')->name('menus.create');
        Route::get('/{menu}/edit', 'edit')->middleware('can:menu_edit')->name('menus.edit');
        Route::get('/{menu}', 'show')->middleware('can:menu_show')->name('menus.show');
        Route::post('/store', 'store')->middleware('can:menu_create')->name('menus.store');
        Route::patch('/{menu}', 'update')->middleware('can:menu_edit')->name('menus.update');
        Route::delete('/{menu}', 'destroy')->middleware('can:menu_delete')->name('menus.destroy');
        Route::post('/clone', 'clone')->middleware('can:menu_create')->name('menus.clone');
    });


    //model
    Route::prefix('models')->controller(ModelController::class)->group(function () {
        Route::post('/update', 'update')->name('models.update');
        Route::post('/bulk_delete', 'bulk_delete')->name('models.bulk_delete');
        Route::post('/bulk_force_delete', 'bulk_force_delete')->name('models.bulk_force_delete');
    });

    //page
    Route::prefix('pages')->controller(PageController::class)->group(function () {
        Route::get('/', 'index')->middleware('can:page_index')->name('pages.index');
        Route::post('/create_theme_pages', 'create_theme_pages')->middleware('can:page_create')->name('pages.create_theme_pages');
        Route::get('/create', 'create')->middleware('can:page_create')->name('pages.create');
        Route::get('/clone/{page}', 'create')->middleware('can:page_clone')->name('pages.clone');
        Route::get('/{page}/edit', 'edit')->middleware('can:page_edit')->name('pages.edit');
        Route::get('/{page}', 'show')->middleware('can:page_show')->name('pages.show');
        Route::post('/store', 'store')->middleware('can:page_create')->name('pages.store');
        Route::patch('/{page}', 'update')->middleware('can:page_edit')->name('pages.update');
        Route::delete('/{page}', 'destroy')->middleware('can:page_delete')->name('pages.destroy');
        Route::post('/templates', 'templates')->middleware('can:page_create')->name('pages.templates');
        Route::get('/{page}/editor', 'editor')->middleware('can:page_create')->name('pages.editor');
        Route::post('/widget', 'widget')->middleware('can:page_edit')->name('pages.widget');
        Route::post('/bulk_delete', 'bulk_delete')->middleware('can:page_bulk_delete')->name('pages.bulk_delete');
    });

    // package
    Route::prefix('packages')->controller(PackageController::class)->group(function () {
        Route::get('/', 'index')->middleware('can:package_index')->name('packages.index');
        Route::post('/', 'check')->middleware('can:package_index')->name('packages.check');
        Route::post('/add', 'add')->middleware('can:package_edit')->name('packages.add');
        Route::post('/update', 'update')->middleware('can:package_edit')->name('packages.update');
        Route::post('/remove', 'remove')->middleware('can:package_edit')->name('packages.remove');
    });

    //permission
    Route::prefix('permissions')->controller(PermissionController::class)->group(function () {
        Route::get('/', 'index')->middleware('can:permission_index')->name('permissions.index');
        Route::get('/create', 'create')->middleware('can:permission_create')->name('permissions.create');
        Route::get('/{permission}/edit', 'edit')->middleware('can:permission_edit')->name('permissions.edit');
        Route::get('/{permission}', 'show')->middleware('can:permission_show')->name('permissions.show');
        Route::post('/store', 'store')->middleware('can:permission_create')->name('permissions.store');
        Route::patch('/{permission}', 'update')->middleware('can:permission_edit')->name('permissions.update');
        Route::delete('/{permission}', 'destroy')->middleware('can:permission_delete')->name('permissions.destroy');
        Route::post('/bulk_assign_roles', 'bulk_assign_roles')->middleware('can:permission_edit')->name('permissions.bulk_assign_roles');
        Route::post('/bulk_remove_roles', 'bulk_remove_roles')->middleware('can:permission_edit')->name('permissions.bulk_remove_roles');
    });

    //plugin
    Route::prefix('plugins')->controller(PluginController::class)->group(function () {
        Route::get('/', 'index')->middleware('can:plugin_index')->name('plugins.index');
        Route::post('/upload', 'upload')->middleware('can:plugin_upload')->name('plugins.upload');
        Route::post('/activate/{pluginId}', 'activate')->middleware('can:plugin_activate')->name('plugins.activate');
        Route::post('/deactivate/{pluginId}', 'deactivate')->middleware('can:plugin_deactivate')->name('plugins.deactivate');
        Route::post('/delete/{pluginId}', 'delete')->middleware('can:plugin_delete')->name('plugins.delete');
    });

    //post
    Route::prefix('posts')->controller(PostController::class)->group(function () {
        Route::get('/restore/{id}', 'restore')->name('posts.restore');
        Route::get('/', 'index')->middleware('can:post_index')->name('posts.index');
        Route::get('/create', 'create')->middleware('can:post_create')->name('posts.create');
        Route::get('/create/{post}', 'create')->middleware('can:post_clone')->name('posts.clone');
        Route::get('/{post}/edit', 'edit')->middleware('can:post_edit')->name('posts.edit');
        Route::get('/{post}', 'show')->middleware('can:post_show')->name('posts.show');
        Route::post('/store', 'store')->middleware('can:post_create')->name('posts.store');
        Route::patch('/{post}', 'update')->middleware('can:post_edit')->name('posts.update');
        Route::delete('/{id}', 'destroy')->middleware('can:post_delete')->name('posts.destroy');
        Route::post('/bulk_sync_tags', 'bulk_sync_tags')->middleware('can:post_bulk_sync_tags')->name('posts.bulk_sync_tags');
        Route::post('/generate_demo_posts', 'generate_demo_posts')->middleware('can:post_generate_demo_posts')->name('posts.generate_demo_posts');
        Route::post('/bulk_set_websites', 'bulk_set_websites')->middleware('can:post_bulk_set_websites')->name('posts.bulk_set_websites');
        Route::post('/bulk_clone', 'bulk_clone')->middleware('can:post_bulk_clone')->name('posts.bulk_clone');
    });

    //search_keyword
    Route::prefix('search_keywords')->controller(SearchKeywordController::class)->group(function () {
        Route::get('/', 'index')->middleware('can:search_keyword_index')->name('search_keywords.index');
        Route::delete('/{search_keyword}', 'destroy')->middleware('can:search_keyword_delete')->name('search_keywords.destroy');
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
        Route::post('/activate/{themeId}', 'activate')->middleware('can:theme_activate')->name('themes.activate');
        Route::post('/deactivate/{themeId}', 'deactivate')->middleware('can:theme_deactivate')->name('themes.deactivate');
        Route::post('/upload', 'upload')->middleware('can:theme_upload')->name('themes.upload');
        Route::post('/delete/{themeId}', 'delete')->middleware('can:theme_delete')->name('themes.delete');
        Route::post('/preview/{themeId}', 'preview')->middleware('can:theme_preview')->name('themes.preview');
        Route::get('/settings', 'settings')->middleware('can:theme_settings')->name('themes.settings');
        Route::post('/update_setting', 'updateSetting')->middleware('can:theme_settings')->name('themes.update_setting');
    });

    //record
    Route::prefix('records')->controller(RecordController::class)->group(function () {
        Route::get('/', 'index')->middleware('can:record_index')->name('records.index');
        Route::delete('/{record}', 'destroy')->middleware('can:record_delete')->name('records.destroy');
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
        Route::get('/type/{type?}', 'index')->middleware('can:tag_index')->name('tags.index.type');
        Route::get('/bulk_create', 'bulk_create')->middleware('can:tag_bulk_create')->name('tags.bulk_create');
        Route::post('/bulk_store', 'bulk_store')->middleware('can:tag_bulk_create')->name('tags.bulk_store');
        Route::post('/import_csv', 'import_csv')->middleware('can:tag_import_csv')->name('tags.import_csv');
        Route::get('/keywords', 'show_keyword_index')->middleware('can:tag_keyword_index')->name('tags.keywords.index');
        Route::post('/{tag}/keywords/update', 'update_keyword')->middleware('can:tag_keyword_edit')->name('tags.keywords.update');
        Route::get('/create', 'create')->middleware('can:tag_create')->name('tags.create');
        Route::get('/{tag}/edit', 'edit')->middleware('can:tag_edit')->name('tags.edit');
        Route::get('/{tag}', 'show')->middleware('can:tag_show')->name('tags.show');
        Route::post('/store', 'store')->middleware('can:tag_create')->name('tags.store');
        Route::patch('/{tag}', 'update')->middleware('can:tag_edit')->name('tags.update');
        Route::delete('/{tag}', 'destroy')->middleware('can:tag_delete')->name('tags.destroy');
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
        Route::get('/{user}/edit', 'edit')->middleware('can:user_edit')->name('users.edit');
        Route::get('/{user}', 'show')->middleware('can:user_show')->name('users.show');
        Route::post('/store', 'store')->middleware('can:user_create')->name('users.store');
        Route::patch('/{user}', 'update')->middleware('can:user_edit')->name('users.update');
        Route::delete('/{user}', 'destroy')->middleware('can:user_delete')->name('users.destroy');
    });

    //website
    Route::prefix('websites')->controller(WebsiteController::class)->group(function () {
        Route::get('/', 'index')->middleware('can:website_index')->name('websites.index');
        Route::get('/create', 'create')->middleware('can:website_create')->name('websites.create');
        Route::get('/{website}/edit', 'edit')->middleware('can:website_edit')->name('websites.edit');
        Route::get('/{website}', 'show')->middleware('can:website_show')->name('websites.show');
        Route::post('/store', 'store')->middleware('can:website_create')->name('websites.store');
        Route::patch('/{website}', 'update')->middleware('can:website_edit')->name('websites.update');
        Route::delete('/{website}', 'destroy')->middleware('can:website_delete')->name('websites.destroy');
        Route::get('/{website}/options', 'editThemeOptions')->middleware('can:website_edit')->name('websites.theme.options');
        Route::put('/{website}/options/update', 'updateThemeOptions')->middleware('can:website_edit')->name('websites.theme.options.update');
        Route::post('/{website}/options/clone', 'cloneThemeOptions')->middleware('can:website_edit')->name('websites.theme.clone');
        Route::post('/{website}/options/import_default_option', 'importDefaultOption')->middleware('can:website_edit')->name('websites.theme.import_default_option');
    });
    //custom backend route
    if (file_exists(base_path('routes/custom_backend.php'))) {
        include base_path('routes/custom_backend.php');
    }
});
