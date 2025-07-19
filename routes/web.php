<?php

use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Wncms\Http\Controllers\Frontend\PageController;

Route::group([
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath']
    ], function(){

    // Install
    require __DIR__ . '/install.php';

    // Auth
    require __DIR__ . '/auth.php';

    // Backend
    require __DIR__ . '/backend.php';

    // Frontend
    require __DIR__ . '/frontend.php';

});


//throw 404 if no route is matched
//pass $exception to the view
Route::fallback([PageController::class, 'fallback']);
// Route::fallback(function () {
//     return view('errors.404');
// });