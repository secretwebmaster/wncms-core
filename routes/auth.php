<?php

use Wncms\Http\Controllers\Auth\AuthenticatedSessionController;
use Wncms\Http\Controllers\Auth\ConfirmablePasswordController;
use Wncms\Http\Controllers\Auth\EmailVerificationNotificationController;
use Wncms\Http\Controllers\Auth\EmailVerificationPromptController;
use Wncms\Http\Controllers\Auth\NewPasswordController;
use Wncms\Http\Controllers\Auth\PasswordResetLinkController;
use Wncms\Http\Controllers\Auth\RegisteredUserController;
use Wncms\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;


Route::get('verify-email/{id}/{hash}', [VerifyEmailController::class, '__invoke'])->middleware(['auth', 'signed', 'throttle:6,1'])->name('verification.verify');
Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])->middleware(['auth', 'throttle:6,1'])->name('verification.send');


Route::prefix('panel')->middleware(['is_installed'])->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])->middleware('guest')->name('register');
    Route::post('register', [RegisteredUserController::class, 'store'])->middleware('guest');
    Route::post('register/ajax', [RegisteredUserController::class, 'ajax'])->middleware('guest')->name('register.ajax');
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->middleware('guest')->name('login');
    Route::get('login/google', [AuthenticatedSessionController::class, 'login_with_google'])->middleware('guest')->name('login.google');
    Route::get('login/google/callback', [AuthenticatedSessionController::class, 'login_with_google_callback'])->middleware('guest')->name('login.google.callback');
    Route::post('login/check', [AuthenticatedSessionController::class, 'check'])->middleware('guest')->name('login.check');
    Route::post('login/ajax', [AuthenticatedSessionController::class, 'ajax'])->middleware('guest')->name('login.ajax');
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->middleware('guest')->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('guest')->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->middleware('guest')->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->middleware('guest')->name('password.update');
    Route::get('verify-email', [EmailVerificationPromptController::class, '__invoke'])->middleware('auth')->name('verification.notice');
    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])->middleware('auth')->name('password.confirm');
    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store'])->middleware('auth');
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');
});

