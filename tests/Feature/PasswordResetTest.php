<?php

// Forgot password: email, code, new password (test sheet TC40 to TC51)

use App\Enums\CodePurpose;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use App\Services\VerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

/** A registered student whose password is Password123. */
function resetTestStudent(): User
{
    return User::factory()->create([
        'student_id' => 'B2501146',
        'name'       => 'Tan Yen Tze',
        'email'      => 'yentze@helplive.edu.my',
        'password'   => 'Password123',
    ]);
}

/** Session data that means "the code was verified and the new password may be set". */
function resetVerifiedSession(User $user, bool $expired = false): array
{
    return ['password_reset_verified' => [
        'user_id' => $user->id,
        'expires' => $expired ? now()->subMinute()->timestamp : now()->addMinutes(10)->timestamp,
    ]];
}

/** The newest reset code that was emailed. Needs Mail::fake(). */
function resetSentCode(): string
{
    return Mail::sent(VerificationCodeMail::class, fn ($mail) => $mail->purpose === CodePurpose::Reset)->last()->code;
}

it('UC1-TC40 - sendCode() function emails a reset code to a registered email', function () {
    Mail::fake();
    resetTestStudent();

    $this->post(route('password.email'), ['email' => 'yentze@helplive.edu.my'])
        ->assertRedirect(route('password.code'))
        ->assertSessionHas('password_reset_email', 'yentze@helplive.edu.my');

    Mail::assertSent(VerificationCodeMail::class, fn ($mail) => $mail->hasTo('yentze@helplive.edu.my') && $mail->purpose === CodePurpose::Reset);
});

it('UC1-TC41 - sendCode() function gives the same answer for an email that has no account', function () {
    Mail::fake();

    $this->post(route('password.email'), ['email' => 'nobody@helplive.edu.my'])
        ->assertRedirect(route('password.code'))
        ->assertSessionHasNoErrors();

    Mail::assertNothingSent();
});

it('UC1-TC42 - sendCode() function rejects an email with an incorrect format', function () {
    Mail::fake();

    $this->post(route('password.email'), ['email' => 'yentzehelplive.edu.my'])
        ->assertSessionHasErrors(['email' => 'The email field must be a valid email address.']);

    Mail::assertNothingSent();
});

it('UC1-TC43 - verifyCode() function accepts the correct code and opens the new password step', function () {
    Mail::fake();
    $user = resetTestStudent();
    app(VerificationService::class)->sendResetCode($user);

    $this->withSession(['password_reset_email' => $user->email])
        ->post(route('password.code.verify'), ['code' => resetSentCode()])
        ->assertRedirect(route('password.reset'))
        ->assertSessionHas('password_reset_verified');
});

it('UC1-TC44 - verifyCode() function rejects a wrong code', function () {
    Mail::fake();
    $user = resetTestStudent();
    app(VerificationService::class)->sendResetCode($user);
    $code = resetSentCode();

    $this->withSession(['password_reset_email' => $user->email])
        ->post(route('password.code.verify'), ['code' => $code === '000000' ? '111111' : '000000'])
        ->assertSessionHasErrors(['code' => 'This code is invalid or has expired. Please request a new code.'])
        ->assertSessionMissing('password_reset_verified');
});

it('UC1-TC45 - verifyCode() function rejects a code that has expired', function () {
    Mail::fake();
    $user = resetTestStudent();
    app(VerificationService::class)->sendResetCode($user);
    $code = resetSentCode();

    $this->travel(11)->minutes();   // codes last 10 minutes

    $this->withSession(['password_reset_email' => $user->email])
        ->post(route('password.code.verify'), ['code' => $code])
        ->assertSessionHasErrors(['code' => 'This code is invalid or has expired. Please request a new code.']);
});

it('UC1-TC46 - showResetForm() function redirects to the start when the code was not verified', function () {
    $this->get(route('password.reset'))->assertRedirect(route('password.request'));
});

it('UC1-TC47 - showResetForm() function redirects to the start when the verified code has timed out', function () {
    $user = resetTestStudent();

    $this->withSession(resetVerifiedSession($user, expired: true))
        ->get(route('password.reset'))
        ->assertRedirect(route('password.request'));
});

it('UC1-TC48 - reset() function changes the password after the code was verified', function () {
    $user = resetTestStudent();

    $this->withSession(resetVerifiedSession($user))
        ->post(route('password.update'), [
            'password'              => 'NewPass456',
            'password_confirmation' => 'NewPass456',
        ])
        ->assertRedirect(route('login'));

    $user->refresh();

    expect(Hash::check('NewPass456', $user->password))->toBeTrue()
        ->and(Hash::check('Password123', $user->password))->toBeFalse();
});

it('UC1-TC49 - reset() function rejects a new password shorter than 8 characters', function () {
    $user = resetTestStudent();

    $this->withSession(resetVerifiedSession($user))
        ->post(route('password.update'), [
            'password'              => 'Pass12',
            'password_confirmation' => 'Pass12',
        ])
        ->assertSessionHasErrors(['password' => 'The password field must be at least 8 characters.']);

    expect(Hash::check('Password123', $user->fresh()->password))->toBeTrue();
});

it('UC1-TC50 - reset() function rejects two different passwords', function () {
    $user = resetTestStudent();

    $this->withSession(resetVerifiedSession($user))
        ->post(route('password.update'), [
            'password'              => 'NewPass456',
            'password_confirmation' => 'NewPass654',
        ])
        ->assertSessionHasErrors(['password' => 'The password field confirmation does not match.']);

    expect(Hash::check('Password123', $user->fresh()->password))->toBeTrue();
});

it('UC1-TC51 - reset() function rejects the same password as the current one', function () {
    $user = resetTestStudent();

    $this->withSession(resetVerifiedSession($user))
        ->post(route('password.update'), [
            'password'              => 'Password123',
            'password_confirmation' => 'Password123',
        ])
        ->assertSessionHasErrors(['password' => 'Choose a password that is different from your current one.']);
});
