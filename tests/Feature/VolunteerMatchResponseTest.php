<?php

use App\Enums\MatchStatus;
use App\Enums\RequestStatus;
use App\Enums\SupportMode;
use App\Models\Category;
use App\Models\SupportRequest;
use App\Models\User;
use App\Models\VolunteerMatch;
use App\Models\VolunteerProfile;
use App\Notifications\MatchRequested;
use App\Notifications\MatchResponded;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function incomingRequest(VolunteerProfile $volunteer, ?User $student = null): VolunteerMatch
{
    $supportRequest = SupportRequest::create([
        'user_id' => ($student ?? User::factory()->create())->id,
        'category_id' => Category::factory()->create()->id,
        'description' => 'I need help understanding SQL joins.',
        'support_mode' => SupportMode::F2F,
        'status' => RequestStatus::Pending,
    ]);

    return VolunteerMatch::create([
        'support_request_id' => $supportRequest->id,
        'volunteer_profile_id' => $volunteer->id,
        'status' => MatchStatus::Pending,
        'expires_at' => now()->addHours(48),
    ]);
}

it('lists only the requests addressed to the logged-in volunteer', function () {
    $mine = VolunteerProfile::factory()->create();
    $other = VolunteerProfile::factory()->create();
    incomingRequest($mine, User::factory()->create(['name' => 'AAA Student One']));
    incomingRequest($other, User::factory()->create(['name' => 'ZZZ Student Two']));

    $this->actingAs($mine->user)
        ->get(route('volunteer-matches.index'))
        ->assertOk()
        ->assertSee('AAA Student One')
        ->assertDontSee('ZZZ Student Two');
});

it('sends users without a volunteer profile to the profile setup', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('volunteer-matches.index'))
        ->assertRedirect(route('volunteer.profile'));
});

it('redirects guests from the inbox to login', function () {
    $this->get(route('volunteer-matches.index'))->assertRedirect(route('login'));
});

it('shows the full request detail and marks the notification as read', function () {
    $volunteer = VolunteerProfile::factory()->create();
    $match = incomingRequest($volunteer);

    $volunteer->user->notify(new MatchRequested($match->supportRequest));

    $this->actingAs($volunteer->user)
        ->get(route('volunteer-matches.show', $match))
        ->assertOk()
        ->assertSee($match->supportRequest->user->name)
        ->assertSee($match->supportRequest->category->name)
        ->assertSee($match->supportRequest->description)
        ->assertSee('Accept Request')
        ->assertSee('Decline');

    expect($volunteer->user->unreadNotifications()->count())->toBe(0);
});

it('forbids viewing a match addressed to another volunteer', function () {
    $match = incomingRequest(VolunteerProfile::factory()->create());

    $this->actingAs(User::factory()->create())
        ->get(route('volunteer-matches.show', $match))
        ->assertForbidden();
});

it('accepts a pending request and informs the student', function () {
    Notification::fake();

    $volunteer = VolunteerProfile::factory()->create();
    $student = User::factory()->create();
    $match = incomingRequest($volunteer, $student);

    $this->actingAs($volunteer->user)
        ->post(route('volunteer-matches.respond', $match), ['decision' => 'accept'])
        ->assertRedirect(route('support-requests.index'));

    expect($match->refresh()->status)->toBe(MatchStatus::Accepted)
        ->and($match->responded_at)->not->toBeNull()
        ->and($match->supportRequest->refresh()->status)->toBe(RequestStatus::Matched);

    Notification::assertSentTo($student, MatchResponded::class);
});

it('rejects a pending request and re-opens it for other volunteers', function () {
    Notification::fake();

    $volunteer = VolunteerProfile::factory()->create();
    $student = User::factory()->create();
    $match = incomingRequest($volunteer, $student);

    $this->actingAs($volunteer->user)
        ->post(route('volunteer-matches.respond', $match), ['decision' => 'reject'])
        ->assertRedirect(route('support-requests.index'));

    expect($match->refresh()->status)->toBe(MatchStatus::Rejected)
        ->and($match->supportRequest->refresh()->status)->toBe(RequestStatus::Open);

    Notification::assertSentTo($student, MatchResponded::class);
});

it('blocks responding to an already answered request', function () {
    $volunteer = VolunteerProfile::factory()->create();
    $match = incomingRequest($volunteer);
    $match->update(['status' => MatchStatus::Rejected, 'responded_at' => now()]);

    $this->actingAs($volunteer->user)
        ->post(route('volunteer-matches.respond', $match), ['decision' => 'accept'])
        ->assertSessionHas('failed');

    expect($match->refresh()->status)->toBe(MatchStatus::Rejected);
});

it('blocks responding to an expired request', function () {
    $volunteer = VolunteerProfile::factory()->create();
    $match = incomingRequest($volunteer);
    $match->update(['expires_at' => now()->subHour()]);

    $this->actingAs($volunteer->user)
        ->post(route('volunteer-matches.respond', $match), ['decision' => 'accept'])
        ->assertSessionHas('failed');

    expect($match->refresh()->status)->toBe(MatchStatus::Pending);
});

it('forbids responding to a match addressed to another volunteer', function () {
    $match = incomingRequest(VolunteerProfile::factory()->create());

    $this->actingAs(User::factory()->create())
        ->post(route('volunteer-matches.respond', $match), ['decision' => 'accept'])
        ->assertForbidden();
});

it('rejects an unknown decision value', function () {
    $volunteer = VolunteerProfile::factory()->create();
    $match = incomingRequest($volunteer);

    $this->actingAs($volunteer->user)
        ->post(route('volunteer-matches.respond', $match), ['decision' => 'maybe'])
        ->assertSessionHasErrors('decision');
});
