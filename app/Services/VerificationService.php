<?php

namespace App\Services;

use App\Mail\VerificationCodeMail;
use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;

class VerificationService
{
    public const CODE_EXPIRY_MINUTES = 10;   // how long a code works
    public const LINK_EXPIRY_MINUTES = 60;   // how long the emailed link works
    public const MAX_WRONG_CODES     = 5;    // wrong codes allowed before a short lock
    public const LOCK_SECONDS        = 60;

    /**
     * Create a new 6-digit code and return it in plain text (so it can be emailed).
     * Only the hash is saved in the database.
     */
    public function issueCode(User $user, string $purpose): string
    {
        // Keep only one active code per student and purpose.
        VerificationCode::where('user_id', $user->id)->where('purpose', $purpose)->delete();

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        VerificationCode::create([
            'user_id'    => $user->id,
            'purpose'    => $purpose,
            'code'       => Hash::make($code),
            'expires_at' => now()->addMinutes(self::CODE_EXPIRY_MINUTES),
        ]);

        return $code;
    }

    /**
     * Check a typed code. Returns null when it is correct (and uses the code up),
     * or an error message to show the student.
     */
    public function verify(User $user, string $purpose, string $code): ?string
    {
        $key = "verify-code|{$purpose}|{$user->id}";

        if (RateLimiter::tooManyAttempts($key, self::MAX_WRONG_CODES)) {
            return 'Too many incorrect codes. Please try again in ' . RateLimiter::availableIn($key) . ' seconds.';
        }

        $record = VerificationCode::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $record || ! Hash::check($code, $record->code)) {
            RateLimiter::hit($key, self::LOCK_SECONDS);

            return 'This code is invalid or has expired. Please request a new code.';
        }

        $record->delete();          // a code can only be used once
        RateLimiter::clear($key);

        return null;
    }

    /** Link to the verification page. Signed, so it cannot be edited or guessed. */
    public function verifyUrl(User $user): string
    {
        return URL::temporarySignedRoute(
            'verification.show',
            now()->addMinutes(self::LINK_EXPIRY_MINUTES),
            ['user' => $user->id]
        );
    }

    /** Registration email: 6-digit code and a link to the verification page. */
    public function sendRegistrationCode(User $user): void
    {
        $code = $this->issueCode($user, 'register');

        Mail::to($user->email)->send(
            new VerificationCodeMail($user, $code, 'register', $this->verifyUrl($user))
        );
    }

    /** Two-factor email sent after a correct email and password at login. */
    public function sendLoginCode(User $user): void
    {
        $code = $this->issueCode($user, 'login');

        Mail::to($user->email)->send(new VerificationCodeMail($user, $code, 'login'));
    }
}
