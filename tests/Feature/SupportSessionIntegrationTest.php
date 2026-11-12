<?php

use App\Enums\MatchStatus;
use App\Enums\RequestStatus;
use App\Models\Category;
use App\Models\SupportRequest;
use App\Models\SupportSession;
use App\Models\User;
use App\Models\VolunteerMatch;
use App\Models\VolunteerProfile;
use App\Mail\VerificationCodeMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Helpers
function integVolunteer(): array
{
    $user = User::factory()->create();
    $profile = VolunteerProfile::factory()->create([
        'user_id' => $user->id,
        'supports_f2f' => true,
        'is_active' => true,
    ]);

    foreach (range(0, 6) as $day) {
        $profile->availabilities()->create([
            'day_of_week' => $day,
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);
    }

    return [$user, $profile];
}

function integAcceptedRequest(User $student, VolunteerProfile $profile): SupportRequest
{
    $request = SupportRequest::create([
        'user_id' => $student->id,
        'category_id' => Category::firstOrCreate(['name' => 'Academic'])->id,
        'description' => 'Need help with Laravel validation',
        'support_mode' => 'F2F',
    ]);

    VolunteerMatch::create([
        'support_request_id' => $request->id,
        'volunteer_profile_id' => $profile->id,
        'status' => MatchStatus::Accepted,
        'expires_at' => now()->addHours(48),
    ]);

    return $request;
}

function integSlot(): string
{
    return now()->addDays(7)->setTime(10, 0)->format('Y-m-d H:i:s');
}

// UC1 (login / logout) with UC3

it('IT1 - a student who logs in through the UC1 login page (with 2FA) can open My Sessions (UC3)', function () {
    Mail::fake();
    $student = User::factory()->create(['email' => 'alex@student.test']);

    $this->post(route('login.store'), [
        'email' => 'alex@student.test',
        'password' => 'password',
    ])->assertRedirect(route('login.2fa'));

    $code = Mail::sent(VerificationCodeMail::class)->last()->code;

    $this->post(route('login.2fa.verify'), ['code' => $code])
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($student);
    $this->get(route('support-sessions.index'))->assertOk();
});


it('IT2 - after logging out (UC1), the UC3 session pages redirect to the login page', function () {
    $student = User::factory()->create();

    $this->actingAs($student)->post(route('logout'))->assertRedirect(route('login'));

    $this->assertGuest();
    $this->get(route('support-sessions.index'))->assertRedirect(route('login'));
});

// UC2 (support requests) with UC3

it('IT3 - a request submitted through the UC2 form cannot be scheduled until a volunteer accepts it', function () {
    $student = User::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs($student)->post(route('support-requests.store'), [
        'category_id' => $category->id,
        'description' => 'I need help with Laravel validation rules',
        'support_mode' => 'F2F',
    ])->assertRedirect();

    $request = SupportRequest::first();
    expect($request->status)->toBe(RequestStatus::Open);

    $this->get(route('support-sessions.create', $request))
        ->assertRedirect()
        ->assertSessionHas('failed', 'This request has no accepted match.');
});

it('IT4 - the UC2 My Requests page shows a "Schedule Session" link to UC3 for an accepted request', function () {
    $student = User::factory()->create();
    [, $profile] = integVolunteer();
    $request = integAcceptedRequest($student, $profile);

    $this->actingAs($student)->get(route('support-requests.index'))
        ->assertOk()
        ->assertSee('Schedule Session')
        ->assertSee(route('support-sessions.create', $request), false);
});

it('IT5 - after scheduling (UC3), the UC2 My Requests page shows the request as Scheduled with a "View Session" link', function () {
    $student = User::factory()->create();
    [, $profile] = integVolunteer();
    $request = integAcceptedRequest($student, $profile);

    $this->actingAs($student)->post(route('support-sessions.store', $request), [
        'scheduled_start' => integSlot(),
        'location' => 'Library',
    ])->assertSessionHasNoErrors();

    $session = SupportSession::first();
    expect($request->fresh()->status)->toBe(RequestStatus::Scheduled);

    $this->get(route('support-requests.index'))
        ->assertOk()
        ->assertSee('Scheduled')
        ->assertSee('View Session')
        ->assertSee(route('support-sessions.show', $session), false)
        ->assertDontSee(route('support-sessions.create', $request), false);
});

// UC1 (volunteer availability) with UC3

it('IT6 - a slot inside the availability saved on the UC1 volunteer profile can be booked in UC3', function () {
    $student = User::factory()->create();
    $volunteer = User::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs($volunteer)->post(route('volunteer.profile.store'), [
        'categories' => [$category->id],
        'support_modes' => ['F2F'],
        'location' => 'Student Lounge',
        'availability' => [['day' => 1, 'start' => '09:00', 'end' => '12:00']],
    ])->assertSessionHasNoErrors();

    $request = integAcceptedRequest($student, $volunteer->fresh()->volunteerProfile);
    $monday10am = now()->next('Monday')->setTime(10, 0)->format('Y-m-d H:i:s');

    $this->actingAs($student)->post(route('support-sessions.store', $request), [
        'scheduled_start' => $monday10am,
        'location' => 'Library',
    ])->assertSessionHasNoErrors();

    expect(SupportSession::count())->toBe(1);
});

it('IT7 - a slot outside the availability saved on the UC1 volunteer profile is rejected in UC3', function () {
    $student = User::factory()->create();
    $volunteer = User::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs($volunteer)->post(route('volunteer.profile.store'), [
        'categories' => [$category->id],
        'support_modes' => ['F2F'],
        'location' => 'Student Lounge',
        'availability' => [['day' => 1, 'start' => '09:00', 'end' => '12:00']],
    ])->assertSessionHasNoErrors();

    $request = integAcceptedRequest($student, $volunteer->fresh()->volunteerProfile);
    $monday2pm = now()->next('Monday')->setTime(14, 0)->format('Y-m-d H:i:s');

    $this->actingAs($student)->post(route('support-sessions.store', $request), [
        'scheduled_start' => $monday2pm,
        'location' => 'Library',
    ])->assertSessionHasErrors(['scheduled_start' => 'Please choose a time within the volunteer\'s availability.']);

    expect(SupportSession::count())->toBe(0);
});

// UC3 with the volunteer dashboard and the UC2 volunteer directory

it('IT8 - a session booked in UC3 appears on the volunteer\'s dashboard and opens from there', function () {
    $student = User::factory()->create(['name' => 'Alex Tan']);
    [$volunteerUser, $profile] = integVolunteer();
    $request = integAcceptedRequest($student, $profile);

    $this->actingAs($student)->post(route('support-sessions.store', $request), [
        'scheduled_start' => integSlot(),
        'location' => 'Library',
    ])->assertSessionHasNoErrors();

    $session = SupportSession::first();

    $this->actingAs($volunteerUser)->get(route('dashboard'))
        ->assertOk()
        ->assertViewHas('upcomingSessions', fn ($list) => $list->pluck('id')->all() === [$session->id])
        ->assertSee(route('support-sessions.show', $session), false);

    $this->get(route('support-sessions.show', $session))->assertOk();
});

it('IT9 - after a session is booked in UC3, the volunteer is shown as busy in the UC2 volunteer directory', function () {
    $student = User::factory()->create();
    [, $profile] = integVolunteer();
    $request = integAcceptedRequest($student, $profile);

    $this->actingAs($student)->get(route('volunteers.index'))
        ->assertViewHas('volunteers', fn ($list) => (int) $list->firstWhere('id', $profile->id)->is_free === 1);

    $this->post(route('support-sessions.store', $request), [
        'scheduled_start' => integSlot(),
        'location' => 'Library',
    ])->assertSessionHasNoErrors();

    $this->get(route('volunteers.index'))
        ->assertViewHas('volunteers', fn ($list) => (int) $list->firstWhere('id', $profile->id)->is_free === 0);
});

// Iteration 2 Integration Tests

function integPendingMatch(User $student, VolunteerProfile $profile): VolunteerMatch
{
    $request = SupportRequest::create([
        'user_id' => $student->id,
        'category_id' => Category::firstOrCreate(['name' => 'Academic'])->id,
        'description' => 'Need help with Laravel validation',
        'support_mode' => 'F2F',
        'status' => RequestStatus::Pending,
    ]);

    return VolunteerMatch::create([
        'support_request_id' => $request->id,
        'volunteer_profile_id' => $profile->id,
        'status' => MatchStatus::Pending,
        'expires_at' => now()->addHours(48),
    ]);
}

it('IT10 - after the volunteer accepts in UC2, the student is notified and the notification opens the UC3 schedule form', function () {
    $student = User::factory()->create();
    [$volunteerUser, $profile] = integVolunteer();
    $match = integPendingMatch($student, $profile);
    $request = $match->supportRequest;

    $this->actingAs($student)->get(route('support-sessions.create', $request))
        ->assertSessionHas('failed', 'This request has no accepted match.');

    $this->actingAs($volunteerUser)->post(route('volunteer-matches.respond', $match), ['decision' => 'accept'])
        ->assertRedirect(route('support-requests.index'));

    expect($request->fresh()->status)->toBe(RequestStatus::Matched);

    $notification = $student->notifications()->first();
    expect($notification->data['decision'])->toBe('accepted');

    $this->actingAs($student)->post(route('notifications.read', $notification->id))
        ->assertRedirect(route('support-sessions.create', $request));

    $this->get(route('support-sessions.create', $request))->assertOk();
});

it('IT11 - a request the volunteer does not answer within 48 hours (UC2) cannot be scheduled in UC3 and is open again', function () {
    $student = User::factory()->create();
    [, $profile] = integVolunteer();
    $match = integPendingMatch($student, $profile);
    $request = $match->supportRequest;

    $this->travel(49)->hours();
    $this->artisan('matches:expire')->assertSuccessful();

    expect($match->fresh()->status)->toBe(MatchStatus::Expired)
        ->and($request->fresh()->status)->toBe(RequestStatus::Open)
        ->and($student->notifications()->first()->data['decision'])->toBe('expired');

    $this->actingAs($student)->get(route('support-sessions.create', $request))
        ->assertRedirect()
        ->assertSessionHas('failed', 'This request has no accepted match.');

    $this->get(route('support-requests.index'))
        ->assertOk()
        ->assertSee('Find Volunteer');
});

it('IT12 - cancelling a session in UC3 closes the request on the UC2 My Requests page and notifies the volunteer', function () {
    $student = User::factory()->create(['name' => 'Alex Tan']);
    [$volunteerUser, $profile] = integVolunteer();
    $request = integAcceptedRequest($student, $profile);

    $this->actingAs($student)->post(route('support-sessions.store', $request), [
        'scheduled_start' => integSlot(),
        'location' => 'Library',
    ])->assertSessionHasNoErrors();

    $session = SupportSession::first();

    $this->patch(route('support-sessions.cancel', $session))
        ->assertSessionHas('success', 'Session cancelled. The other participant has been notified.');

    expect($request->fresh()->status)->toBe(RequestStatus::Closed);

    $this->get(route('support-requests.index'))
        ->assertOk()
        ->assertSee('Closed')
        ->assertDontSee('Schedule Session');

    $this->actingAs($volunteerUser)->get(route('notifications.index'))
        ->assertOk()
        ->assertSee('Alex Tan cancelled the session');
});

it('IT13 - feedback given in UC3 updates the volunteer\'s rating in the UC2 volunteer directory', function () {
    $student = User::factory()->create();
    [$volunteerUser, $profile] = integVolunteer();
    $request = integAcceptedRequest($student, $profile);

    $this->actingAs($student)->get(route('volunteers.index'))
        ->assertViewHas('volunteers', fn ($list) => $list->firstWhere('id', $profile->id)->rating === null);

    $session = SupportSession::create([
        'volunteer_match_id' => $request->volunteerMatches()->first()->id,
        'scheduled_start' => now()->subHour(),
        'location' => 'Library',
    ]);

    $this->actingAs($volunteerUser)->patch(route('support-sessions.complete', $session))
        ->assertSessionHas('success');

    $this->actingAs($student)->post(route('feedback.store', $session), [
        'rating' => 4,
        'comment' => 'Very helpful session.',
    ])->assertSessionHasNoErrors();

    $this->get(route('volunteers.index'))
        ->assertViewHas('volunteers', function ($list) use ($profile) {
            $volunteer = $list->firstWhere('id', $profile->id);

            return (float) $volunteer->rating === 4.0 && (int) $volunteer->reviews_count === 1;
        });
});

it('IT14 - a reschedule in UC3 appears in the unread pop-up when the volunteer logs in with 2FA (UC1)', function () {
    Mail::fake();
    $student = User::factory()->create(['name' => 'Alex Tan']);
    [$volunteerUser, $profile] = integVolunteer();
    $request = integAcceptedRequest($student, $profile);

    $this->actingAs($student)->post(route('support-sessions.store', $request), [
        'scheduled_start' => integSlot(),
        'location' => 'Library',
    ])->assertSessionHasNoErrors();

    $this->patch(route('support-sessions.reschedule.update', SupportSession::first()), [
        'scheduled_start' => now()->addDays(7)->setTime(14, 0)->format('Y-m-d H:i:s'),
    ])->assertSessionHasNoErrors();

    $this->post(route('logout'));

    $this->post(route('login.store'), [
        'email' => $volunteerUser->email,
        'password' => 'password',
    ])->assertRedirect(route('login.2fa'));

    $code = Mail::sent(VerificationCodeMail::class)->last()->code;

    $this->post(route('login.2fa.verify'), ['code' => $code])
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($volunteerUser);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('id="notification-popup"', false)
        ->assertSee('Alex Tan rescheduled your session');
});