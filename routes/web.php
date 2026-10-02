<?php

use App\Http\Controllers\SupportRequestController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/support-requests');

Route::get('/login', function () {
    abort_unless(app()->environment('local'), 404);

    auth()->login(User::firstOrFail());

    return redirect()->route('support-requests.index');
})->name('login');

Route::middleware('auth')->group(function () {
    Route::resource('support-requests', SupportRequestController::class)
        ->only(['index', 'create', 'store']);
});
