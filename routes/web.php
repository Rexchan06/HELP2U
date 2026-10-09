<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SupportSessionController;

Route::middleware('auth')->group(function () {

    Route::get('/support-requests/{supportRequest}/sessions/create', [SupportSessionController::class, 'create'])
        ->name('support-sessions.create');

    Route::post('/support-requests/{supportRequest}/sessions', [SupportSessionController::class, 'store'])
        ->name('support-sessions.store');

    Route::get('/support-sessions', [SupportSessionController::class, 'index'])
        ->name('support-sessions.index');

    Route::get('/support-sessions/{supportSession}', [SupportSessionController::class, 'show'])
        ->name('support-sessions.show');
});