<?php

// Use Case 1 - Edit Profile (test sheet TC17 to TC19)

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

/** A logged-in student with no bio yet. */
function profileTestStudent(): User
{
    return User::factory()->create([
        'student_id' => 'B2501146',
        'name'       => 'Tan Yen Tze',
        'email'      => 'yentze@helplive.edu.my',
        'password'   => 'Password123',
        'bio'        => null,
    ]);
}

it('TC17 - update() function saves a new name and bio and redirects to the dashboard', function () {
    $student = profileTestStudent();
    $bio = 'I am a third-year computer science student interested in helping first-years with mathematics and Python.';

    $this->actingAs($student)
        ->put(route('profile.update'), ['name' => 'Bob', 'bio' => $bio])
        ->assertRedirect(route('dashboard'));

    expect(session('status') ?? session('success'))->toBe('Your profile has been updated.');

    $this->assertDatabaseHas('users', [
        'id'   => $student->id,
        'name' => 'Bob',
        'bio'  => $bio,
    ]);
});

it('TC18 - update() function rejects an empty full name', function () {
    $student = profileTestStudent();

    $this->actingAs($student)
        ->put(route('profile.update'), ['name' => '', 'bio' => 'Hello World'])
        ->assertSessionHasErrors(['name' => 'The name field is required.']);

    $this->assertDatabaseHas('users', ['id' => $student->id, 'name' => 'Tan Yen Tze']);
});

it('TC19 - update() function rejects a bio longer than 200 characters', function () {
    $student = profileTestStudent();

    $this->actingAs($student)
        ->put(route('profile.update'), ['name' => 'Tan Yen Tze', 'bio' => str_repeat('A', 201)])
        ->assertSessionHasErrors(['bio' => 'The bio field must not be greater than 200 characters.']);

    $this->assertDatabaseHas('users', ['id' => $student->id, 'bio' => null]);
});

it('TC19 - update() function accepts a bio of exactly 200 characters', function () {
    $student = profileTestStudent();

    $this->actingAs($student)
        ->put(route('profile.update'), ['name' => 'Tan Yen Tze', 'bio' => str_repeat('A', 200)])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard'));
});