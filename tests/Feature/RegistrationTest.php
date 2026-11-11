<?php

// Use Case 1 - Registration: details, then code, then password (test sheet TC1 to TC11, TC58, TC60, TC61)

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
    Mail::fake();   // no real emails are sent while testing
});

/** Valid registration details from the test sheet (no password: it is chosen after the code). */
function validRegistrationData(array $overrides = []): array
{
    return array_merge([
        'student_id' => 'B2501146',
        'name'       => 'Tan Yen Tze',
        'email'      => 'yentze@helplive.edu.my',
    ], $overrides);
}

/**
 * A registration whose code was already entered correctly, so the create password page is open.
 * Returns [token, session data] - use the session data with $this->withSession().
 */
function registrationVerifiedState(): array
{
    $verification = app(VerificationService::class);

    $token = $verification->startRegistration(validRegistrationData());
    $verification->markRegistrationVerified($token);

    return [$token, ['registration_verified' => [
        'token'   => $token,
        'expires' => now()->addMinutes(15)->timestamp,
    ]]];
}

/** Sends a new password to the create password page. */
function postPassword($test, string $token, array $session, string $password, ?string $confirmation = null)
{
    return $test->withSession($session)->post(route('verification.password.store', $token), [
        'password'              => $password,
        'password_confirmation' => $confirmation ?? $password,
    ]);
}

it('TC1 - store() function waits for email verification and does not save the account yet', function () {
    $response = $this->post(route('register.store'), validRegistrationData());

    // The student is sent to the verification page
    expect($response->headers->get('Location'))->toContain('/verify/');

    // Nothing is saved in the users table yet
    $this->assertDatabaseMissing('users', ['email' => 'yentze@helplive.edu.my']);

    Mail::assertSent(VerificationCodeMail::class, fn ($mail) => $mail->hasTo('yentze@helplive.edu.my') && $mail->purpose === CodePurpose::Register);
});

it('TC2 - store() function sends the verification email to the lowercase address', function () {
    $this->post(route('register.store'), validRegistrationData(['email' => 'yEntze@Helplive.edu.my']))
        ->assertSessionHasNoErrors();

    Mail::assertSent(VerificationCodeMail::class, fn ($mail) => $mail->hasTo('yentze@helplive.edu.my'));
});

it('TC3 - storePassword() function saves the password as a hash and not as plain text', function () {
    [$token, $session] = registrationVerifiedState();

    postPassword($this, $token, $session, 'Password123');

    $user = User::where('email', 'yentze@helplive.edu.my')->firstOrFail();

    expect($user->password)->not->toBe('Password123')
        ->and($user->password)->toStartWith('$2y$')
        ->and(Hash::check('Password123', $user->password))->toBeTrue();
});

it('TC4 - store() function rejects the form when every field is empty', function () {
    $this->post(route('register.store'), [])
        ->assertSessionHasErrors(['student_id' => 'Enter valid Student ID', 'name', 'email']);

    $this->assertDatabaseCount('users', 0);
    Mail::assertNothingSent();
});

it('TC5 - store() function rejects an email with an incorrect format', function () {
    $this->post(route('register.store'), validRegistrationData(['email' => 'yentzehelplive.edu.my']))
        ->assertSessionHasErrors(['email' => 'The email field must be a valid email address.']);

    Mail::assertNothingSent();
});

it('TC6 - store() function rejects a student ID and email that are already registered', function () {
    User::factory()->create([
        'student_id' => 'B2501146',
        'email'      => 'yentze@helplive.edu.my',
    ]);

    $this->post(route('register.store'), validRegistrationData(['name' => 'Bob']))
        ->assertSessionHasErrors([
            'student_id' => 'This student ID is already registered.',
            'email'      => 'An account already exists for this email.',
        ]);

    $this->assertDatabaseCount('users', 1);
    Mail::assertNothingSent();
});

it('TC7 - store() function rejects a student ID that is not B followed by 7 digits', function () {
    $this->post(route('register.store'), validRegistrationData([
        'student_id' => 'W1234s56',
        'name'       => 'Bob',
        'email'      => 'abc@helplive.edu.my',
    ]))->assertSessionHasErrors(['student_id' => 'Enter valid Student ID']);

    Mail::assertNothingSent();
});

it('TC8 - storePassword() function rejects a password shorter than 8 characters', function () {
    [$token, $session] = registrationVerifiedState();

    postPassword($this, $token, $session, 'Pass12')
        ->assertSessionHasErrors(['password' => 'The password field must be at least 8 characters.']);

    $this->assertDatabaseMissing('users', ['email' => 'yentze@helplive.edu.my']);
});

it('TC9 - storePassword() function rejects a password that has no number', function () {
    [$token, $session] = registrationVerifiedState();

    postPassword($this, $token, $session, 'PasswordOnly')
        ->assertSessionHasErrors(['password' => 'The password field must contain at least one number.']);

    $this->assertDatabaseMissing('users', ['email' => 'yentze@helplive.edu.my']);
});

it('TC10 - storePassword() function rejects a password that has no letter', function () {
    [$token, $session] = registrationVerifiedState();

    postPassword($this, $token, $session, '12345678')
        ->assertSessionHasErrors(['password' => 'The password field must contain at least one letter.']);

    $this->assertDatabaseMissing('users', ['email' => 'yentze@helplive.edu.my']);
});

it('TC11 - storePassword() function rejects two different passwords', function () {
    [$token, $session] = registrationVerifiedState();

    postPassword($this, $token, $session, 'Password123', 'Password321')
        ->assertSessionHasErrors(['password' => 'The password field confirmation does not match.']);

    $this->assertDatabaseMissing('users', ['email' => 'yentze@helplive.edu.my']);
});

it('TC58 - store() function rejects an email that does not end with @helplive.edu.my', function () {
    $this->post(route('register.store'), validRegistrationData([
        'student_id' => 'B1234567',
        'name'       => 'Bob',
        'email'      => 'bob@gmail.com',
    ]))->assertSessionHasErrors(['email' => 'Please use your @helplive.edu.my email address.']);

    Mail::assertNothingSent();
});

it('TC60 - storePassword() function creates the account, logs the student in and opens the dashboard', function () {
    [$token, $session] = registrationVerifiedState();

    // Before the password is chosen the student is not in the users table
    $this->assertDatabaseMissing('users', ['email' => 'yentze@helplive.edu.my']);

    postPassword($this, $token, $session, 'Password123')
        ->assertRedirect(route('dashboard'));

    $user = User::where('email', 'yentze@helplive.edu.my')->firstOrFail();

    expect($user->student_id)->toBe('B2501146')
        ->and($user->name)->toBe('Tan Yen Tze')
        ->and($user->email_verified_at)->not->toBeNull();
    $this->assertAuthenticatedAs($user);
});

it('TC61 - showPassword() function sends the student back to registration when the code was not verified', function () {
    // A registration that is waiting for its code (the code has not been entered)
    $token = app(VerificationService::class)->startRegistration(validRegistrationData());

    $this->get(route('verification.password', $token))
        ->assertRedirect(route('register'));

    $this->assertDatabaseMissing('users', ['email' => 'yentze@helplive.edu.my']);
});
