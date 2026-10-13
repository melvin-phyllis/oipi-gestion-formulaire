<?php

use App\Http\Controllers\Administration\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth',
    'session.current',
    'password.changed',
    'permission:notifications_view',
])
    ->prefix('notifications')
    ->name('notifications.')
    ->group(function (): void {
        Route::get('/', [NotificationController::class, 'ctrl_index'])
            ->name('index');
        Route::get('/{notification}/data', [NotificationController::class, 'ctrl_data'])
            ->whereUuid('notification')
            ->name('data');
        Route::patch('/read-all', [NotificationController::class, 'ctrl_markAllRead'])
            ->name('read-all');
        Route::patch('/{notification}/read', [NotificationController::class, 'ctrl_markRead'])
            ->whereUuid('notification')
            ->name('read');
    });
