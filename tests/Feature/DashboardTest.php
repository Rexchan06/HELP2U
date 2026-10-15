<?php

// Use Case 1 - Dashboard (test sheet TC16)

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Pages use @vite, so skip the asset build while testing
    $this->withoutVite();
});

it("TC16 - index() function shows the logged-in student's details on the dashboard", function () {
    $student = User::factory()->create([
        'student_id' => 'B2501146',
        'name'       => 'Tan Yen Tze',
        'email'      => 'yentze@helplive.edu.my',
        'password'   => 'Password123',
    ]);

    $this->actingAs($student)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Tan Yen Tze')
        ->assertSee('B2501146')
        ->assertSee('yentze@helplive.edu.my');
});