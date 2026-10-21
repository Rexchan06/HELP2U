<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\VerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerificationController extends Controller
{
    public function show(User $user)
    {
        if ($user->email_verified_at) {
            return redirect()->route('login')->with('status', 'Your account is already verified. Please log in.');
        }

        return view('authorise.verify', ['user' => $user]);
    }

    public function store(Request $request, User $user, VerificationService $verification)
    {
        if ($user->email_verified_at) {
            return redirect()->route('login');
        }

        $request->validate(['code' => ['required', 'digits:6']]);

        // Wrong or expired code: show the message and let the student ask for a new one.
        if ($error = $verification->verify($user, 'register', $request->code)) {
            return back()->withErrors(['code' => $error]);
        }

        $user->forceFill(['email_verified_at' => now()])->save();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Your account is active. Welcome to HELP2U!');
    }

    public function resend(User $user, VerificationService $verification)
    {
        if ($user->email_verified_at) {
            return redirect()->route('login');
        }

        $verification->sendRegistrationCode($user);

        return back()->with('status', 'We sent a new code to ' . $user->email . '.');
    }
}
