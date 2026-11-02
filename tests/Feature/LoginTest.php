<?php

// Use Case 1 - Login (test sheet TC12 to TC15 and TC29)

use App\Enums\CodePurpose;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

/** A registered student whose password is Password123. */
function loginTestStudent(array $overrides = []): User
{
    return User::factory()->create(array_merge([
        'student_id' => 'B2501146',
        'name'       => 'Tan Yen Tze',
        'email'      => 'yentze@helplive.edu.my',
        'password'   => 'Password123',
    ], $overrides));
}

it('UC1-TC12 - store() function emails a login code after the correct email and password', function () {
    Mail::fake();
    loginTestStudent();

    $this->post(route('login.store'), [
        'email'    => 'yentze@helplive.edu.my',
        'password' => 'Password123',
    ])->assertRedirect(route('login.2fa'));

    $this->assertGuest();
    Mail::assertSent(VerificationCodeMail::class, fn ($mail) => $mail->purpose === CodePurpose::Login);
});

it('UC1-TC13 - store() function rejects a registered email with a wrong password', function () {
    Mail::fake();
    loginTestStudent(['email' => 'abc123@helplive.edu.my']);

    $this->post(route('login.store'), [
        'email'    => 'abc123@helplive.edu.my',
        'password' => 'WrongPass999',
    ])->assertSessionHasErrors(['email' => 'The email or password is incorrect.']);

    $this->assertGuest();
    Mail::assertNothingSent();
});

it('UC1-TC14 - store() function rejects an email that has no account', function () {
    Mail::fake();

    $this->post(route('login.store'), [
        'email'    => 'random@helplive.edu.my',
        'password' => 'Password123',
    ])->assertSessionHasErrors(['email' => 'The email or password is incorrect.']);

    $this->assertGuest();
    Mail::assertNothingSent();
});

it('UC1-TC15 - store() function rejects the form when the email and password are empty', function () {
    $this->post(route('login.store'), [])
        ->assertSessionHasErrors(['email', 'password']);

    $this->assertGuest();
});

it('UC1-TC29 - store() function locks the login for 20 seconds after 5 wrong passwords', function () {
    Mail::fake();
    loginTestStudent();

    $wrong   = ['email' => 'yentze@helplive.edu.my', 'password' => 'WrongPass999'];
    $correct = ['email' => 'yentze@helplive.edu.my', 'password' => 'Password123'];

    // Wrong passwords 1 to 4 show the normal error
    foreach (range(1, 4) as $attempt) {
        $this->post(route('login.store'), $wrong)
            ->assertSessionHasErrors(['email' => 'The email or password is incorrect.']);
    }

    // The 5th wrong password starts the 20 second lock
    $this->post(route('login.store'), $wrong)
        ->assertSessionHasErrors(['email' => 'Too many login attempts. Please try again in 20 seconds.']);

    // While locked, even the correct password is refused and no code is sent
    $this->post(route('login.store'), $correct)->assertSessionHasErrors('email');

    expect(session('errors')->first('email'))->toContain('Too many login attempts');
    $this->assertGuest();
    Mail::assertNothingSent();

    // After the lock ends, the correct password works again
    $this->travel(21)->seconds();

    $this->post(route('login.store'), $correct)->assertRedirect(route('login.2fa'));

    Mail::assertSent(VerificationCodeMail::class, fn ($mail) => $mail->purpose === CodePurpose::Login);
});
