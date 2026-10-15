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

function makeUser(): User
{
    static $n = 0;
    $n++;

    return User::create([
        'student_id' => 'B24' . str_pad((string) $n, 5, '0', STR_PAD_LEFT),
        'name' => "Test User $n",
        'email' => "user$n@student.test",
        'password' => 'password',
    ]);
}

function makeVolunteer(): array
{
    $user = makeUser();
    $profile = VolunteerProfile::create(['user_id' => $user->id]);

    foreach (range(0, 6) as $day) {
        $profile->availabilities()->create([
            'day_of_week' => $day,
            'start_time' => '09:00',
            'end_time' => '17:00',
        ]);
    }

    return [$user, $profile];
}

function makeMatch(User $student, VolunteerProfile $profile, MatchStatus $status = MatchStatus::Accepted, string $mode = 'F2F'): VolunteerMatch
{
    $category = Category::firstOrCreate(['name' => 'Academic']);

    $request = SupportRequest::create([
        'user_id' => $student->id,
        'category_id' => $category->id,
        'description' => 'Need help with Laravel',
        'support_mode' => $mode,
        'status' => 'OPEN',
    ]);

    return VolunteerMatch::create([
        'support_request_id' => $request->id,
        'volunteer_profile_id' => $profile->id,
        'status' => $status,
        'expires_at' => now()->addHours(48),
    ]);
}

function makeSession(VolunteerMatch $match, $start = null): SupportSession
{
    return SupportSession::create([
        'volunteer_match_id' => $match->id,
        'scheduled_start' => $start ?? now()->addDays(30),
        'location' => 'Library',
    ]);
}

function future(): string
{
    return now()->addDays(30)->setTime(10, 0)->format('Y-m-d H:i:s');
}

// create() Test

it('TC1 - create() function shows the schedule form to the student with an accepted match', function () {
    $student = makeUser();
    [, $vp] = makeVolunteer();
    $match = makeMatch($student, $vp);

    $this->actingAs($student)
        ->get(route('support-sessions.create', $match->supportRequest))
        ->assertOk()
        ->assertViewIs('support-sessions.create');
});

it('TC2 - create() function returns 403 (Forbidden) on the schedule form if the student does not belong to the match', function () {
    $student = makeUser();
    $other = makeUser();
    [, $vp] = makeVolunteer();
    $match = makeMatch($student, $vp);

    $this->actingAs($other)
        ->get(route('support-sessions.create', $match->supportRequest))
        ->assertForbidden();
});

it('TC3 - create() function blocks the form when there is no accepted match', function () {
    $student = makeUser();
    [, $vp] = makeVolunteer();
    $match = makeMatch($student, $vp, MatchStatus::Pending);

    $this->actingAs($student)
        ->get(route('support-sessions.create', $match->supportRequest))
        ->assertRedirect()
        ->assertSessionHas('failed', 'This request has no accepted match.');
});

it('TC4 - create() function blocks the form when a session already exists', function () {
    $student = makeUser();
    [, $vp] = makeVolunteer();
    $match = makeMatch($student, $vp);
    makeSession($match);

    $this->actingAs($student)
        ->get(route('support-sessions.create', $match->supportRequest))
        ->assertRedirect()
        ->assertSessionHas('failed', 'A session has already been scheduled for this request.');
});

it('TC5 - create() function redirects a guest (not logged in) to the login page', function () {
    $student = makeUser();
    [, $vp] = makeVolunteer();
    $match = makeMatch($student, $vp);

    $this->get(route('support-sessions.create', $match->supportRequest))
        ->assertRedirect(route('login'));
});

it('TC6 - create() function returns 404 (Not Found) when the support request does not exist', function () {
    $this->actingAs(makeUser())
        ->get('/support-requests/9999/sessions/create')
        ->assertNotFound();
});

// store() Test

it('TC7 - store() function creates a session when the student submits valid data', function () {
    $student = makeUser();
    [, $vp] = makeVolunteer();
    $match = makeMatch($student, $vp);

    $response = $this->actingAs($student)->post(route('support-sessions.store', $match->supportRequest), [
        'scheduled_start' => future(),
        'location' => 'Library Level 3, Discussion Room 2',
    ]);

    $session = SupportSession::first();
    $response->assertRedirect(route('support-sessions.show', $session))
        ->assertSessionHas('success', 'Support session created successfully!');
    expect($session->volunteer_match_id)->toBe($match->id)
        ->and($session->location)->toBe('Library Level 3, Discussion Room 2');
    // status defaults to SCHEDULED and duration_minutes to 60 (database defaults)
    $this->assertDatabaseHas('support_sessions', ['volunteer_match_id' => $match->id, 'status' => 'SCHEDULED', 'duration_minutes' => 60]);
});

it('TC8 - store() function allows the location to be empty for a virtual session', function () {
    $student = makeUser();
    [, $vp] = makeVolunteer();
    $match = makeMatch($student, $vp, MatchStatus::Accepted, 'VIRTUAL');

    $this->actingAs($student)->post(route('support-sessions.store', $match->supportRequest), [
        'scheduled_start' => future(),
        'location' => '',
    ])->assertSessionHasNoErrors()
    ->assertRedirect();

    expect(SupportSession::first()->location)->toBeNull();
});

it('TC9 - store() function rejects the form when scheduled_start is empty', function () {
    $student = makeUser();
    [, $vp] = makeVolunteer();
    $match = makeMatch($student, $vp);

    $this->actingAs($student)->post(route('support-sessions.store', $match->supportRequest), [
        'scheduled_start' => '',
        'location' => 'Library',
    ])->assertSessionHasErrors(['scheduled_start' => 'The scheduled start field is required.']);

    expect(SupportSession::count())->toBe(0);
});

it('TC10 - store() function rejects a scheduled_start in the past', function () {
    $student = makeUser();
    [, $vp] = makeVolunteer();
    $match = makeMatch($student, $vp);

    $this->actingAs($student)->post(route('support-sessions.store', $match->supportRequest), [
        'scheduled_start' => now()->subDays(30)->format('Y-m-d H:i:s'),
        'location' => 'Library',
    ])->assertSessionHasErrors(['scheduled_start' => 'The scheduled start field must be a date after now.']);

    expect(SupportSession::count())->toBe(0);
});

it('TC11 - store() function rejects a scheduled_start that is not a date', function () {
    $student = makeUser();
    [, $vp] = makeVolunteer();
    $match = makeMatch($student, $vp);

    $this->actingAs($student)->post(route('support-sessions.store', $match->supportRequest), [
        'scheduled_start' => 'not-a-date',
        'location' => 'Library',
    ])->assertSessionHasErrors('scheduled_start');

    expect(SupportSession::count())->toBe(0);
});

it('TC12 - store() function rejects a location with 256 characters (over the limit)', function () {
    $student = makeUser();
    [, $vp] = makeVolunteer();
    $match = makeMatch($student, $vp);

    $this->actingAs($student)->post(route('support-sessions.store', $match->supportRequest), [
        'scheduled_start' => future(),
        'location' => str_repeat('A', 256),
    ])->assertSessionHasErrors(['location' => 'The location field must not be greater than 255 characters.']);

    expect(SupportSession::count())->toBe(0);
});

it('TC13 - store() function accepts a location with exactly 255 characters', function () {
    $student = makeUser();
    [, $vp] = makeVolunteer();
    $match = makeMatch($student, $vp);

    $this->actingAs($student)->post(route('support-sessions.store', $match->supportRequest), [
        'scheduled_start' => future(),
        'location' => str_repeat('A', 255),
    ])->assertSessionHasNoErrors()->assertRedirect();

    expect(strlen(SupportSession::first()->location))->toBe(255);
});

it('TC14 - store() function returns 403 (Forbidden) if the student does not own the request', function () {
    $student = makeUser();
    $other = makeUser();
    [, $vp] = makeVolunteer();
    $match = makeMatch($student, $vp);

    $this->actingAs($other)->post(route('support-sessions.store', $match->supportRequest), [
        'scheduled_start' => future(),
        'location' => 'Library',
    ])->assertForbidden();

    expect(SupportSession::count())->toBe(0);
});

it('TC15 - store() function blocks the session when there is no accepted match', function () {
    $student = makeUser();
    [, $vp] = makeVolunteer();
    $match = makeMatch($student, $vp, MatchStatus::Pending);

    $this->actingAs($student)->post(route('support-sessions.store', $match->supportRequest), [
        'scheduled_start' => future(),
        'location' => 'Library',
    ])->assertSessionHas('failed', 'This request has no accepted match.');

    expect(SupportSession::count())->toBe(0);
});

it('TC16 - store() function blocks a second session for the same request', function () {
    $student = makeUser();
    [, $vp] = makeVolunteer();
    $match = makeMatch($student, $vp);
    $payload = ['scheduled_start' => future(), 'location' => 'Library'];

    $this->actingAs($student)->post(route('support-sessions.store', $match->supportRequest), $payload)->assertRedirect();
    $this->actingAs($student)->post(route('support-sessions.store', $match->supportRequest), $payload)
        ->assertSessionHas('failed', 'A session has already been scheduled for this request.');

    expect(SupportSession::count())->toBe(1);
});

// index() Test

it('TC17 - index() function lists the student\'s own sessions', function () {
    $student = makeUser();
    [, $vp] = makeVolunteer();
    $s1 = makeSession(makeMatch($student, $vp), now()->addDays(10));
    $s2 = makeSession(makeMatch($student, $vp), now()->addDays(15));

    $this->actingAs($student)->get(route('support-sessions.index'))
        ->assertOk()
        ->assertViewHas('supportSessions', fn ($list) => $list->pluck('id')->sort()->values()->all() === [$s1->id, $s2->id]);
});

it('TC18 - index() function lists the sessions assigned to the volunteer', function () {
    $student = makeUser();
    [$volUser, $vp] = makeVolunteer();
    $s1 = makeSession(makeMatch($student, $vp));

    $this->actingAs($volUser)->get(route('support-sessions.index'))
        ->assertOk()
        ->assertViewHas('supportSessions', fn ($list) => $list->pluck('id')->all() === [$s1->id]);
});

it('TC19 - index() function shows an empty list when the user has no sessions', function () {
    $this->actingAs(makeUser())->get(route('support-sessions.index'))
        ->assertOk()
        ->assertViewHas('supportSessions', fn ($list) => $list->isEmpty());
});

it('TC20 - index() function does not list other people\'s sessions', function () {
    $studentA = makeUser();
    $studentB = makeUser();
    [, $vpW] = makeVolunteer();
    makeSession(makeMatch($studentB, $vpW)); // a session that belongs to another student

    $this->actingAs($studentA)->get(route('support-sessions.index'))
        ->assertViewHas('supportSessions', fn ($list) => $list->isEmpty());
});

it('TC21 - index() function orders sessions by start time, latest first', function () {
    $student = makeUser();
    [, $vp] = makeVolunteer();
    $s1 = makeSession(makeMatch($student, $vp), now()->addDays(10));
    $s2 = makeSession(makeMatch($student, $vp), now()->addDays(15));

    $this->actingAs($student)->get(route('support-sessions.index'))
        ->assertViewHas('supportSessions', fn ($list) => $list->pluck('id')->all() === [$s2->id, $s1->id]);
});

// show() Test

it('TC22 - show() function lets the student view their own session', function () {
    $student = makeUser();
    [, $vp] = makeVolunteer();
    $session = makeSession(makeMatch($student, $vp));

    $this->actingAs($student)->get(route('support-sessions.show', $session))
        ->assertOk()->assertViewIs('support-sessions.show');
});

it('TC23 - show() function lets the volunteer view their assigned session', function () {
    $student = makeUser();
    [$volUser, $vp] = makeVolunteer();
    $session = makeSession(makeMatch($student, $vp));

    $this->actingAs($volUser)->get(route('support-sessions.show', $session))
        ->assertOk()->assertViewIs('support-sessions.show');
});

it('TC24 - show() function returns 403 (Forbidden) for a user who is not in the session', function () {
    $student = makeUser();
    [, $vp] = makeVolunteer();
    $session = makeSession(makeMatch($student, $vp));

    $this->actingAs(makeUser())->get(route('support-sessions.show', $session))
        ->assertForbidden();
});

it('TC25 - show() function returns 404 (Not Found) when the session does not exist', function () {
    $this->actingAs(makeUser())->get('/support-sessions/9999')->assertNotFound();
});

it('TC26 - show() function redirects a guest (not logged in) to the login page', function () {
    $student = makeUser();
    [, $vp] = makeVolunteer();
    $session = makeSession(makeMatch($student, $vp));

    $this->get(route('support-sessions.show', $session))->assertRedirect(route('login'));
});
// store() availability and booking Test

function at(string $time): string
{
    return now()->addDays(30)->setTimeFromTimeString($time)->format('Y-m-d H:i:s');
}

it('TC27 - store() function marks the support request as SCHEDULED after booking', function () {
    $student = makeUser();
    [, $vp] = makeVolunteer();
    $match = makeMatch($student, $vp);

    $this->actingAs($student)->post(route('support-sessions.store', $match->supportRequest), [
        'scheduled_start' => future(),
        'location' => 'Library',
    ])->assertSessionHasNoErrors();

    expect($match->supportRequest->fresh()->status)->toBe(RequestStatus::Scheduled);
});

it('TC28 - store() function rejects a time outside the volunteer\'s availability', function () {
    $student = makeUser();
    [, $vp] = makeVolunteer();
    $match = makeMatch($student, $vp);

    $this->actingAs($student)->post(route('support-sessions.store', $match->supportRequest), [
        'scheduled_start' => at('18:00'),
        'location' => 'Library',
    ])->assertSessionHasErrors('scheduled_start');

    expect(SupportSession::count())->toBe(0)
        ->and($match->supportRequest->fresh()->status)->not->toBe(RequestStatus::Scheduled);
});

it('TC29 - store() function accepts a session starting exactly when availability starts', function () {
    $student = makeUser();
    [, $vp] = makeVolunteer();
    $match = makeMatch($student, $vp);

    $this->actingAs($student)->post(route('support-sessions.store', $match->supportRequest), [
        'scheduled_start' => at('09:00'),
        'location' => 'Library',
    ])->assertSessionHasNoErrors();

    expect(SupportSession::count())->toBe(1);
});

it('TC30 - store() function accepts a session ending exactly when availability ends', function () {
    $student = makeUser();
    [, $vp] = makeVolunteer();
    $match = makeMatch($student, $vp);

    $this->actingAs($student)->post(route('support-sessions.store', $match->supportRequest), [
        'scheduled_start' => at('16:00'),
        'location' => 'Library',
    ])->assertSessionHasNoErrors();

    expect(SupportSession::count())->toBe(1);
});

it('TC31 - store() function rejects a session that runs past the end of availability', function () {
    $student = makeUser();
    [, $vp] = makeVolunteer();
    $match = makeMatch($student, $vp);

    $this->actingAs($student)->post(route('support-sessions.store', $match->supportRequest), [
        'scheduled_start' => at('16:30'),
        'location' => 'Library',
    ])->assertSessionHasErrors('scheduled_start');

    expect(SupportSession::count())->toBe(0);
});

it('TC32 - store() function rejects a time the volunteer is already booked for', function () {
    [, $vp] = makeVolunteer();
    makeSession(makeMatch(makeUser(), $vp), at('10:00'));

    $student = makeUser();
    $match = makeMatch($student, $vp);

    $this->actingAs($student)->post(route('support-sessions.store', $match->supportRequest), [
        'scheduled_start' => at('10:00'),
        'location' => 'Library',
    ])->assertSessionHasErrors('scheduled_start');

    expect(SupportSession::count())->toBe(1);
});

it('TC33 - store() function rejects an empty location for a face-to-face session', function () {
    $student = makeUser();
    [, $vp] = makeVolunteer();
    $match = makeMatch($student, $vp, MatchStatus::Accepted, 'F2F');
 
    $this->actingAs($student)->post(route('support-sessions.store', $match->supportRequest), [
        'scheduled_start' => future(),
        'location' => '',
    ])->assertSessionHasErrors(['location' => 'The location field is required.']);
 
    expect(SupportSession::count())->toBe(0);
});
