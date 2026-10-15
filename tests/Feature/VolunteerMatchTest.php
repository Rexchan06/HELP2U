<?php

use App\Enums\MatchStatus;
use App\Enums\RequestStatus;
use App\Enums\SupportMode;
use App\Models\Category;
use App\Models\SupportRequest;
use App\Models\User;
use App\Models\VolunteerProfile;
use App\Notifications\MatchRequested;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function newSupportRequest(User $student): SupportRequest
{
    return SupportRequest::create([
        'user_id' => $student->id,
        'category_id' => Category::factory()->create()->id,
        'description' => 'I need help understanding SQL joins.',
        'support_mode' => SupportMode::F2F,
    ]);
}

it('sends the request to the chosen volunteer and notifies them', function () {
    Notification::fake();

    $student = User::factory()->create();
    $supportRequest = newSupportRequest($student);
    $volunteer = VolunteerProfile::factory()->create();

    $this->actingAs($student)
        ->post(route('volunteer-matches.store', $volunteer), ['support_request' => $supportRequest->id])
        ->assertRedirect(route('support-requests.index'));

    $match = $supportRequest->volunteerMatches()->sole();

    expect($match->status)->toBe(MatchStatus::Pending)
        ->and($match->expires_at->isFuture())->toBeTrue()
        ->and(now()->diffInHours($match->expires_at))->toBeGreaterThanOrEqual(47)
        ->and($supportRequest->refresh()->status)->toBe(RequestStatus::Pending);

    Notification::assertSentTo($volunteer->user, MatchRequested::class);
});

it('blocks sending while another match is still active', function () {
    $student = User::factory()->create();
    $supportRequest = newSupportRequest($student);
    $first = VolunteerProfile::factory()->create();
    $second = VolunteerProfile::factory()->create();

    $supportRequest->volunteerMatches()->create([
        'volunteer_profile_id' => $first->id,
        'status' => MatchStatus::Pending,
        'expires_at' => now()->addDay(),
    ]);

    $this->actingAs($student)
        ->post(route('volunteer-matches.store', $second), ['support_request' => $supportRequest->id])
        ->assertSessionHas('failed');

    expect($supportRequest->volunteerMatches()->count())->toBe(1);
});

it('allows re-sending after a rejection', function () {
    $student = User::factory()->create();
    $supportRequest = newSupportRequest($student);
    $volunteer = VolunteerProfile::factory()->create();

    $supportRequest->volunteerMatches()->create([
        'volunteer_profile_id' => $volunteer->id,
        'status' => MatchStatus::Rejected,
        'expires_at' => now()->subDay(),
        'responded_at' => now()->subDay(),
    ]);

    $this->actingAs($student)
        ->post(route('volunteer-matches.store', $volunteer), ['support_request' => $supportRequest->id])
        ->assertRedirect();

    $match = $supportRequest->volunteerMatches()->sole();

    expect($match->status)->toBe(MatchStatus::Pending)
        ->and($match->responded_at)->toBeNull();
});

it('forbids sending a request that belongs to another student', function () {
    $supportRequest = newSupportRequest(User::factory()->create());
    $volunteer = VolunteerProfile::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('volunteer-matches.store', $volunteer), ['support_request' => $supportRequest->id])
        ->assertForbidden();
});

it('returns 404 when sending to an inactive volunteer', function () {
    $student = User::factory()->create();
    $supportRequest = newSupportRequest($student);
    $volunteer = VolunteerProfile::factory()->create(['is_active' => false]);

    $this->actingAs($student)
        ->post(route('volunteer-matches.store', $volunteer), ['support_request' => $supportRequest->id])
        ->assertNotFound();
});

it('shows the send request form on the profile page in request context', function () {
    $student = User::factory()->create();
    $supportRequest = newSupportRequest($student);
    $volunteer = VolunteerProfile::factory()->create();

    $this->actingAs($student)
        ->get(route('volunteers.show', ['volunteer' => $volunteer->id, 'support_request' => $supportRequest->id]))
        ->assertOk()
        ->assertSee('Send Request');
});

it('shows a request sent state once the volunteer was chosen', function () {
    $student = User::factory()->create();
    $supportRequest = newSupportRequest($student);
    $volunteer = VolunteerProfile::factory()->create();

    $supportRequest->volunteerMatches()->create([
        'volunteer_profile_id' => $volunteer->id,
        'status' => MatchStatus::Pending,
        'expires_at' => now()->addDay(),
    ]);

    $this->actingAs($student)
        ->get(route('volunteers.show', ['volunteer' => $volunteer->id, 'support_request' => $supportRequest->id]))
        ->assertOk()
        ->assertSee('Request Sent');
});

it('redirects guests to the login page', function () {
    $supportRequest = newSupportRequest(User::factory()->create());
    $volunteer = VolunteerProfile::factory()->create();

    $this->post(route('volunteer-matches.store', $volunteer), ['support_request' => $supportRequest->id])
        ->assertRedirect(route('login'));

    expect($supportRequest->volunteerMatches()->count())->toBe(0);
});

it('blocks students from sending a request to themselves', function () {
    $student = User::factory()->create();
    $supportRequest = newSupportRequest($student);
    $volunteer = VolunteerProfile::factory()->create(['user_id' => $student->id]);

    $this->actingAs($student)
        ->post(route('volunteer-matches.store', $volunteer), ['support_request' => $supportRequest->id])
        ->assertSessionHas('failed', 'You cannot send a request to yourself.');

    expect($supportRequest->volunteerMatches()->count())->toBe(0);
});

it('requires a support request to be chosen', function () {
    $student = User::factory()->create();
    $volunteer = VolunteerProfile::factory()->create();

    $this->actingAs($student)
        ->post(route('volunteer-matches.store', $volunteer))
        ->assertSessionHasErrors('support_request');

    expect($volunteer->volunteerMatches()->count())->toBe(0);
});

it('rejects a support request id that does not exist', function () {
    $student = User::factory()->create();
    $volunteer = VolunteerProfile::factory()->create();

    $this->actingAs($student)
        ->post(route('volunteer-matches.store', $volunteer), ['support_request' => 9999])
        ->assertSessionHasErrors('support_request');

    expect($volunteer->volunteerMatches()->count())->toBe(0);
});

it('rolls back the match and request updates when a database error occurs', function () {
    $student = User::factory()->create();
    $supportRequest = newSupportRequest($student);
    $volunteer = VolunteerProfile::factory()->create();

    DB::beforeExecuting(function (string $query) {
        if (str_starts_with($query, 'update "support_requests"')) {
            throw new RuntimeException('Simulated database failure');
        }
    });

    $this->actingAs($student)
        ->post(route('volunteer-matches.store', $volunteer), ['support_request' => $supportRequest->id])
        ->assertStatus(500);

    expect($supportRequest->volunteerMatches()->count())->toBe(0)
        ->and($supportRequest->refresh()->status)->toBe(RequestStatus::Open);
});
