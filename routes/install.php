<?php

use Illuminate\Support\Facades\Route;
use Wncms\Http\Controllers\Backend\InstallController;

Route::get('installed', [InstallController::class, 'installed'])->name('installer.installed');
Route::post('install/progress', [InstallController::class, 'progress'])->name('installer.progress');

Route::prefix('install')->middleware(['is_installed'])->group(function(){
    Route::get('/', [InstallController::class, 'welcome'])->name('installer.welcome');
    Route::get('requirements', [InstallController::class, 'requirements'])->name('installer.requirements');
    Route::get('permissions', [InstallController::class, 'permissions'])->name('installer.permissions');
    Route::get('wizard', [InstallController::class, 'wizard'])->name('installer.wizard');
    Route::post('wizard/install', [InstallController::class, 'install'])->name('installer.wizard.install');
    Route::get('environment', [InstallController::class, 'environmentMenu'])->name('installer.environment');
});