<?php

use Illuminate\Support\Facades\Route;
use Edscool\Plugins\ClockIn\Http\Controllers\ClockInController;

Route::middleware(['web', 'auth'])
    ->prefix('portal/plugins/clockin')
    ->name('plugins.clockin.')
    ->group(function () {
        Route::get('/', [ClockInController::class, 'index'])->name('index');
        Route::post('/clock-in', [ClockInController::class, 'clockIn'])->name('clockin');
        Route::post('/undo', [ClockInController::class, 'undoClockIn'])->name('undo');
    });
