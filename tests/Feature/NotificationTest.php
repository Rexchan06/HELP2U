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

uses(RefreshDatabase::class);

function notifiedMatch(): array
{
    $student = User::factory()->create();
    $volunteer = VolunteerProfile::factory()->create();
    $supportRequest = SupportRequest::create([
        'user_id' => $student->id,
        'category_id' => Category::factory()->create()->id,
        'description' => 'I need help understanding SQL joins.',
        'support_mode' => SupportMode::F2F,
        'status' => RequestStatus::Pending,
    ]);
    $match = VolunteerMatch::create([
        'support_request_id' => $supportRequest->id,
        'volunteer_profile_id' => $volunteer->id,
        'status' => MatchStatus::Pending,
        'expires_at' => now()->addHours(48),
    ]);

    return [$student, $volunteer, $match];
}

it('TC33 - notifications page redirects guests to login', function () {
    $this->get(route('notifications.index'))->assertRedirect(route('login'));
});

it('TC34 - notifications page lists the signed-in user notifications', function () {
    [$student, $volunteer, $match] = notifiedMatch();
    $volunteer->user->notify(new MatchRequested($match->supportRequest));

    $this->actingAs($volunteer->user)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertSee('sent you a support request')
        ->assertSee($match->supportRequest->category->name);
});

it('TC35 - the navigation bell shows the unread notification count', function () {
    [$student, $volunteer, $match] = notifiedMatch();
    $volunteer->user->notify(new MatchRequested($match->supportRequest));

    expect($volunteer->user->unreadNotifications()->count())->toBe(1);
});

it('TC36 - read() marks a notification as read and sends the volunteer to the match detail', function () {
    [$student, $volunteer, $match] = notifiedMatch();
    $volunteer->user->notify(new MatchRequested($match->supportRequest));
    $notification = $volunteer->user->notifications()->sole();

    $this->actingAs($volunteer->user)
        ->post(route('notifications.read', $notification->id))
        ->assertRedirect(route('volunteer-matches.show', $match));

    expect($notification->refresh()->read_at)->not->toBeNull();
});

it('TC37 - read() sends the student to their requests for a status-change notification', function () {
    [$student, $volunteer, $match] = notifiedMatch();
    $student->notify(new MatchResponded($match));
    $notification = $student->notifications()->sole();

    $this->actingAs($student)
        ->post(route('notifications.read', $notification->id))
        ->assertRedirect(route('support-requests.index'));

    expect($notification->refresh()->read_at)->not->toBeNull();
});

it('TC38 - read() sends the student to the scheduling page for an accepted notification', function () {
    [$student, $volunteer, $match] = notifiedMatch();
    $match->update(['status' => MatchStatus::Accepted, 'responded_at' => now()]);
    $student->notify(new MatchResponded($match));
    $notification = $student->notifications()->sole();

    $this->actingAs($student)
        ->post(route('notifications.read', $notification->id))
        ->assertRedirect(route('support-sessions.create', $match->support_request_id));
});

it('TC39 - read() sends the student to the session detail when it is already scheduled', function () {
    [$student, $volunteer, $match] = notifiedMatch();
    $match->update(['status' => MatchStatus::Accepted, 'responded_at' => now()]);
    $session = $match->supportSession()->create([
        'scheduled_start' => now()->addDay(),
        'status' => \App\Enums\SessionStatus::Scheduled,
    ]);
    $student->notify(new MatchResponded($match));
    $notification = $student->notifications()->sole();

    $this->actingAs($student)
        ->post(route('notifications.read', $notification->id))
        ->assertRedirect(route('support-sessions.show', $session));
});

it('TC40 - the login popup shows the latest notifications', function () {
    [$student, $volunteer, $match] = notifiedMatch();
    $volunteer->user->notify(new MatchRequested($match->supportRequest));
    $volunteer->user->notifications()->sole()->markAsRead();
    $volunteer->user->notify(new MatchRequested($match->supportRequest->refresh()));

    $this->actingAs($volunteer->user)
        ->withSession(['show_unread_popup' => true])
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('notification-popup')
        ->assertSee('sent you a support request');

    expect($volunteer->user->unreadNotifications()->count())->toBe(1);
});

it('TC41 - readAll() marks every notification as read', function () {
    [$student, $volunteer, $match] = notifiedMatch();
    $volunteer->user->notify(new MatchRequested($match->supportRequest));
    $student->notify(new MatchResponded($match));

    $this->actingAs($student)
        ->post(route('notifications.read-all'))
        ->assertRedirect(route('notifications.index'));

    expect($student->unreadNotifications()->count())->toBe(0);
});

it('TC42 - read() prevents reading another user notification', function () {
    [$student, $volunteer, $match] = notifiedMatch();
    $volunteer->user->notify(new MatchRequested($match->supportRequest));
    $notification = $volunteer->user->notifications()->sole();

    $this->actingAs($student)
        ->post(route('notifications.read', $notification->id))
        ->assertNotFound();
});
