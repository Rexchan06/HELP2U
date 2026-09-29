<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));
Route::view('/login', 'authorise.login')->name('login');
Route::view('/register', 'authorise.register')->name('register');
Route::view('/verify', 'authorise.verify')->name('verify');
Route::view('/dashboard', 'dashboard')->name('dashboard');
Route::view('/two-factor', 'authorise.two-factor')->name('two-factor');
