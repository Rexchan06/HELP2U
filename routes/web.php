<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\SupportRequestController;
use App\Http\Controllers\SupportSessionController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\VolunteerController;
use App\Http\Controllers\VolunteerMatchController;
use App\Http\Controllers\VolunteerProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::get('/register', [RegisterController::class, 'show'])->name('register');
Route::post('/register', [RegisterController::class, 'store'])->name('register.store');

Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [UserProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [UserProfileController::class, 'update'])->name('profile.update');

    Route::get('/volunteer/profile', [VolunteerProfileController::class, 'edit'])->name('volunteer.profile');
    Route::post('/volunteer/profile', [VolunteerProfileController::class, 'store'])->name('volunteer.profile.store');

    Route::resource('support-requests', SupportRequestController::class)
        ->only(['index', 'create', 'store']);

    Route::get('/volunteers', [VolunteerController::class, 'index'])->name('volunteers.index');
    Route::get('/volunteers/{volunteer}', [VolunteerController::class, 'show'])->name('volunteers.show');
    Route::post('/volunteers/{volunteer}/matches', [VolunteerMatchController::class, 'store'])->name('volunteer-matches.store');

    Route::get('/support-requests/{supportRequest}/sessions/create', [SupportSessionController::class, 'create'])
        ->name('support-sessions.create');
    Route::post('/support-requests/{supportRequest}/sessions', [SupportSessionController::class, 'store'])
        ->name('support-sessions.store');
    Route::get('/support-sessions', [SupportSessionController::class, 'index'])
        ->name('support-sessions.index');
    Route::get('/support-sessions/{supportSession}', [SupportSessionController::class, 'show'])
        ->name('support-sessions.show');
});