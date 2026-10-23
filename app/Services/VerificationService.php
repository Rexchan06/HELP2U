<?php

namespace App\Services;

use App\Enums\CodePurpose;
use App\Mail\VerificationCodeMail;
use App\Models\TwoFactorCode;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class VerificationService
{
    public const CODE_EXPIRY_MINUTES = 10;   // how long a code works
    public const LINK_EXPIRY_MINUTES = 60;   // how long a pending registration (and its link) is kept
    public const MAX_WRONG_CODES     = 5;    // wrong tries allowed for one code

    // =====================================================================
    //  REGISTRATION: nothing is saved in the users table until the code is verified.
    //  The details wait in the cache, under a random token, for up to 60 minutes.
    // =====================================================================

    /**
     * Keep the registration details in the cache, email a code and a link,
     * and return the token that identifies this pending registration.
     * $data needs: student_id, name, email, password_hash.
     */
    public function startRegistration(array $data): string
    {
        // A student who registers again replaces their earlier pending registration.
        $emailKey = 'pending-email:' . $data['email'];

        if ($oldToken = Cache::pull($emailKey)) {
            Cache::forget($this->pendingKey($oldToken));
        }

        $token = Str::random(40);
        $code = $this->newCode();
        $linkExpiry = now()->addMinutes(self::LINK_EXPIRY_MINUTES);

        $pending = $data + [
            'code_hash'       => Hash::make($code),
            'code_expires_at' => now()->addMinutes(self::CODE_EXPIRY_MINUTES)->timestamp,
            'link_expires_at' => $linkExpiry->timestamp,
            'attempts'        => 0,
        ];

        Cache::put($this->pendingKey($token), $pending, $linkExpiry);
        Cache::put($emailKey, $token, $linkExpiry);

        $this->sendRegistrationEmail($pending, $code, $token);

        return $token;
    }

    /** The waiting registration details, or null if the token is unknown or expired. */
    public function pendingRegistration(string $token): ?array
    {
        return Cache::get($this->pendingKey($token));
    }

    /** Email a new code for a waiting registration. Returns false if it has expired. */
    public function resendRegistrationCode(string $token): bool
    {
        $pending = $this->pendingRegistration($token);

        if (! $pending) {
            return false;
        }

        $code = $this->newCode();
        $pending['code_hash'] = Hash::make($code);
        $pending['code_expires_at'] = now()->addMinutes(self::CODE_EXPIRY_MINUTES)->timestamp;
        $pending['attempts'] = 0;

        $this->savePending($token, $pending);
        $this->sendRegistrationEmail($pending, $code, $token);

        return true;
    }

    /** Check a typed registration code. Returns null if correct, or an error message. */
    public function checkRegistrationCode(string $token, string $code): ?string
    {
        $pending = $this->pendingRegistration($token);

        if (! $pending || $pending['code_expires_at'] < now()->timestamp) {
            return 'This code is invalid or has expired. Please request a new code.';
        }

        if ($pending['attempts'] >= self::MAX_WRONG_CODES) {
            return 'Too many incorrect attempts. Please request a new code.';
        }

        if (! Hash::check($code, $pending['code_hash'])) {
            $pending['attempts']++;
            $this->savePending($token, $pending);

            return 'This code is invalid or has expired. Please request a new code.';
        }

        return null;
    }

    /** Remove a waiting registration (after the account has been created). */
    public function forgetRegistration(string $token): void
    {
        if ($pending = $this->pendingRegistration($token)) {
            Cache::forget('pending-email:' . $pending['email']);
        }

        Cache::forget($this->pendingKey($token));
    }

    /** Link to the verification page. Signed, so it cannot be edited or guessed. */
    public function verifyUrl(string $token): string
    {
        return URL::temporarySignedRoute(
            'verification.show',
            now()->addMinutes(self::LINK_EXPIRY_MINUTES),
            ['token' => $token]
        );
    }

    // =====================================================================
    //  LOGIN: the student already exists, so the code is saved in two_factor_codes.
    // =====================================================================

    /** Create a login code (saved hashed in two_factor_codes) and return it in plain text. */
    public function issueCode(User $user, CodePurpose $purpose): string
    {
        // Only one active code per student and purpose: remove older unused ones.
        TwoFactorCode::where('user_id', $user->id)
            ->where('purpose', $purpose->value)
            ->whereNull('used_at')
            ->delete();

        $code = $this->newCode();

        TwoFactorCode::create([
            'user_id'    => $user->id,
            'purpose'    => $purpose,
            'code_hash'  => Hash::make($code),
            'expires_at' => now()->addMinutes(self::CODE_EXPIRY_MINUTES),
        ]);

        return $code;
    }

    /** Check a typed login code. Returns null when correct (code is then used up), or an error message. */
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

    /** Two-factor email sent after a correct email and password at login. */
    public function sendLoginCode(User $user): void
    {
        $code = $this->issueCode($user, CodePurpose::Login);

        Mail::to($user->email)->send(new VerificationCodeMail($user->name, $code, CodePurpose::Login));
    }

    // =====================================================================
    //  Helpers
    // =====================================================================

    private function newCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function pendingKey(string $token): string
    {
        return 'pending-registration:' . $token;
    }

    private function savePending(string $token, array $pending): void
    {
        Cache::put($this->pendingKey($token), $pending, Carbon::createFromTimestamp($pending['link_expires_at']));
    }

    private function sendRegistrationEmail(array $pending, string $code, string $token): void
    {
        Mail::to($pending['email'])->send(
            new VerificationCodeMail($pending['name'], $code, CodePurpose::Register, $this->verifyUrl($token))
        );
    }
}
