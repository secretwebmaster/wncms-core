<?php

use Illuminate\Support\Facades\Route;

Route::group([
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath']
    ], function(){

    require __DIR__ . '/auth.php';
    require __DIR__ . '/install.php';

    Route::middleware(['is_installed', 'has_website'])->group(function(){
        //! Backend 需登入
        require __DIR__ . '/backend.php';

        //! Frontend 不需登入
        require __DIR__ . '/frontend.php';
    });
});


//throw 404 if no route is matched
//pass $exception to the view
Route::fallback(function () {
    return view('errors.404');
});