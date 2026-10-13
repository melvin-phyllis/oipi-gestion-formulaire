<?php

use App\Http\Controllers\Analytics\AnalyticsController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth',
    'session.current',
    'password.changed',
    'permission:analytics_view',
])
    ->prefix('analytics')
    ->name('analytics.')
    ->group(function (): void {
        Route::get('/export/excel', [AnalyticsController::class, 'ctrl_exportExcel'])
            ->name('export.excel');
        Route::get('/export/pdf', [AnalyticsController::class, 'ctrl_exportPdf'])
            ->name('export.pdf');
        Route::get('/', [AnalyticsController::class, 'ctrl_index'])
            ->name('index');
    });
