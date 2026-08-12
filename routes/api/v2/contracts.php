<?php

use Illuminate\Support\Facades\Route;
use Wncms\Http\Controllers\Api\V2\ContractController;

Route::prefix('v2')
    ->name('api.v2.')
    ->middleware(['api', 'api_v2_whitelist'])
    ->group(function () {
        Route::get('/openapi.json', [ContractController::class, 'openApi'])->name('openapi');
        Route::get('/capabilities', [ContractController::class, 'capabilities'])
            ->middleware('api_v2_token_auth')
            ->name('capabilities');
    });
