<?php

namespace App\Http\Controllers;

use App\Enums\CodePurpose;
use App\Models\User;
use App\Services\VerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function show()
    {
        return view('authorise.login');
    }

    /** Step 1: check the email and password, then email a 6-digit code. */
    public function store(Request $request, VerificationService $verification)
    {
        $maxAttempts = 5;    // failed attempts allowed
        $lockSeconds = 20;   // how long the lock lasts

        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $credentials['email'] = strtolower($credentials['email']);

        // One counter per email address and device
        $key = Str::transliterate($credentials['email'] . '|' . $request->ip());

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            return back()->withInput($request->only('email'))->withErrors([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        // Check the email and password without logging the student in yet
        if (! Auth::validate($credentials)) {
            RateLimiter::hit($key, $lockSeconds);

            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'The email or password is incorrect.',
            ]);
        }

        RateLimiter::clear($key);

        // Every account in the users table is already verified, so go straight to the code step.
        $user = User::where('email', $credentials['email'])->firstOrFail();

        $verification->sendLoginCode($user);
        $request->session()->put('two_factor_user_id', $user->id);

        return redirect()->route('login.2fa')
            ->with('status', 'We sent a 6-digit code to ' . $user->email . '.');
    }

    /** Step 2: the page where the student types the emailed code. */
    public function showTwoFactor(Request $request)
    {
        if (! $request->session()->has('two_factor_user_id')) {
            return redirect()->route('login');
        }

        return view('authorise.two-factor');
    }

    /** Step 2: check the code and log the student in. */
    public function verifyTwoFactor(Request $request, VerificationService $verification)
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $user = User::find($request->session()->get('two_factor_user_id'));

        if (! $user) {
            return redirect()->route('login');
        }

        if ($error = $verification->verify($user, CodePurpose::Login, $request->code)) {
            return back()->withErrors(['code' => $error]);
        }

        $request->session()->forget('two_factor_user_id');
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function resendTwoFactor(Request $request, VerificationService $verification)
    {
        $user = User::find($request->session()->get('two_factor_user_id'));

        if (! $user) {
            return redirect()->route('login');
        }

        $verification->sendLoginCode($user);

        return back()->with('status', 'We sent a new code to ' . $user->email . '.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
