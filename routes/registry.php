<?php

use App\Http\Controllers\Registry\FormAttachmentController;
use App\Http\Controllers\Registry\MissingFormController;
use App\Http\Controllers\Registry\RegistrySettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'session.current', 'password.changed', 'permission:settings_manage'])
    ->prefix('registry/settings')
    ->name('registry.settings.')
    ->group(function (): void {
        Route::get('/', [RegistrySettingsController::class, 'ctrl_index'])->name('index');
        Route::post('/title-types', [RegistrySettingsController::class, 'ctrl_storeTitleType'])->name('title-types.store');
        Route::put('/title-types/{titleType}', [RegistrySettingsController::class, 'ctrl_updateTitleType'])->whereUuid('titleType')->name('title-types.update');
        Route::patch('/title-types/{titleType}/status', [RegistrySettingsController::class, 'ctrl_updateTitleTypeStatus'])->whereUuid('titleType')->name('title-types.status.update');
        Route::delete('/title-types/{titleType}', [RegistrySettingsController::class, 'ctrl_destroyTitleType'])->whereUuid('titleType')->name('title-types.destroy');
        Route::patch('/title-types/{titleType}/restore', [RegistrySettingsController::class, 'ctrl_restoreTitleType'])->whereUuid('titleType')->withTrashed()->name('title-types.restore');
        Route::post('/search-locations', [RegistrySettingsController::class, 'ctrl_storeSearchLocation'])->name('search-locations.store');
        Route::put('/search-locations/{searchLocation}', [RegistrySettingsController::class, 'ctrl_updateSearchLocation'])->whereUuid('searchLocation')->name('search-locations.update');
        Route::patch('/search-locations/{searchLocation}/status', [RegistrySettingsController::class, 'ctrl_updateSearchLocationStatus'])->whereUuid('searchLocation')->name('search-locations.status.update');
        Route::delete('/search-locations/{searchLocation}', [RegistrySettingsController::class, 'ctrl_destroySearchLocation'])->whereUuid('searchLocation')->name('search-locations.destroy');
        Route::patch('/search-locations/{searchLocation}/restore', [RegistrySettingsController::class, 'ctrl_restoreSearchLocation'])->whereUuid('searchLocation')->withTrashed()->name('search-locations.restore');
    });

Route::middleware(['auth', 'session.current', 'password.changed'])
    ->prefix('registry/forms')
    ->name('registry.forms.')
    ->group(function (): void {
        Route::get('/', [MissingFormController::class, 'ctrl_index'])
            ->middleware('permission:registry_view')
            ->name('index');
        Route::get('/create', [MissingFormController::class, 'ctrl_create'])
            ->middleware('permission:registry_create')
            ->name('create');
        Route::post('/register-found', [MissingFormController::class, 'ctrl_storeFound'])
            ->middleware('permission:registry_register_found')
            ->name('store-found');
        Route::post('/import', [MissingFormController::class, 'ctrl_import'])
            ->middleware([
                'permission:registry_import',
                'throttle:registry-imports',
            ])
            ->name('import');
        Route::get('/imports/{missingFormImport}', [MissingFormController::class, 'ctrl_importStatus'])
            ->whereUuid('missingFormImport')
            ->middleware('permission:registry_import')
            ->name('imports.status');
        Route::get('/{missingForm}', [MissingFormController::class, 'ctrl_show'])
            ->whereUuid('missingForm')
            ->middleware('permission:registry_view')
            ->name('show');
        Route::get('/{missingForm}/edit', [MissingFormController::class, 'ctrl_edit'])
            ->whereUuid('missingForm')
            ->middleware('permission:registry_update_metadata')
            ->name('edit');
        Route::post('/', [MissingFormController::class, 'ctrl_store'])
            ->middleware('permission:registry_create')
            ->name('store');
        Route::put('/{missingForm}', [MissingFormController::class, 'ctrl_update'])
            ->whereUuid('missingForm')
            ->middleware('permission:registry_update_metadata')
            ->name('update');
        Route::patch('/{missingForm}/archive', [MissingFormController::class, 'ctrl_archive'])
            ->whereUuid('missingForm')
            ->middleware('permission:registry_archive')
            ->name('archive');
        Route::patch('/{missingForm}/restore', [MissingFormController::class, 'ctrl_restore'])
            ->whereUuid('missingForm')
            ->middleware('permission:registry_archive')
            ->name('restore');
        Route::patch('/{missingForm}/finalization/digitized', [MissingFormController::class, 'ctrl_confirmDigitized'])
            ->whereUuid('missingForm')
            ->middleware('permission:registry_finalize')
            ->name('finalization.digitized');
        Route::patch('/{missingForm}/finalization/entered', [MissingFormController::class, 'ctrl_confirmEntered'])
            ->whereUuid('missingForm')
            ->middleware('permission:registry_finalize')
            ->name('finalization.entered');
        Route::patch('/{missingForm}/reopen', [MissingFormController::class, 'ctrl_reopen'])
            ->whereUuid('missingForm')
            ->middleware('permission:registry_reopen')
            ->name('reopen');
        Route::post('/{missingForm}/attachments', [FormAttachmentController::class, 'ctrl_store'])
            ->whereUuid('missingForm')
            ->middleware([
                'permission:attachments_add_any,attachments_add_own',
                'throttle:attachments',
            ])
            ->name('attachments.store');
        Route::get('/{missingForm}/attachments/{attachment}/download', [FormAttachmentController::class, 'ctrl_download'])
            ->whereUuid('missingForm')
            ->whereUuid('attachment')
            ->middleware([
                'permission:registry_view',
                'throttle:attachments',
            ])
            ->name('attachments.download');
        Route::delete('/{missingForm}/attachments/{attachment}', [FormAttachmentController::class, 'ctrl_destroy'])
            ->whereUuid('missingForm')
            ->whereUuid('attachment')
            ->middleware([
                'permission:attachments_delete_any,attachments_delete_own',
                'throttle:attachments',
            ])
            ->name('attachments.destroy');
    });
