<?php

use App\Http\Controllers\Mission\SearchMissionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'session.current', 'password.changed'])
    ->prefix('missions')
    ->name('missions.')
    ->group(function (): void {
        Route::get('/', [SearchMissionController::class, 'ctrl_index'])
            ->middleware('permission:missions_assign,missions_record_any_result,missions_record_own_result,missions_review_found')
            ->name('index');
        Route::get('/{searchMission}', [SearchMissionController::class, 'ctrl_show'])
            ->whereUuid('searchMission')
            ->middleware('permission:missions_assign,missions_record_any_result,missions_record_own_result,missions_review_found')
            ->name('show');
        Route::post('/forms/{missingForm}', [SearchMissionController::class, 'ctrl_assign'])
            ->whereUuid('missingForm')
            ->middleware('permission:missions_assign')
            ->name('assign');
        Route::patch('/{searchMission}/result', [SearchMissionController::class, 'ctrl_recordResult'])
            ->whereUuid('searchMission')
            ->middleware('permission:missions_record_own_result,missions_record_any_result')
            ->name('result.store');
        Route::patch('/{searchMission}/start', [SearchMissionController::class, 'ctrl_start'])
            ->whereUuid('searchMission')
            ->middleware('permission:missions_record_own_result,missions_record_any_result')
            ->name('start');
        Route::patch('/{searchMission}/cancel', [SearchMissionController::class, 'ctrl_cancel'])
            ->whereUuid('searchMission')
            ->middleware('permission:missions_assign')
            ->name('cancel');
        Route::patch('/{searchMission}/reassign', [SearchMissionController::class, 'ctrl_reassign'])
            ->whereUuid('searchMission')
            ->middleware('permission:missions_assign')
            ->name('reassign');
        Route::patch('/{searchMission}/found/confirm', [SearchMissionController::class, 'ctrl_confirmFound'])
            ->whereUuid('searchMission')
            ->middleware('permission:missions_review_found')
            ->name('found.confirm');
        Route::patch('/{searchMission}/found/reject', [SearchMissionController::class, 'ctrl_rejectFound'])
            ->whereUuid('searchMission')
            ->middleware('permission:missions_review_found')
            ->name('found.reject');
    });
