<?php

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
use Wncms\Http\Controllers\ThemeController;
use Illuminate\Support\Facades\Route;

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
    Route::get('advertisements', [AdvertisementController::class, 'index'])->middleware('can:advertisement_index')->name('advertisements.index');
    Route::get('advertisements/create', [AdvertisementController::class, 'create'])->middleware('can:advertisement_create')->name('advertisements.create');
    Route::get('advertisements/clone/{advertisement}', [AdvertisementController::class, 'clone'])->middleware('can:advertisement_clone')->name('advertisements.clone');
    Route::get('advertisements/create/{advertisement}', [AdvertisementController::class, 'create'])->middleware('can:advertisement_clone')->name('advertisements.clone');
    Route::get('advertisements/{advertisement}/edit', [AdvertisementController::class, 'edit'])->middleware('can:advertisement_edit')->name('advertisements.edit');
    Route::post('advertisements/store', [AdvertisementController::class, 'store'])->middleware('can:advertisement_create')->name('advertisements.store');
    Route::patch('advertisements/{advertisement}', [AdvertisementController::class, 'update'])->middleware('can:advertisement_edit')->name('advertisements.update');
    Route::delete('advertisements/{advertisement}', [AdvertisementController::class, 'destroy'])->middleware('can:advertisement_delete')->name('advertisements.destroy');
    Route::post('advertisements/bulk_delete', [AdvertisementController::class, 'bulk_delete'])->middleware('can:advertisement_bulk_delete')->name('advertisements.bulk_delete');


    //Analytics
    Route::get('analytics', [AnalyticsController::class, 'index'])->middleware('can:analytics_index')->name('analytics.index');
    Route::get('analytics/traffic', [AnalyticsController::class, 'show_traffic'])->middleware('can:analytics_index')->name('analytics.traffic');
    Route::get('analytics/click', [AnalyticsController::class, 'show_click'])->middleware('can:analytics_index')->name('analytics.click');


    //banner
    Route::get('banners', [BannerController::class, 'index'])->middleware('can:banner_index')->name('banners.index');
    Route::get('banners/create', [BannerController::class, 'create'])->middleware('can:banner_create')->name('banners.create');
    Route::get('banners/clone/{banner}', [BannerController::class, 'create'])->middleware('can:banner_clone')->name('banners.clone');
    Route::post('banners/clone', [BannerController::class, 'bulk_clone'])->middleware('can:banner_clone')->name('banners.clone.bulk');
    Route::get('banners/{banner}/edit', [BannerController::class, 'edit'])->middleware('can:banner_edit')->name('banners.edit');
    Route::get('banners/{banner}', [BannerController::class, 'show'])->middleware('can:banner_show')->name('banners.show');
    Route::post('banners/store', [BannerController::class, 'store'])->middleware('can:banner_create')->name('banners.store');
    Route::patch('banners/{banner}', [BannerController::class, 'update'])->middleware('can:banner_edit')->name('banners.update');
    Route::delete('banners/{banner}', [BannerController::class, 'destroy'])->middleware('can:banner_delete')->name('banners.destroy');
    Route::post('banners/bulk_delete', [BannerController::class, 'bulk_delete'])->middleware('can:banner_bulk_delete')->name('banners.bulk_delete');


    //cache
    Route::post('cache/flush', [CacheController::class, 'flush'])->middleware('can:cache_flush')->name('cache.flush');
    Route::post('cache/flush/{tag}', [CacheController::class, 'flush'])->middleware('can:cache_flush')->name('cache.flush.tag');
    Route::post('cache/clear/{key}', [CacheController::class, 'clear'])->middleware('can:cache_clear')->name('cache.clear');
    Route::post('cache/clear/{tag}/{key}', [CacheController::class, 'clear'])->middleware('can:cache_clear')->name('cache.clear.tag');
    

    //contact_form
    Route::get('contact_forms', [ContactFormController::class, 'index'])->middleware('can:contact_form_index')->name('contact_forms.index');
    Route::get('contact_forms/create', [ContactFormController::class, 'create'])->middleware('can:contact_form_create')->name('contact_forms.create');
    Route::get('contact_forms/{contact_form}/edit', [ContactFormController::class, 'edit'])->middleware('can:contact_form_edit')->name('contact_forms.edit');
    Route::get('contact_forms/{contact_form}', [ContactFormController::class, 'show'])->middleware('can:contact_form_show')->name('contact_forms.show');
    Route::post('contact_forms/store', [ContactFormController::class, 'store'])->middleware('can:contact_form_create')->name('contact_forms.store');
    Route::patch('contact_forms/{contact_form}', [ContactFormController::class, 'update'])->middleware('can:contact_form_edit')->name('contact_forms.update');
    Route::delete('contact_forms/{contact_form}', [ContactFormController::class, 'destroy'])->middleware('can:contact_form_delete')->name('contact_forms.destroy');


    //contact_form_option
    Route::get('contact_form_options', [ContactFormOptionController::class, 'index'])->middleware('can:contact_form_option_index')->name('contact_form_options.index');
    Route::get('contact_form_options/create', [ContactFormOptionController::class, 'create'])->middleware('can:contact_form_option_create')->name('contact_form_options.create');
    Route::get('contact_form_options/{contact_form_option}/edit', [ContactFormOptionController::class, 'edit'])->middleware('can:contact_form_option_edit')->name('contact_form_options.edit');
    Route::get('contact_form_options/{contact_form_option}', [ContactFormOptionController::class, 'show'])->middleware('can:contact_form_option_show')->name('contact_form_options.show');
    Route::post('contact_form_options/store', [ContactFormOptionController::class, 'store'])->middleware('can:contact_form_option_create')->name('contact_form_options.store');
    Route::patch('contact_form_options/{contact_form_option}', [ContactFormOptionController::class, 'update'])->middleware('can:contact_form_option_edit')->name('contact_form_options.update');
    Route::delete('contact_form_options/{contact_form_option}', [ContactFormOptionController::class, 'destroy'])->middleware('can:contact_form_option_delete')->name('contact_form_options.destroy');


    //contact_form_submission
    Route::get('contact_form_submissions', [ContactFormSubmissionController::class, 'index'])->middleware('can:contact_form_submission_index')->name('contact_form_submissions.index');
    Route::get('contact_form_submissions/{contact_form_submission}', [ContactFormSubmissionController::class, 'show'])->middleware('can:contact_form_submission_show')->name('contact_form_submissions.show');
    Route::delete('contact_form_submissions/{contact_form_submission}', [ContactFormSubmissionController::class, 'destroy'])->middleware('can:contact_form_submission_delete')->name('contact_form_submissions.destroy');
    Route::post('contact_form_submissions/bulk_delete', [ContactFormSubmissionController::class, 'bulk_delete'])->middleware('can:contact_form_submission_bulk_delete')->name('contact_form_submissions.bulk_delete');
    Route::get('contact_form_submissions/export/{type}', [ContactFormSubmissionController::class, 'export'])->middleware('can:contact_form_submission_export')->name('contact_form_submissions.export');


    //dashboard
    Route::get('dashboard', [DashboardController::class, 'show_dashboard'])->name('dashboard');
    Route::post('switch_website', [DashboardController::class, 'switch_website'])->name('dashboard.switch_website');

    
    //faq
    Route::get('faqs', [FaqController::class, 'index'])->middleware('can:faq_index')->name('faqs.index');
    Route::get('faqs/create', [FaqController::class, 'create'])->middleware('can:faq_create')->name('faqs.create');
    Route::get('faqs/create/{faq}', [FaqController::class, 'create'])->middleware('can:faq_clone')->name('faqs.clone');
    Route::get('faqs/{faq}/edit', [FaqController::class, 'edit'])->middleware('can:faq_edit')->name('faqs.edit');
    Route::post('faqs/store', [FaqController::class, 'store'])->middleware('can:faq_create')->name('faqs.store');
    Route::patch('faqs/{faq}', [FaqController::class, 'update'])->middleware('can:faq_edit')->name('faqs.update');
    Route::delete('faqs/{faq}', [FaqController::class, 'destroy'])->middleware('can:faq_delete')->name('faqs.destroy');
    Route::post('faqs/bulk_delete', [FaqController::class, 'bulk_delete'])->middleware('can:faq_bulk_delete')->name('faqs.bulk_delete');


    //menu
    Route::post('menus/edit_menu_item', [MenuController::class, 'edit_menu_item'])->middleware('can:menu_edit')->name('menus.edit_menu_item');
    Route::post('menus/get_menu_item', [MenuController::class, 'get_menu_item'])->middleware('can:menu_edit')->name('menus.get_menu_item');
    Route::get('menus', [MenuController::class, 'index'])->middleware('can:menu_index')->name('menus.index');
    Route::get('menus/create', [MenuController::class, 'create'])->middleware('can:menu_create')->name('menus.create');
    Route::get('menus/{menu}/edit', [MenuController::class, 'edit'])->middleware('can:menu_edit')->name('menus.edit');
    Route::get('menus/{menu}', [MenuController::class, 'show'])->middleware('can:menu_show')->name('menus.show');
    Route::post('menus/store', [MenuController::class, 'store'])->middleware('can:menu_create')->name('menus.store');
    Route::patch('menus/{menu}', [MenuController::class, 'update'])->middleware('can:menu_edit')->name('menus.update');
    Route::delete('menus/{menu}', [MenuController::class, 'destroy'])->middleware('can:menu_delete')->name('menus.destroy');
    Route::post('menus/clone', [MenuController::class, 'clone'])->middleware('can:menu_create')->name('menus.clone');


    //model
    Route::post('models/update', [ModelController::class, 'update'])->name('models.update');
    Route::post('models/bulk_delete', [ModelController::class, 'bulk_delete'])->name('models.bulk_delete');
    Route::post('models/bulk_force_delete', [ModelController::class, 'bulk_force_delete'])->name('models.bulk_force_delete');


    //page
    Route::get('pages', [PageController::class, 'index'])->middleware('can:page_index')->name('pages.index');
    Route::post('pages/create_theme_pages', [PageController::class, 'create_theme_pages'])->middleware('can:page_create')->name('pages.create_theme_pages');
    Route::get('pages/create', [PageController::class, 'create'])->middleware('can:page_create')->name('pages.create');
    Route::get('pages/clone/{page}', [PageController::class, 'create'])->middleware('can:page_clone')->name('pages.clone');
    Route::get('pages/{page}/edit', [PageController::class, 'edit'])->middleware('can:page_edit')->name('pages.edit');
    Route::get('pages/{page}', [PageController::class, 'show'])->middleware('can:page_show')->name('pages.show');
    Route::post('pages/store', [PageController::class, 'store'])->middleware('can:page_create')->name('pages.store');
    Route::patch('pages/{page}', [PageController::class, 'update'])->middleware('can:page_edit')->name('pages.update');
    Route::delete('pages/{page}', [PageController::class, 'destroy'])->middleware('can:page_delete')->name('pages.destroy');
    Route::post('pages/templates', [PageController::class, 'templates'])->middleware('can:page_create')->name('pages.templates');
    Route::get('pages/{page}/editor', [PageController::class, 'editor'])->middleware('can:page_create')->name('pages.editor');
    Route::post('pages/widget', [PageController::class, 'widget'])->middleware('can:page_edit')->name('pages.widget');
    Route::post('pages/bulk_delete', [PageController::class, 'bulk_delete'])->middleware('can:page_bulk_delete')->name('pages.bulk_delete');
    

    //permission
    Route::get('permissions', [PermissionController::class, 'index'])->middleware('can:permission_index')->name('permissions.index');
    Route::get('permissions/create', [PermissionController::class, 'create'])->middleware('can:permission_create')->name('permissions.create');
    Route::get('permissions/{permission}/edit', [PermissionController::class, 'edit'])->middleware('can:permission_edit')->name('permissions.edit');
    Route::get('permissions/{permission}', [PermissionController::class, 'show'])->middleware('can:permission_show')->name('permissions.show');
    Route::post('permissions/store', [PermissionController::class, 'store'])->middleware('can:permission_create')->name('permissions.store');
    Route::patch('permissions/{permission}', [PermissionController::class, 'update'])->middleware('can:permission_edit')->name('permissions.update');
    Route::delete('permissions/{permission}', [PermissionController::class, 'destroy'])->middleware('can:permission_delete')->name('permissions.destroy');
    Route::post('permissions/bulk_assign_roles', [PermissionController::class, 'bulk_assign_roles'])->middleware('can:permission_edit')->name('permissions.bulk_assign_roles');
    Route::post('permissions/bulk_remove_roles', [PermissionController::class, 'bulk_remove_roles'])->middleware('can:permission_edit')->name('permissions.bulk_remove_roles');


    //plugin
    Route::get('plugins', [PluginController::class, 'index'])->middleware('can:plugin_index')->name('plugins.index');
    Route::post('plugins/upload', [PluginController::class, 'upload'])->middleware('can:plugin_upload')->name('plugins.upload');
    Route::post('plugins/activate/{pluginId}', [PluginController::class, 'activate'])->middleware('can:plugin_activate')->name('plugins.activate');
    Route::post('plugins/deactivate/{pluginId}', [PluginController::class, 'deactivate'])->middleware('can:plugin_deactivate')->name('plugins.deactivate');
    Route::post('plugins/delete/{pluginId}', [PluginController::class, 'delete'])->middleware('can:plugin_delete')->name('plugins.delete');


    //post
    Route::get('posts/restore/{id}', [PostController::class, 'restore'])->name('posts.restore');
    Route::get('posts', [PostController::class, 'index'])->middleware('can:post_index')->name('posts.index');
    Route::get('posts/create', [PostController::class, 'create'])->middleware('can:post_create')->name('posts.create');
    Route::get('posts/create/{post}', [PostController::class, 'create'])->middleware('can:post_clone')->name('posts.clone');
    Route::get('posts/{post}/edit', [PostController::class, 'edit'])->middleware('can:post_edit')->name('posts.edit');
    Route::get('posts/{post}', [PostController::class, 'show'])->middleware('can:post_show')->name('posts.show');
    Route::post('posts/store', [PostController::class, 'store'])->middleware('can:post_create')->name('posts.store');
    Route::patch('posts/{post}', [PostController::class, 'update'])->middleware('can:post_edit')->name('posts.update');
    Route::delete('posts/{id}', [PostController::class, 'destroy'])->middleware('can:post_delete')->name('posts.destroy');
    Route::post('posts/bulk_sync_tags', [PostController::class, 'bulk_sync_tags'])->middleware('can:post_bulk_sync_tags')->name('posts.bulk_sync_tags');
    Route::post('posts/generate_demo_posts', [PostController::class, 'generate_demo_posts'])->middleware('can:post_generate_demo_posts')->name('posts.generate_demo_posts');
    Route::post('posts/bulk_set_websites', [PostController::class, 'bulk_set_websites'])->middleware('can:post_bulk_set_websites')->name('posts.bulk_set_websites');
    Route::post('posts/bulk_clone', [PostController::class, 'bulk_clone'])->middleware('can:post_bulk_clone')->name('posts.bulk_clone');


    //search_keyword
    // TODO: allow user to create search keywords in backend (Controller create store)
    // TODO: allow user to modify search keywords count (Controller edit update)
    Route::get('search_keywords', [SearchKeywordController::class, 'index'])->middleware('can:search_keyword_index')->name('search_keywords.index');
    // Route::get('search_keywords/create', [SearchKeywordController::class, 'create'])->middleware('can:search_keyword_create')->name('search_keywords.create');
    // Route::get('search_keywords/{search_keyword}/edit', [SearchKeywordController::class, 'edit'])->middleware('can:search_keyword_edit')->name('search_keywords.edit');
    // Route::get('search_keywords/{search_keyword}', [SearchKeywordController::class, 'show'])->middleware('can:search_keyword_show')->name('search_keywords.show');
    // Route::post('search_keywords/store', [SearchKeywordController::class, 'store'])->middleware('can:search_keyword_create')->name('search_keywords.store');
    // Route::patch('search_keywords/{search_keyword}', [SearchKeywordController::class, 'update'])->middleware('can:search_keyword_edit')->name('search_keywords.update');
    Route::delete('search_keywords/{search_keyword}', [SearchKeywordController::class, 'destroy'])->middleware('can:search_keyword_delete')->name('search_keywords.destroy');


    //setting
    Route::get('settings', [SettingController::class, 'index'])->middleware('can:setting_index')->name('settings.index');
    Route::put('settings', [SettingController::class, 'update'])->middleware('can:setting_edit')->name('settings.update');
    Route::post('settings/smtp/test', [SettingController::class, 'smtp_test'])->middleware('can:setting_edit')->name('settings.smtp_test');
    Route::post('settings/quick/add', [SettingController::class, 'add_quick_link'])->middleware('can:setting_edit')->name('settings.quick.add');
    Route::post('settings/quick/remove', [SettingController::class, 'remove_quick_link'])->middleware('can:setting_edit')->name('settings.quick.remove');


    //theme
    Route::get('themes', [ThemeController::class, 'index'])->middleware('can:theme_index')->name('themes.index');
    Route::post('themes/activate/{themeId}', [ThemeController::class, 'activate'])->middleware('can:theme_activate')->name('themes.activate');
    Route::post('themes/deactivate/{themeId}', [ThemeController::class, 'deactivate'])->middleware('can:theme_deactivate')->name('themes.deactivate');
    Route::post('themes/upload', [ThemeController::class, 'upload'])->middleware('can:theme_upload')->name('themes.upload');
    Route::post('themes/delete/{themeId}', [ThemeController::class, 'delete'])->middleware('can:theme_delete')->name('themes.delete');
    Route::post('themes/preview/{themeId}', [ThemeController::class, 'preview'])->middleware('can:theme_preview')->name('themes.preview');
    Route::get('themes/settings', [ThemeController::class, 'settings'])->middleware('can:theme_settings')->name('themes.settings');
    Route::post('themes/update_setting', [ThemeController::class, 'updateSetting'])->middleware('can:theme_settings')->name('themes.update_setting');
    


    //record
    Route::get('records', [RecordController::class, 'index'])->middleware('can:record_index')->name('records.index');
    // Route::get('records/create', [RecordController::class, 'create'])->middleware('can:record_create')->name('records.create');
    // Route::get('records/{record}/edit', [RecordController::class, 'edit'])->middleware('can:record_edit')->name('records.edit');
    // Route::get('records/{record}', [RecordController::class, 'show'])->middleware('can:record_show')->name('records.show');
    // Route::post('records/store', [RecordController::class, 'store'])->middleware('can:record_create')->name('records.store');
    // Route::patch('records/{record}', [RecordController::class, 'update'])->middleware('can:record_edit')->name('records.update');
    Route::delete('records/{record}', [RecordController::class, 'destroy'])->middleware('can:record_delete')->name('records.destroy');
    Route::post('records/bulk_delete', [RecordController::class, 'bulk_delete'])->middleware('can:record_bulk_delete')->name('records.bulk_delete');
    

    //role
    Route::get('roles', [RoleController::class, 'index'])->middleware('can:role_index')->name('roles.index');
    Route::get('roles/create', [RoleController::class, 'create'])->middleware('can:role_create')->name('roles.create');
    Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->middleware('can:role_edit')->name('roles.edit');
    Route::get('roles/{role}', [RoleController::class, 'show'])->middleware('can:role_show')->name('roles.show');
    Route::post('roles/store', [RoleController::class, 'store'])->middleware('can:role_create')->name('roles.store');
    Route::patch('roles/{role}', [RoleController::class, 'update'])->middleware('can:role_edit')->name('roles.update');
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])->middleware('can:role_delete')->name('roles.destroy');


    //tag
    Route::get('tags', [TagController::class, 'index'])->middleware('can:tag_index')->name('tags.index');
    Route::get('tags/type/create', [TagController::class, 'create_type'])->middleware('can:tag_create_type')->name('tags.create_type');
    Route::post('tags/type/store', [TagController::class, 'store_type'])->middleware('can:tag_create_type')->name('tags.store_type');
    Route::get('tags/type/{type?}', [TagController::class, 'index'])->middleware('can:tag_index')->name('tags.index.type');
    Route::get('tags/bulk_create', [TagController::class, 'bulk_create'])->middleware('can:tag_bulk_create')->name('tags.bulk_create');
    Route::post('tags/bulk_store', [TagController::class, 'bulk_store'])->middleware('can:tag_bulk_create')->name('tags.bulk_store');
    Route::post('tags/import_csv', [TagController::class, 'import_csv'])->middleware('can:tag_import_csv')->name('tags.import_csv');
    Route::get('tags/keywords', [TagController::class, 'show_keyword_index'])->middleware('can:tag_keyword_index')->name('tags.keywords.index');
    Route::post('tags/{tag}/keywords/update', [TagController::class, 'update_keyword'])->middleware('can:tag_keyword_edit')->name('tags.keywords.update');
    Route::get('tags/create', [TagController::class, 'create'])->middleware('can:tag_create')->name('tags.create');
    Route::get('tags/{tag}/edit', [TagController::class, 'edit'])->middleware('can:tag_edit')->name('tags.edit');
    Route::get('tags/{tag}', [TagController::class, 'show'])->middleware('can:tag_show')->name('tags.show');
    Route::post('tags/store', [TagController::class, 'store'])->middleware('can:tag_create')->name('tags.store');
    Route::patch('tags/{tag}', [TagController::class, 'update'])->middleware('can:tag_edit')->name('tags.update');
    Route::delete('tags/{tag}', [TagController::class, 'destroy'])->middleware('can:tag_delete')->name('tags.destroy');
    Route::post('tags/bulk_delete', [TagController::class, 'bulk_delete'])->middleware('can:tag_bulk_delete')->name('tags.bulk_delete');
    Route::post('tags/bulk_set_parent', [TagController::class, 'bulk_set_parent'])->middleware('can:tag_edit')->name('tags.bulk_set_parent');
    Route::post('tags/get_languages', [TagController::class, 'get_languages'])->middleware('can:tag_edit')->name('tags.get_languages');


    //upload
    Route::post('uploads/image', [UploadController::class, 'upload_image'])->middleware('can:upload_image')->name('uploads.image');
    // Route::post('uploads/video', [UploadController::class, 'upload_video'])->middleware('can:upload_video')->name('uploads.video');


    //update
    Route::get('updates', [UpdateController::class, 'index'])->name('updates');
    Route::post('updates/check', [UpdateController::class, 'check'])->name('updates.check');


    //user
    Route::prefix('users')->group(function () {
        //account
        Route::prefix('account')->group(function () {
            Route::get('profile', [UserController::class, 'show_user_profile'])->middleware('can:user_profile_show')->name('users.account.profile.show');
            Route::post('profile/update', [UserController::class, 'update_user_profile'])->middleware('can:user_profile_update')->name('users.account.profile.update');
            Route::get('security', [UserController::class, 'show_user_security'])->middleware('can:user_security_show')->name('users.account.security.show');
            Route::get('api', [UserController::class, 'show_user_api'])->middleware('can:user_api_show')->name('users.account.api.show');
            Route::post('api/update', [UserController::class, 'update_user_api'])->middleware('can:user_api_update')->name('users.account.api.update');
            Route::get('record', [UserController::class, 'show_user_record'])->middleware('can:user_record_show')->name('users.account.record.show');
            Route::post('email/update', [UserController::class, 'update_user_email'])->middleware('can:user_profile_update')->name('users.account.email.update');
            Route::post('password/update', [UserController::class, 'update_user_password'])->middleware('can:user_profile_update')->name('users.account.password.update');
        });

        //resources
        Route::get('/', [UserController::class, 'index'])->middleware('can:user_index')->name('users.index');
        Route::get('create', [UserController::class, 'create'])->middleware('can:user_create')->name('users.create');
        Route::get('{user}/edit', [UserController::class, 'edit'])->middleware('can:user_edit')->name('users.edit');
        Route::get('{user}', [UserController::class, 'show'])->middleware('can:user_show')->name('users.show');
        Route::post('store', [UserController::class, 'store'])->middleware('can:user_create')->name('users.store');
        Route::patch('{user}', [UserController::class, 'update'])->middleware('can:user_edit')->name('users.update');
        Route::delete('{user}', [UserController::class, 'destroy'])->middleware('can:user_delete')->name('users.destroy');
    });


    //website
    Route::get('websites', [WebsiteController::class, 'index'])->middleware('can:website_index')->name('websites.index');
    Route::get('websites/create', [WebsiteController::class, 'create'])->middleware('can:website_create')->name('websites.create');
    Route::get('websites/{website}/edit', [WebsiteController::class, 'edit'])->middleware('can:website_edit')->name('websites.edit');
    Route::get('websites/{website}', [WebsiteController::class, 'show'])->middleware('can:website_show')->name('websites.show');
    Route::post('websites/store', [WebsiteController::class, 'store'])->middleware('can:website_create')->name('websites.store');
    Route::patch('websites/{website}', [WebsiteController::class, 'update'])->middleware('can:website_edit')->name('websites.update');
    Route::delete('websites/{website}', [WebsiteController::class, 'destroy'])->middleware('can:website_delete')->name('websites.destroy');
    Route::get('websites/{website}/options', [WebsiteController::class, 'editThemeOptions'])->middleware('can:website_edit')->name('websites.theme.options');
    Route::put('websites/{website}/options/update', [WebsiteController::class, 'updateThemeOptions'])->middleware('can:website_edit')->name('websites.theme.options.update');
    Route::post('websites/{website}/options/clone', [WebsiteController::class, 'cloneThemeOptions'])->middleware('can:website_edit')->name('websites.theme.clone');
    Route::post('websites/{website}/options/import_default_option', [WebsiteController::class, 'importDefaultOption'])->middleware('can:website_edit')->name('websites.theme.import_default_option');

    //custom backend route
    if (file_exists(base_path('routes/custom_backend.php'))) {
        include base_path('routes/custom_backend.php');
    }
});