<?php

use App\Enums\MatchStatus;
use App\Enums\RequestStatus;
use App\Models\Category;
use App\Models\SupportRequest;
use App\Models\SupportSession;
use App\Models\User;
use App\Models\VolunteerMatch;
use App\Models\VolunteerProfile;
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

it('IT1 - a student who logs in through the UC1 login page can open My Sessions (UC3)', function () {
    $student = User::factory()->create(['email' => 'alex@student.test']);

    $this->post(route('login.store'), [
        'email' => 'alex@student.test',
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));

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