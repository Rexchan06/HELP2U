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
    private const PASSWORD_STEP_MINUTES = 15;   // time allowed to choose a password after the code

    // ---------- Step 2: the verification code ----------

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

        if ($this->alreadyRegistered($pending)) {
            $verification->forgetRegistration($token);

            return redirect()->route('login')
                ->with('status', 'An account already exists for these details. Please log in.');
        }

        // The code is correct: the student may now choose a password (still nothing saved in users).
        $verification->markRegistrationVerified($token);

        $request->session()->put('registration_verified', [
            'token'   => $token,
            'expires' => now()->addMinutes(self::PASSWORD_STEP_MINUTES)->timestamp,
        ]);

        return redirect()->route('verification.password', $token)
            ->with('status', 'Your email is verified. Now choose a password to finish creating your account.');
    }

    public function resend(string $token, VerificationService $verification)
    {
        if (! $verification->resendRegistrationCode($token)) {
            return redirect()->route('register')
                ->with('status', 'This verification has expired. Please register again.');
        }

        return back()->with('status', 'We sent a new code to your email.');
    }

    // ---------- Step 3: choose a password ----------

    public function showPassword(Request $request, string $token, VerificationService $verification)
    {
        $pending = $this->verifiedRegistration($request, $token, $verification);

        if (! $pending) {
            return redirect()->route('register')
                ->with('status', 'Please verify your email first. If your link has expired, register again.');
        }

        return view('authorise.set-password', ['token' => $token, 'email' => $pending['email']]);
    }

    public function storePassword(Request $request, string $token, VerificationService $verification)
    {
        $pending = $this->verifiedRegistration($request, $token, $verification);

        if (! $pending) {
            return redirect()->route('register')
                ->with('status', 'Please verify your email first. If your link has expired, register again.');
        }

        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        if ($this->alreadyRegistered($pending)) {
            $verification->forgetRegistration($token);

            return redirect()->route('login')
                ->with('status', 'An account already exists for these details. Please log in.');
        }

        // Only now is the account saved in the users table.
        $user = User::forceCreate([
            'student_id'        => $pending['student_id'],
            'name'              => $pending['name'],
            'email'             => $pending['email'],
            'password'          => Hash::make($data['password']),
            'email_verified_at' => now(),
        ]);

        $verification->forgetRegistration($token);
        $request->session()->forget('registration_verified');

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Your account is active. Welcome to HELP2U!');
    }

    // ---------- Helpers ----------

    /** The waiting registration, if its code was verified in this browser and the time has not run out. */
    private function verifiedRegistration(Request $request, string $token, VerificationService $verification): ?array
    {
        $pending = $verification->pendingRegistration($token);
        $session = $request->session()->get('registration_verified');

        if (! $pending
            || empty($pending['verified_at'])
            || ! $session
            || $session['token'] !== $token
            || $session['expires'] < now()->timestamp) {
            return null;
        }

        return $pending;
    }

    /** Someone may have registered the same email or student ID while this one was waiting. */
    private function alreadyRegistered(array $pending): bool
    {
        return User::where('email', $pending['email'])
            ->orWhere('student_id', $pending['student_id'])
            ->exists();
    }
}
