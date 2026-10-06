<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\VerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class VerificationController extends Controller
{
    public function show(Request $request)
    {
        $user = User::where('email', $request->query('email'))->firstOrFail();

        if ($user->email_verified_at) {
            return redirect()->route('login')->with('status', 'Your account is already verified. Please log in.');
        }

        return view('authorise.verify', ['email' => $user->email]);
    }

    public function store(Request $request, VerificationService $verification)
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'code'     => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $user = User::where('email', $request->email)->whereNull('email_verified_at')->first();

        if (! $user || ! $verification->checkCode($user, 'register', $request->code)) {
            return back()->withErrors([
                'code' => 'This code is invalid or has expired. Select "Send a new code" to get another one.',
            ]);
        }

        $user->forceFill([
            'password'          => Hash::make($request->password),
            'email_verified_at' => now(),
        ])->save();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Your account is active. Welcome to UniHELP!');
    }

    public function resend(Request $request, VerificationService $verification)
    {
        $user = User::where('email', $request->email)->whereNull('email_verified_at')->first();

        if ($user) {
            $verification->sendCode($user, 'register');
        }

        return back()->with('status', 'We sent a new code to your email.');
    }
}