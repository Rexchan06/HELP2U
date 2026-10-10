<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\VolunteerProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::get('/register', [RegisterController::class, 'show'])->name('register');
Route::post('/register', [RegisterController::class, 'store'])->name('register.store');

Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('auth')->name('dashboard');
Route::get('/profile', [UserProfileController::class, 'edit'])->middleware('auth')->name('profile.edit');
Route::put('/profile', [UserProfileController::class, 'update'])->middleware('auth')->name('profile.update');


Route::view('/volunteer/profile', 'volunteer.profile')->middleware('auth')->name('volunteer.profile');
Route::get('/volunteer/profile', [VolunteerProfileController::class, 'edit'])->middleware('auth')->name('volunteer.profile');
Route::post('/volunteer/profile', [VolunteerProfileController::class, 'store'])->middleware('auth')->name('volunteer.profile.store');