<?php

use Illuminate\Support\Facades\Route;
use Wncms\Api\V2\ApiResponseFactory;
use Wncms\Http\Controllers\Api\V2\Backend\I18nController;

Route::prefix('v2')->middleware(['api', 'api_v2_whitelist'])->group(function () {
    Route::get('/translations', [I18nController::class, 'translations'])->name('api.v2.translations');

    Route::prefix('frontend')->name('api.v2.frontend.')->group(function () {
        Route::get('/health', function (ApiResponseFactory $responses) {
            return $responses->success(['scope' => 'frontend'], 'ok');
        })->name('health');
    });
});
