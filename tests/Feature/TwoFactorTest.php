<?php

// Email verification code (before the password step) and two-factor login (test sheet TC30 to TC39)

use App\Enums\CodePurpose;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use App\Services\VerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

/** A verified student with the password Password123 (already in the users table). */
function twoFactorTestStudent(): User
{
    return User::factory()->create([
        'student_id' => 'B2501146',
        'name'       => 'Tan Yen Tze',
        'email'      => 'yentze@helplive.edu.my',
        'password'   => 'Password123',
    ]);
}

/** Starts a registration that is waiting for its code (nothing is saved in users yet). Returns the token. */
function twoFactorStartRegistration(): string
{
    return app(VerificationService::class)->startRegistration([
        'student_id' => 'B2501146',
        'name'       => 'Tan Yen Tze',
        'email'      => 'yentze@helplive.edu.my',
    ]);
}

/** The newest code that was emailed for a purpose. Needs Mail::fake(). */
function twoFactorSentCode(CodePurpose $purpose): string
{
    return Mail::sent(VerificationCodeMail::class, fn ($mail) => $mail->purpose === $purpose)->last()->code;
}

/** A code that is different from the real one. */
function twoFactorWrongCode(string $realCode): string
{
    return $realCode === '000000' ? '111111' : '000000';
}

it('TC30 - store() function accepts the correct code and opens the create password page', function () {
    Mail::fake();
    $token = twoFactorStartRegistration();

    $this->post(route('verification.store', $token), ['code' => twoFactorSentCode(CodePurpose::Register)])
        ->assertRedirect(route('verification.password', $token))
        ->assertSessionHas('registration_verified');

    // The account is still not saved: the student has to choose a password first
    $this->assertDatabaseMissing('users', ['email' => 'yentze@helplive.edu.my']);
    $this->assertGuest();
});

it('TC31 - store() function does not create an account when the code is wrong', function () {
    Mail::fake();
    $token = twoFactorStartRegistration();

    $this->post(route('verification.store', $token), ['code' => twoFactorWrongCode(twoFactorSentCode(CodePurpose::Register))])
        ->assertSessionHasErrors(['code' => 'This code is invalid or has expired. Please request a new code.']);

    $this->assertDatabaseMissing('users', ['email' => 'yentze@helplive.edu.my']);
    $this->assertGuest();
});

it('TC32 - store() function does not create an account when the code has expired', function () {
    Mail::fake();
    $token = twoFactorStartRegistration();
    $code = twoFactorSentCode(CodePurpose::Register);

    $this->travel(11)->minutes();   // codes last 10 minutes

    $this->post(route('verification.store', $token), ['code' => $code])
        ->assertSessionHasErrors(['code' => 'This code is invalid or has expired. Please request a new code.']);

    $this->assertDatabaseMissing('users', ['email' => 'yentze@helplive.edu.my']);
});

it('TC33 - resend() function emails a new verification code that can be used', function () {
    Mail::fake();
    $token = twoFactorStartRegistration();

    $this->post(route('verification.resend', $token))->assertSessionHas('status');

    Mail::assertSent(VerificationCodeMail::class, 2);

    $this->post(route('verification.store', $token), ['code' => twoFactorSentCode(CodePurpose::Register)])
        ->assertRedirect(route('verification.password', $token));
});

it('TC34 - store() function emails a login code after the correct email and password', function () {
    Mail::fake();
    $user = twoFactorTestStudent();

    $this->post(route('login.store'), [
        'email'    => 'yentze@helplive.edu.my',
        'password' => 'Password123',
    ])->assertRedirect(route('login.2fa'))
      ->assertSessionHas('two_factor_user_id', $user->id);

    $this->assertGuest();
    Mail::assertSent(VerificationCodeMail::class, fn ($mail) => $mail->hasTo('yentze@helplive.edu.my') && $mail->purpose === CodePurpose::Login);
});

it('TC35 - verifyTwoFactor() function logs the student in when the login code is correct', function () {
    Mail::fake();
    $user = twoFactorTestStudent();
    app(VerificationService::class)->sendLoginCode($user);

    $this->withSession(['two_factor_user_id' => $user->id])
        ->post(route('login.2fa.verify'), ['code' => twoFactorSentCode(CodePurpose::Login)])
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

it('TC36 - verifyTwoFactor() function rejects a wrong login code', function () {
    Mail::fake();
    $user = twoFactorTestStudent();
    app(VerificationService::class)->sendLoginCode($user);

    $this->withSession(['two_factor_user_id' => $user->id])
        ->post(route('login.2fa.verify'), ['code' => twoFactorWrongCode(twoFactorSentCode(CodePurpose::Login))])
        ->assertSessionHasErrors(['code' => 'This code is invalid or has expired. Please request a new code.']);

    $this->assertGuest();
});

it('TC37 - show() function sends the student back to registration when the pending registration is gone', function () {
    Mail::fake();
    $verification = app(VerificationService::class);
    $token = twoFactorStartRegistration();
    $link = $verification->verifyUrl($token);

    $verification->forgetRegistration($token);

    $this->get($link)->assertRedirect(route('register'));
});

it('TC38 - showTwoFactor() function redirects to login when the password step was not done', function () {
    $this->get(route('login.2fa'))->assertRedirect(route('login'));
});

it('TC39 - verifyTwoFactor() function blocks a code after 5 wrong attempts, even for the correct code', function () {
    Mail::fake();
    $user = twoFactorTestStudent();
    app(VerificationService::class)->sendLoginCode($user);
    $code = twoFactorSentCode(CodePurpose::Login);
    $wrong = twoFactorWrongCode($code);

    foreach (range(1, 5) as $attempt) {
        $this->withSession(['two_factor_user_id' => $user->id])
            ->post(route('login.2fa.verify'), ['code' => $wrong]);
    }

    $this->withSession(['two_factor_user_id' => $user->id])
        ->post(route('login.2fa.verify'), ['code' => $code])
        ->assertSessionHasErrors('code');

    expect(session('errors')->first('code'))->toContain('Too many incorrect attempts');
    $this->assertGuest();
});
