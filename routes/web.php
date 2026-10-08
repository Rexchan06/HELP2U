<?php

use App\Http\Controllers\SupportRequestController;
use App\Http\Controllers\VolunteerController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/support-requests');

// NOTE: Auth module is not merged yet, so routes are temporarily public.
// When login is ready, delete the block below and uncomment the
// auth-protected group instead — then in SupportRequestController,
// replace $this->currentUser($request) with $request->user().
Route::resource('support-requests', SupportRequestController::class)
    ->only(['index', 'create', 'store']);

Route::get('volunteers', [VolunteerController::class, 'index'])->name('volunteers.index');
Route::get('volunteers/{volunteer}', [VolunteerController::class, 'show'])->name('volunteers.show');
 
// Route::get('/login', function () {
//     abort_unless(app()->environment('local'), 404);
 
//     $user = User::first() ?? User::factory()->create([
//         'name' => 'Test User',
//         'email' => 'test@example.com',
//     ]);
 
//     auth()->login($user);
 
//     return redirect()->route('support-requests.index');
// })->name('login');
 
// Route::middleware('auth')->group(function () {
//     Route::resource('support-requests', SupportRequestController::class)
//         ->only(['index', 'create', 'store']);
//     Route::get('volunteers', [VolunteerController::class, 'index'])->name('volunteers.index');
//     Route::get('volunteers/{volunteer}', [VolunteerController::class, 'show'])->name('volunteers.show');
// });
