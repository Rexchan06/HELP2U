<?php

// Use Case 1 - Registration (test sheet TC1 to TC11 and TC58)

use App\Enums\CodePurpose;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    Mail::fake();   // no real emails are sent while testing
});

/** Valid registration details from the test sheet. Each test changes only what it needs. */
function validRegistrationData(array $overrides = []): array
{
    return array_merge([
        'student_id'            => 'B2501146',
        'name'                  => 'Tan Yen Tze',
        'email'                 => 'yentze@helplive.edu.my',
        'password'              => 'Password123',
        'password_confirmation' => 'Password123',
    ], $overrides);
}

it('UC1-TC1 - store() function waits for email verification and does not save the account yet', function () {
    $response = $this->post(route('register.store'), validRegistrationData());

    // The student is sent to the verification page
    expect($response->headers->get('Location'))->toContain('/verify/');

    // Nothing is saved in the users table until the code is verified
    $this->assertDatabaseMissing('users', ['email' => 'yentze@helplive.edu.my']);

    Mail::assertSent(VerificationCodeMail::class, fn ($mail) => $mail->hasTo('yentze@helplive.edu.my') && $mail->purpose === CodePurpose::Register);
});

it('UC1-TC2 - store() function sends the verification email to the lowercase address', function () {
    $this->post(route('register.store'), validRegistrationData(['email' => 'yEntze@Helplive.edu.my']))
        ->assertSessionHasNoErrors();

    Mail::assertSent(VerificationCodeMail::class, fn ($mail) => $mail->hasTo('yentze@helplive.edu.my'));
});

it('UC1-TC3 - store() function saves the password as a hash and not as plain text', function () {
    $response = $this->post(route('register.store'), validRegistrationData());

    // The token is the last part of the verification link
    $token = basename(parse_url($response->headers->get('Location'), PHP_URL_PATH));
    $code = Mail::sent(VerificationCodeMail::class)->last()->code;

    $this->post(route('verification.store', $token), ['code' => $code]);

    $user = User::where('email', 'yentze@helplive.edu.my')->firstOrFail();

    expect($user->password)->not->toBe('Password123')
        ->and($user->password)->toStartWith('$2y$')
        ->and(Hash::check('Password123', $user->password))->toBeTrue();
});

it('UC1-TC4 - store() function rejects the form when every field is empty', function () {
    $this->post(route('register.store'), [])
        ->assertSessionHasErrors(['student_id' => 'Enter valid Student ID', 'name', 'email', 'password']);

    $this->assertDatabaseCount('users', 0);
    Mail::assertNothingSent();
});

it('UC1-TC5 - store() function rejects an email with an incorrect format', function () {
    $this->post(route('register.store'), validRegistrationData(['email' => 'yentzehelplive.edu.my']))
        ->assertSessionHasErrors(['email' => 'The email field must be a valid email address.']);

    Mail::assertNothingSent();
});

it('UC1-TC6 - store() function rejects a student ID and email that are already registered', function () {
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

it('UC1-TC7 - store() function rejects a student ID that is not B followed by 7 digits', function () {
    $this->post(route('register.store'), validRegistrationData([
        'student_id' => 'W1234s56',
        'name'       => 'Bob',
        'email'      => 'abc@helplive.edu.my',
    ]))->assertSessionHasErrors(['student_id' => 'Enter valid Student ID']);

    Mail::assertNothingSent();
});

it('UC1-TC8 - store() function rejects a password shorter than 8 characters', function () {
    $this->post(route('register.store'), validRegistrationData([
        'student_id'            => 'B1234567',
        'name'                  => 'Bob',
        'email'                 => 'abc@helplive.edu.my',
        'password'              => 'Pass12',
        'password_confirmation' => 'Pass12',
    ]))->assertSessionHasErrors(['password' => 'The password field must be at least 8 characters.']);

    Mail::assertNothingSent();
});

it('UC1-TC9 - store() function rejects a password that has no number', function () {
    $this->post(route('register.store'), validRegistrationData([
        'student_id'            => 'B1234567',
        'name'                  => 'Bob',
        'email'                 => 'abc@helplive.edu.my',
        'password'              => 'PasswordOnly',
        'password_confirmation' => 'PasswordOnly',
    ]))->assertSessionHasErrors(['password' => 'The password field must contain at least one number.']);

    Mail::assertNothingSent();
});

it('UC1-TC10 - store() function rejects a password that has no letter', function () {
    $this->post(route('register.store'), validRegistrationData([
        'student_id'            => 'B1234567',
        'name'                  => 'Bob',
        'email'                 => 'abc@helplive.edu.my',
        'password'              => '12345678',
        'password_confirmation' => '12345678',
    ]))->assertSessionHasErrors(['password' => 'The password field must contain at least one letter.']);

    Mail::assertNothingSent();
});

it('UC1-TC11 - store() function rejects two different passwords', function () {
    $this->post(route('register.store'), validRegistrationData([
        'student_id'            => 'B1234567',
        'name'                  => 'Bob',
        'email'                 => 'abc@helplive.edu.my',
        'password'              => 'Password123',
        'password_confirmation' => 'Password321',
    ]))->assertSessionHasErrors(['password' => 'The password field confirmation does not match.']);

    Mail::assertNothingSent();
});

it('UC1-TC58 - store() function rejects an email that does not end with @helplive.edu.my', function () {
    $this->post(route('register.store'), validRegistrationData([
        'student_id' => 'B1234567',
        'name'       => 'Bob',
        'email'      => 'bob@gmail.com',
    ]))->assertSessionHasErrors(['email' => 'Please use your @helplive.edu.my email address.']);

    Mail::assertNothingSent();
});
