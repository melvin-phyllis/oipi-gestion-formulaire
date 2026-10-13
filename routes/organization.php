<?php

use App\Http\Controllers\Organization\OrganizationController;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth',
    'session.current',
    'password.changed',
    'permission:settings_manage',
])
    ->prefix('organization')
    ->name('organization.')
    ->group(function (): void {
        Route::get('/', [OrganizationController::class, 'ctrl_index'])
            ->name('index');

        Route::get('/directorates/create', [OrganizationController::class, 'ctrl_createDirectorate'])
            ->name('directorates.create');
        Route::post('/directorates', [OrganizationController::class, 'ctrl_storeDirectorate'])
            ->name('directorates.store');
        Route::get('/directorates/{directorate}/edit', [OrganizationController::class, 'ctrl_editDirectorate'])
            ->whereUuid('directorate')
            ->name('directorates.edit');
        Route::put('/directorates/{directorate}', [OrganizationController::class, 'ctrl_updateDirectorate'])
            ->whereUuid('directorate')
            ->name('directorates.update');
        Route::patch('/directorates/{directorate}/status', [OrganizationController::class, 'ctrl_updateDirectorateStatus'])
            ->whereUuid('directorate')
            ->name('directorates.status.update');
        Route::delete('/directorates/{directorate}', [OrganizationController::class, 'ctrl_destroyDirectorate'])
            ->whereUuid('directorate')
            ->name('directorates.destroy');
        Route::patch('/directorates/{directorate}/restore', [OrganizationController::class, 'ctrl_restoreDirectorate'])
            ->whereUuid('directorate')
            ->withTrashed()
            ->name('directorates.restore');

        Route::get('/directorates/{directorate}/services/create', [OrganizationController::class, 'ctrl_createService'])
            ->whereUuid('directorate')
            ->name('services.create');
        Route::post('/services', [OrganizationController::class, 'ctrl_storeService'])
            ->name('services.store');
        Route::get('/services/{service}/edit', [OrganizationController::class, 'ctrl_editService'])
            ->whereUuid('service')
            ->name('services.edit');
        Route::put('/services/{service}', [OrganizationController::class, 'ctrl_updateService'])
            ->whereUuid('service')
            ->name('services.update');
        Route::patch('/services/{service}/status', [OrganizationController::class, 'ctrl_updateServiceStatus'])
            ->whereUuid('service')
            ->name('services.status.update');
        Route::delete('/services/{service}', [OrganizationController::class, 'ctrl_destroyService'])
            ->whereUuid('service')
            ->name('services.destroy');
        Route::patch('/services/{service}/restore', [OrganizationController::class, 'ctrl_restoreService'])
            ->whereUuid('service')
            ->withTrashed()
            ->name('services.restore');
    });
