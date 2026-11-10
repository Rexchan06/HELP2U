<?php

namespace App\Http\Controllers;

use App\Enums\CodePurpose;
use App\Models\TwoFactorCode;
use App\Models\User;
use App\Services\VerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordResetController extends Controller
{
    private const SENT_MESSAGE = 'If that email has an account, we sent a 6-digit code to it.';
    private const RESENT_MESSAGE = 'If that email has an account, we sent a new code to it.';
    private const BAD_CODE_MESSAGE = 'This code is invalid or has expired. Please request a new code.';

    // ---------- Step 1: ask for the email ----------

    public function showRequestForm()
    {
        return view('authorise.forgot-password');
    }

    public function sendCode(Request $request, VerificationService $verification)
    {
        $request->merge(['email' => strtolower(trim((string) $request->email))]);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if ($user) {
            $verification->sendResetCode($user);
        }

        $request->session()->put('password_reset_email', $data['email']);

        return redirect()->route('password.code')->with('status', self::SENT_MESSAGE);
    }

    // ---------- Step 2: enter the emailed code ----------

    public function showCodeForm(Request $request)
    {
        if (! $request->session()->has('password_reset_email')) {
            return redirect()->route('password.request');
        }

        return view('authorise.reset-code', [
            'email' => $request->session()->get('password_reset_email'),
        ]);
    }

    public function verifyCode(Request $request, VerificationService $verification)
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $email = $request->session()->get('password_reset_email');

        if (! $email) {
            return redirect()->route('password.request');
        }

        $user = User::where('email', $email)->first();

        $error = $user
            ? $verification->verify($user, CodePurpose::Reset, $request->code)
            : self::BAD_CODE_MESSAGE;

        if ($error) {
            return back()->withErrors(['code' => $error]);
        }

        // The code was correct: allow the password to be changed for the next 10 minutes.
        $request->session()->put('password_reset_verified', [
            'user_id' => $user->id,
            'expires' => now()->addMinutes(10)->timestamp,
        ]);

        return redirect()->route('password.reset');
    }

    public function resendCode(Request $request, VerificationService $verification)
    {
        $email = $request->session()->get('password_reset_email');

        if (! $email) {
            return redirect()->route('password.request');
        }

        if ($user = User::where('email', $email)->first()) {
            $verification->sendResetCode($user);
        }

        return back()->with('status', self::RESENT_MESSAGE);
    }

    // ---------- Step 3: choose a new password ----------

    public function showResetForm(Request $request)
    {
        if (! $this->verifiedUser($request)) {
            return redirect()->route('password.request')
                ->with('status', 'Please request a new code to reset your password.');
        }

        return view('authorise.reset-password');
    }

    public function reset(Request $request)
    {
        $user = $this->verifiedUser($request);

        if (! $user) {
            return redirect()->route('password.request')
                ->with('status', 'Please request a new code to reset your password.');
        }

        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        if (Hash::check($data['password'], $user->password)) {
            return back()->withErrors([
                'password' => 'Choose a password that is different from your current one.',
            ]);
        }

        $user->forceFill(['password' => Hash::make($data['password'])])->save();

        // Remove any reset codes that are still waiting, and end the reset session.
        TwoFactorCode::where('user_id', $user->id)
            ->where('purpose', CodePurpose::Reset->value)
            ->delete();

        $request->session()->forget(['password_reset_email', 'password_reset_verified']);

        return redirect()->route('login')
            ->with('status', 'Your password has been changed. Please log in with your new password.');
    }

    /** The student whose code was verified, if that was done in the last 10 minutes. */
    private function verifiedUser(Request $request): ?User
    {
        $verified = $request->session()->get('password_reset_verified');

        if (! $verified || $verified['expires'] < now()->timestamp) {
            return null;
        }

        return User::find($verified['user_id']);
    }
}
