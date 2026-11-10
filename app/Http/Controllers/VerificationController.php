<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\VerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerificationController extends Controller
{
    public function show(string $token, VerificationService $verification)
    {
        $pending = $verification->pendingRegistration($token);

        if (! $pending) {
            return redirect()->route('register')
                ->with('status', 'This verification link has expired. Please register again.');
        }

        return view('authorise.verify', ['token' => $token, 'email' => $pending['email']]);
    }

    public function store(Request $request, string $token, VerificationService $verification)
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $pending = $verification->pendingRegistration($token);

        if (! $pending) {
            return redirect()->route('register')
                ->with('status', 'This verification has expired. Please register again.');
        }

        // Wrong or expired code: show the message and let the student ask for a new one.
        if ($error = $verification->checkRegistrationCode($token, $request->code)) {
            return back()->withErrors(['code' => $error]);
        }

        // Someone may have registered the same email or student ID while this one was waiting.
        if (User::where('email', $pending['email'])->orWhere('student_id', $pending['student_id'])->exists()) {
            $verification->forgetRegistration($token);

            return redirect()->route('login')
                ->with('status', 'An account already exists for these details. Please log in.');
        }

        // The code is correct: only now is the account saved in the users table.
        $user = User::forceCreate([
            'student_id'        => $pending['student_id'],
            'name'              => $pending['name'],
            'email'             => $pending['email'],
            'password'          => $pending['password_hash'],
            'email_verified_at' => now(),
        ]);

        $verification->forgetRegistration($token);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Your account is active. Welcome to HELP2U!');
    }

    public function resend(string $token, VerificationService $verification)
    {
        if (! $verification->resendRegistrationCode($token)) {
            return redirect()->route('register')
                ->with('status', 'This verification has expired. Please register again.');
        }

        return back()->with('status', 'We sent a new code to your email.');
    }
}
