<?php

namespace App\Services;

use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class VerificationService
{
    public function issueCode(User $user, string $purpose): string
    {
        VerificationCode::where('user_id', $user->id)->where('purpose', $purpose)->delete();

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        VerificationCode::create([
            'user_id'    => $user->id,
            'purpose'    => $purpose,
            'code'       => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
        ]);

        return $code;
    }

    public function checkCode(User $user, string $purpose, string $code): bool
    {
        $record = VerificationCode::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $record || ! Hash::check($code, $record->code)) {
            return false;
        }

        $record->delete();   // a code can only be used once
        return true;
    }

    public function verifyUrl(User $user): string
    {
        return URL::temporarySignedRoute('verify', now()->addMinutes(60), ['email' => $user->email]);
    }

    public function sendCode(User $user, string $purpose): void
    {
        $code = $this->issueCode($user, $purpose);

        $body = $purpose === 'register'
            ? "Hi {$user->name},\n\nYour UniHELP verification code is: {$code}\n\nVerify your account here:\n" . $this->verifyUrl($user) . "\n\nThe code expires in 10 minutes."
            : "Hi {$user->name},\n\nYour UniHELP login code is: {$code}\n\nThe code expires in 10 minutes.";

        Mail::raw($body, function ($mail) use ($user, $purpose) {
            $mail->to($user->email)->subject($purpose === 'register' ? 'Verify your UniHELP account' : 'Your UniHELP login code');
        });
    }
}