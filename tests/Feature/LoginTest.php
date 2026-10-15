<?php

// Use Case 1 - Login (test sheet TC12 to TC15)

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

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

it('TC12 - store() function logs the student in and redirects to the dashboard', function () {
    $student = loginTestStudent();

    $this->post(route('login.store'), [
        'email'    => 'yentze@helplive.edu.my',
        'password' => 'Password123',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($student);
});

it('TC13 - store() function rejects a registered email with a wrong password', function () {
    loginTestStudent(['email' => 'abc123@helplive.edu.my']);

    $this->post(route('login.store'), [
        'email'    => 'abc123@helplive.edu.my',
        'password' => 'WrongPass999',
    ])->assertSessionHasErrors(['email' => 'The email or password is incorrect.']);

    $this->assertGuest();
});

it('TC14 - store() function rejects an email that has no account', function () {
    $this->post(route('login.store'), [
        'email'    => 'random@helplive.edu.my',
        'password' => 'Password123',
    ])->assertSessionHasErrors(['email' => 'The email or password is incorrect.']);

    $this->assertGuest();
});

it('TC15 - store() function rejects the form when the email and password are empty', function () {
    $this->post(route('login.store'), [])
        ->assertSessionHasErrors(['email', 'password']);

    $this->assertGuest();
});