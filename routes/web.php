<?php

use App\Http\Controllers\Analytics\AnalyticsController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

Route::get('/dashboard', [AnalyticsController::class, 'ctrl_dashboard'])
    ->middleware([
        'auth',
        'session.current',
        'password.changed',
        'permission:dashboard_view',
    ])
    ->name('dashboard');
