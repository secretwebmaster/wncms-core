<?php

use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Wncms\Http\Controllers\Frontend\PageController;

// Main route group
Route::group([
    'prefix' => gss('enable_translation', true) ? LaravelLocalization::setLocale() : null,
    'middleware' => gss('enable_translation', true) ? ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath'] : [],
], function () {

    // Install
    require __DIR__ . '/install.php';

    // Auth
    require __DIR__ . '/auth.php';

    // Backend
    require __DIR__ . '/backend.php';

    // Frontend
    require __DIR__ . '/frontend.php';
});

// Fallback route
Route::fallback([PageController::class, 'fallback']);
