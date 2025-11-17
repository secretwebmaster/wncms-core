<?php

use Illuminate\Support\Facades\Route;
use Wncms\Http\Controllers\Api\V1\MenuController;
use Wncms\Http\Controllers\Api\V1\PageController;
use Wncms\Http\Controllers\Api\V1\PostController;
use Wncms\Http\Controllers\Api\V1\TagController;
use Wncms\Http\Controllers\Api\V1\UpdateController;


Route::prefix('v1')->name('api.v1.')->group(function () {

    // Menus
    Route::prefix('menus')->name('menus.')->controller(MenuController::class)->group(function () {
        Route::post('/', 'index')->name('index');
        Route::post('store', 'store')->name('store');
        Route::post('sync', 'sync')->name('sync');
        Route::post('{id}', 'show')->name('show');
    });

    // Pages
    Route::prefix('pages')->name('pages.')->controller(PageController::class)->group(function () {
        Route::post('/', 'index')->name('index');
        Route::post('store', 'store')->name('store');
        Route::post('{id}', 'show')->name('show');
    });

    // Posts
    Route::prefix('posts')->name('posts.')->controller(PostController::class)->group(function () {
        Route::match(['GET', 'POST'], '/', 'index')->name('index');
        Route::post('store', 'store')->name('store');
        Route::post('update/{slug}', 'update')->name('update');
        Route::post('delete/{slug}', 'delete')->name('delete');
        Route::match(['GET', 'POST'], '{slug}', 'show')->name('show');
    });

    // Tags
    Route::prefix('tags')->name('tags.')->controller(TagController::class)->group(function () {
        Route::post('/', 'index')->name('index');
        Route::post('exist', 'exist')->name('exist');
        Route::post('store', 'store')->name('store');
    });

    // Update
    Route::prefix('update')->name('update.')->controller(UpdateController::class)->group(function () {
        Route::post('/', 'update')->name('run');
        Route::post('progress', 'progress')->name('progress');
    });
});

// Custom user-defined API routes
if (file_exists(base_path('routes/custom_api.php'))) {
    include base_path('routes/custom_api.php');
}
