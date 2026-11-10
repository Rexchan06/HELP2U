<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\SupportRequestController;
use App\Http\Controllers\SupportSessionController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\VolunteerController;
use App\Http\Controllers\VolunteerMatchController;
use App\Http\Controllers\VolunteerProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FeedbackController;

Route::get('/', fn () => redirect()->route('login'));

Route::get('/register', [RegisterController::class, 'show'])->name('register');
Route::post('/register', [RegisterController::class, 'store'])->name('register.store');

Route::get('/verify/{token}', [VerificationController::class, 'show'])->middleware('signed')->name('verification.show');
Route::post('/verify/{token}', [VerificationController::class, 'store'])->middleware('throttle:10,1')->name('verification.store');
Route::post('/verify/{token}/resend', [VerificationController::class, 'resend'])->middleware('throttle:3,1')->name('verification.resend');

Route::get('/login/2fa', [LoginController::class, 'showTwoFactor'])->name('login.2fa');
Route::post('/login/2fa', [LoginController::class, 'verifyTwoFactor'])->middleware('throttle:10,1')->name('login.2fa.verify');
Route::post('/login/2fa/resend', [LoginController::class, 'resendTwoFactor'])->middleware('throttle:3,1')->name('login.2fa.resend');

Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/forgot-password', [PasswordResetController::class, 'showRequestForm'])->name('password.request');
Route::post('/forgot-password', [PasswordResetController::class, 'sendCode'])->middleware('throttle:5,1')->name('password.email');
Route::get('/forgot-password/code', [PasswordResetController::class, 'showCodeForm'])->name('password.code');
Route::post('/forgot-password/code', [PasswordResetController::class, 'verifyCode'])->middleware('throttle:10,1')->name('password.code.verify');
Route::post('/forgot-password/resend', [PasswordResetController::class, 'resendCode'])->middleware('throttle:3,1')->name('password.resend');
Route::get('/reset-password', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');

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

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');

    Route::get('/matches/inbox', [VolunteerMatchController::class, 'index'])->name('volunteer-matches.index');
    Route::get('/matches/{match}', [VolunteerMatchController::class, 'show'])->name('volunteer-matches.show');
    Route::post('/matches/{match}/respond', [VolunteerMatchController::class, 'respond'])->name('volunteer-matches.respond');

    Route::get('/support-requests/{supportRequest}/sessions/create', [SupportSessionController::class, 'create'])
        ->name('support-sessions.create');
    Route::post('/support-requests/{supportRequest}/sessions', [SupportSessionController::class, 'store'])
        ->name('support-sessions.store');
    Route::get('/support-sessions', [SupportSessionController::class, 'index'])
        ->name('support-sessions.index');
    Route::get('/support-sessions/{supportSession}', [SupportSessionController::class, 'show'])
        ->name('support-sessions.show');
    Route::patch('/support-sessions/{supportSession}/cancel', [SupportSessionController::class, 'cancel'])
        ->name('support-sessions.cancel');
    Route::get('/support-sessions/{supportSession}/reschedule', [SupportSessionController::class, 'editSchedule'])
        ->name('support-sessions.reschedule');
    Route::patch('/support-sessions/{supportSession}/reschedule', [SupportSessionController::class, 'reschedule'])
        ->name('support-sessions.reschedule.update');
    Route::patch('/support-sessions/{supportSession}/complete', [SupportSessionController::class, 'complete'])
        ->name('support-sessions.complete');
    Route::post('/support-sessions/{supportSession}/feedback', [FeedbackController::class, 'store'])
        ->name('feedback.store');
    Route::get('/feedback', [FeedbackController::class, 'index'])->name('feedback.index');
});