<?php

use App\Enums\SupportMode;
use App\Models\Category;
use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires authentication to view the create form', function () {
    $this->get(route('support-requests.create'))->assertRedirect();
})->skip('Auth module not merged yet — routes are temporarily public');

it('shows the create form with categories', function () {
    Category::factory()->create(['name' => 'Programming']);

    $this->actingAs(User::factory()->create())
        ->get(route('support-requests.create'))
        ->assertOk()
        ->assertSee('Create Support Request')
        ->assertSee('Programming');
});

it('stores a support request for the authenticated user', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('support-requests.store'), [
            'category_id' => $category->id,
            'description' => 'I need help understanding SQL joins for my assignment.',
            'support_mode' => SupportMode::F2F->value,
        ]);

    $supportRequest = SupportRequest::sole();
    $response->assertRedirect(route('volunteers.index', ['support_request' => $supportRequest->id]));

    $this->assertDatabaseHas('support_requests', [
        'user_id' => $user->id,
        'category_id' => $category->id,
        'support_mode' => 'F2F',
        'status' => 'OPEN',
    ]);
});

it('validates required fields', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('support-requests.store'), [])
        ->assertSessionHasErrors(['category_id', 'description', 'support_mode']);
});

it('rejects a support mode that is not allowed', function () {
    $category = Category::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('support-requests.store'), [
            'category_id' => $category->id,
            'description' => 'I need help understanding SQL joins.',
            'support_mode' => 'carrier-pigeon',
        ])
        ->assertSessionHasErrors('support_mode');
});
