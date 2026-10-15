<?php

use App\Http\Controllers\Administration\ProfileController;
use App\Http\Controllers\Administration\RolePermissionController;
use App\Http\Controllers\Administration\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth',
    'session.current',
    'password.changed',
    'permission:profile_update',
])
    ->prefix('profile')
    ->name('profile.')
    ->group(function (): void {
        Route::get('/', [ProfileController::class, 'ctrl_edit'])
            ->name('edit');
        Route::patch('/', [ProfileController::class, 'ctrl_update'])
            ->middleware(['throttle:profile-updates', 'throttle:sensitive-actions'])
            ->name('update');
    });

Route::middleware([
    'auth',
    'session.current',
    'password.changed',
    'permission:users_manage',
])
    ->prefix('users')
    ->name('users.')
    ->group(function (): void {
        Route::get('/', [UserController::class, 'ctrl_index'])
            ->name('index');
        Route::get('/create', [UserController::class, 'ctrl_create'])
            ->name('create');
        Route::post('/username-suggestion', [UserController::class, 'ctrl_generateUsername'])
            ->name('username-suggestion');
        Route::post('/', [UserController::class, 'ctrl_store'])
            ->name('store');
        Route::get('/{user}/edit', [UserController::class, 'ctrl_edit'])
            ->whereUuid('user')
            ->name('edit');
        Route::put('/{user}', [UserController::class, 'ctrl_update'])
            ->whereUuid('user')
            ->name('update');
        Route::patch('/{user}/status', [UserController::class, 'ctrl_updateStatus'])
            ->whereUuid('user')
            ->name('status.update');
        Route::post('/{user}/reset-password', [UserController::class, 'ctrl_resetPassword'])
            ->whereUuid('user')
            ->middleware('throttle:sensitive-actions')
            ->name('password.reset');
    });

Route::middleware([
    'auth',
    'session.current',
    'password.changed',
    'permission:roles_manage',
])
    ->prefix('roles')
    ->name('roles.')
    ->group(function (): void {
        Route::get('/', [RolePermissionController::class, 'ctrl_index'])
            ->name('index');
        Route::put('/{role}/permissions', [RolePermissionController::class, 'ctrl_update'])
            ->whereUuid('role')
            ->name('permissions.update');
    });
