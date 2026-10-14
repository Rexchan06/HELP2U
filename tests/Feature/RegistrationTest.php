<?php

// Use Case 1 - Registration (test sheet TC1 to TC11)

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

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

it('TC1 - store() function creates an account when the student registers with valid details', function () {
    $response = $this->post(route('register.store'), validRegistrationData());

    $response->assertRedirect(route('login'));
    expect(session('status') ?? session('success'))->toBe('Account created. You can now log in.');

    $this->assertDatabaseHas('users', [
        'student_id' => 'B2501146',
        'name'       => 'Tan Yen Tze',
        'email'      => 'yentze@helplive.edu.my',
    ]);
});

it('TC2 - store() function saves the email in lowercase', function () {
    $this->post(route('register.store'), validRegistrationData([
        'email' => 'yEntze@Helplive.edu.my',
    ]))->assertRedirect(route('login'));

    $this->assertDatabaseHas('users', ['email' => 'yentze@helplive.edu.my']);
});

it('TC3 - store() function saves the password as a hash and not as plain text', function () {
    $this->post(route('register.store'), validRegistrationData());

    $user = User::where('email', 'yentze@helplive.edu.my')->firstOrFail();

    expect($user->password)->not->toBe('Password123')
        ->and($user->password)->toStartWith('$2y$')
        ->and(Hash::check('Password123', $user->password))->toBeTrue();
});

it('TC4 - store() function rejects the form when every field is empty', function () {
    $this->post(route('register.store'), [])
        ->assertSessionHasErrors(['student_id', 'name', 'email', 'password']);

    $this->assertDatabaseCount('users', 0);
});

it('TC5 - store() function rejects an email with an incorrect format', function () {
    $this->post(route('register.store'), validRegistrationData([
        'email' => 'yentzehelplive.edu.my',
    ]))->assertSessionHasErrors(['email' => 'The email field must be a valid email address.']);

    $this->assertDatabaseCount('users', 0);
});

it('TC6 - store() function rejects a student ID and email that are already registered', function () {
    User::factory()->create([
        'student_id' => 'B2501146',
        'email'      => 'yentze@helplive.edu.my',
    ]);

    $this->post(route('register.store'), validRegistrationData([
        'name' => 'Bob',
    ]))->assertSessionHasErrors([
        'student_id' => 'This student ID is already registered.',
        'email'      => 'An account already exists for this email.',
    ]);

    $this->assertDatabaseCount('users', 1);
});

it('TC7 - store() function rejects a student ID that is not B followed by 7 digits', function () {
    $this->post(route('register.store'), validRegistrationData([
        'student_id' => 'W1234s56',
        'name'       => 'Bob',
        'email'      => 'abc@helplive.edu.my',
    ]))->assertSessionHasErrors(['student_id' => 'Enter valid Student ID']);

    $this->assertDatabaseMissing('users', ['email' => 'abc@helplive.edu.my']);
});

it('TC8 - store() function rejects a password shorter than 8 characters', function () {
    $this->post(route('register.store'), validRegistrationData([
        'student_id'            => 'B1234567',
        'name'                  => 'Bob',
        'email'                 => 'abc@helplive.edu.my',
        'password'              => 'Pass12',
        'password_confirmation' => 'Pass12',
    ]))->assertSessionHasErrors(['password' => 'The password field must be at least 8 characters.']);

    $this->assertDatabaseCount('users', 0);
});

it('TC9 - store() function rejects a password that has no number', function () {
    $this->post(route('register.store'), validRegistrationData([
        'student_id'            => 'B1234567',
        'name'                  => 'Bob',
        'email'                 => 'abc@helplive.edu.my',
        'password'              => 'PasswordOnly',
        'password_confirmation' => 'PasswordOnly',
    ]))->assertSessionHasErrors(['password' => 'The password field must contain at least one number.']);

    $this->assertDatabaseCount('users', 0);
});

it('TC10 - store() function rejects a password that has no letter', function () {
    $this->post(route('register.store'), validRegistrationData([
        'student_id'            => 'B1234567',
        'name'                  => 'Bob',
        'email'                 => 'abc@helplive.edu.my',
        'password'              => '12345678',
        'password_confirmation' => '12345678',
    ]))->assertSessionHasErrors(['password' => 'The password field must contain at least one letter.']);

    $this->assertDatabaseCount('users', 0);
});

it('TC11 - store() function rejects two different passwords', function () {
    $this->post(route('register.store'), validRegistrationData([
        'student_id'            => 'B1234567',
        'name'                  => 'Bob',
        'email'                 => 'abc@helplive.edu.my',
        'password'              => 'Password123',
        'password_confirmation' => 'Password321',
    ]))->assertSessionHasErrors(['password' => 'The password field confirmation does not match.']);

    $this->assertDatabaseCount('users', 0);
});