<?php

use App\Http\Controllers\Audit\AuditController;
use Illuminate\Support\Facades\Route;

Route::get('/audit', [AuditController::class, 'ctrl_index'])
    ->middleware([
        'auth',
        'session.current',
        'password.changed',
        'permission:audit_view',
    ])
    ->name('audit.index');
