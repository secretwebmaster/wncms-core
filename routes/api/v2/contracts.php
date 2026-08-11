<?php

use Illuminate\Support\Facades\Route;
use Wncms\Http\Controllers\Api\V2\ContractController;

Route::prefix('v2')
    ->name('api.v2.')
    ->middleware(['api', 'api_v2_request_id', 'api_v2_whitelist', 'api_v2_token_auth'])
    ->group(function () {
        Route::get('/capabilities', [ContractController::class, 'capabilities'])->name('capabilities');
    });
