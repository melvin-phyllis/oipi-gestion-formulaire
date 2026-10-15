<?php

use App\Http\Controllers\Authentication\AuthController;
use App\Http\Controllers\Authentication\PasswordController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'ctrl_showLogin'])
        ->name('login');
    Route::post('/login', [AuthController::class, 'ctrl_login'])
        ->middleware('throttle:login')
        ->name('login.store');

    Route::get('/forgot-password', [PasswordController::class, 'ctrl_showForgot'])
        ->name('password.request');
    Route::post('/forgot-password', [PasswordController::class, 'ctrl_sendResetLink'])
        ->middleware('throttle:password-reset')
        ->name('password.email');
    Route::get('/reset-password/{token}', [PasswordController::class, 'ctrl_showReset'])
        ->name('password.reset');
    Route::post('/reset-password', [PasswordController::class, 'ctrl_reset'])
        ->middleware('throttle:password-reset')
        ->name('password.update');
});

Route::middleware(['auth', 'session.current'])->group(function (): void {
    Route::get('/change-password', [PasswordController::class, 'ctrl_showChange'])
        ->name('password.change');
    Route::post('/change-password', [PasswordController::class, 'ctrl_change'])
        ->middleware('throttle:sensitive-actions')
        ->name('password.change.update');
});

Route::post('/logout', [AuthController::class, 'ctrl_logout'])
    ->middleware('auth')
    ->name('logout');
