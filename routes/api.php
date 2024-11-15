<?php

use Wncms\Http\Controllers\Api\V1\AnalyticsController;
use Wncms\Http\Controllers\Api\V1\MenuController;
use Wncms\Http\Controllers\Api\V1\PageController;
use Wncms\Http\Controllers\Api\V1\PostController;
use Wncms\Http\Controllers\Api\V1\TagController;
use Wncms\Http\Controllers\Api\V1\UpdateController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function(){

    Route::post('analytics/record', [AnalyticsController::class, 'record'])->name('analytics.record');
    Route::post('analytics/get', [AnalyticsController::class, 'get'])->name('analytics.get');
    
    Route::post('menus/index', [MenuController::class, 'index'])->name('menus.index');
    Route::post('menus/store', [MenuController::class, 'store'])->name('menus.store');
    Route::post('menus/sync', [MenuController::class, 'sync'])->name('menus.sync');
    Route::post('menus/{id}', [MenuController::class, 'show'])->name('menus.show');
    
    Route::post('pages/index', [PageController::class, 'index'])->name('pages.index');
    Route::post('pages/store', [PageController::class, 'store'])->name('pages.store');
    Route::post('pages/{id}', [PageController::class, 'show'])->name('pages.show');

    Route::post('posts/index', [PostController::class, 'index'])->name('posts.index');
    Route::post('posts/store', [PostController::class, 'store'])->name('posts.store');
    Route::post('posts/{id}', [PostController::class, 'show'])->name('posts.show');

    Route::post('tags/index', [TagController::class, 'index'])->name('tags.index');
    Route::post('tags/exist', [TagController::class, 'exist'])->name('tags.exist');

    Route::post('update', [UpdateController::class, 'update'])->name('update');
    Route::post('update/progress', [UpdateController::class, 'progress'])->name('update.progress');

});

//custom api route
if (file_exists(base_path('routes/custom_api.php'))) {
    include base_path('routes/custom_api.php');
}
