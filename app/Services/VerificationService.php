<?php

namespace App\Services;

use App\Enums\CodePurpose;
use App\Mail\VerificationCodeMail;
use App\Models\TwoFactorCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class VerificationService
{
    public const CODE_EXPIRY_MINUTES = 10;   // how long a code works
    public const LINK_EXPIRY_MINUTES = 60;   // how long the emailed link works
    public const MAX_WRONG_CODES     = 5;    // wrong tries allowed for one code

    /**
     * Create a new 6-digit code (saved in the two_factor_codes table) and return it
     * in plain text so it can be emailed. Only the hash is stored.
     */
    public function issueCode(User $user, CodePurpose $purpose): string
    {
        // Only one active code per student and purpose: remove older unused ones.
        TwoFactorCode::where('user_id', $user->id)
            ->where('purpose', $purpose->value)
            ->whereNull('used_at')
            ->delete();

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        TwoFactorCode::create([
            'user_id'    => $user->id,
            'purpose'    => $purpose,
            'code_hash'  => Hash::make($code),
            'expires_at' => now()->addMinutes(self::CODE_EXPIRY_MINUTES),
        ]);

        return $code;
    }

    /**
     * Check a typed code. Returns null when it is correct (the code is then marked as used),
     * or an error message to show the student.
     */
    public function verify(User $user, CodePurpose $purpose, string $code): ?string
    {
        $record = TwoFactorCode::where('user_id', $user->id)
            ->where('purpose', $purpose->value)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $record) {
            return 'This code is invalid or has expired. Please request a new code.';
        }

        if ($record->attempts >= self::MAX_WRONG_CODES) {
            return 'Too many incorrect attempts. Please request a new code.';
        }

        if (! Hash::check($code, $record->code_hash)) {
            $record->increment('attempts');

            return 'This code is invalid or has expired. Please request a new code.';
        }

        $record->update(['used_at' => now()]);   // a code can only be used once

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
        $code = $this->issueCode($user, CodePurpose::Register);

        Mail::to($user->email)->send(
            new VerificationCodeMail($user, $code, CodePurpose::Register, $this->verifyUrl($user))
        );
    }

    /** Two-factor email sent after a correct email and password at login. */
    public function sendLoginCode(User $user): void
    {
        $code = $this->issueCode($user, CodePurpose::Login);

        Mail::to($user->email)->send(new VerificationCodeMail($user, $code, CodePurpose::Login));
    }
}
