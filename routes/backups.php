<?php

use App\Http\Controllers\Backup\BackupController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth',
    'session.current',
    'password.changed',
    'permission:backups_manage',
])
    ->prefix('backups')
    ->name('backups.')
    ->group(function (): void {
        Route::get('/', [BackupController::class, 'ctrl_index'])->name('index');
        Route::get('/data', [BackupController::class, 'ctrl_data'])->name('data');
        Route::post('/', [BackupController::class, 'ctrl_store'])->name('store');
        Route::get('/{backup}/download', [BackupController::class, 'ctrl_download'])
            ->whereUuid('backup')
            ->name('download');
        Route::delete('/{backup}', [BackupController::class, 'ctrl_destroy'])
            ->whereUuid('backup')
            ->name('destroy');
        Route::post('/{backup}/restore', [BackupController::class, 'ctrl_restore'])
            ->whereUuid('backup')
            ->name('restore');
    });
