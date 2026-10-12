<?php

use App\Enums\MatchStatus;
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

    return [$user, $profile];
}

function makeMatch(User $student, VolunteerProfile $profile, MatchStatus $status = MatchStatus::Accepted): VolunteerMatch
{
    $category = Category::firstOrCreate(['name' => 'Academic']);

    $request = SupportRequest::create([
        'user_id' => $student->id,
        'category_id' => $category->id,
        'description' => 'Need help with Laravel',
        'support_mode' => 'F2F',
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
    return now()->addDays(30)->format('Y-m-d H:i:s');
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

it('TC2 returns 403 on the schedule form for a non-owner', function () {
    $student = makeUser();
    $other = makeUser();
    [, $vp] = makeVolunteer();
    $match = makeMatch($student, $vp);

    $this->actingAs($other)
        ->get(route('support-sessions.create', $match->supportRequest))
        ->assertForbidden();
});

it('TC3 blocks the form when there is no accepted match', function () {
    $student = makeUser();
    [, $vp] = makeVolunteer();
    $match = makeMatch($student, $vp, MatchStatus::Pending);

    $this->actingAs($student)
        ->get(route('support-sessions.create', $match->supportRequest))
        ->assertRedirect()
        ->assertSessionHas('failed', 'This request has no accepted match.');
});

it('TC4 blocks the form when a session already exists', function () {
    $student = makeUser();
    [, $vp] = makeVolunteer();
    $match = makeMatch($student, $vp);
    makeSession($match);

    $this->actingAs($student)
        ->get(route('support-sessions.create', $match->supportRequest))
        ->assertRedirect()
        ->assertSessionHas('failed', 'A session has already been scheduled for this request.');
});

it('TC5 redirects guests from the schedule form to login', function () {
    // needs the named route 'login' from UC1
    $student = makeUser();
    [, $vp] = makeVolunteer();
    $match = makeMatch($student, $vp);

    $this->get(route('support-sessions.create', $match->supportRequest))
        ->assertRedirect(route('login'));
})->skip('Waiting for UC1: no named route "login" exists yet');

it('TC6 returns 404 for a non-existent request', function () {
    $this->actingAs(makeUser())
        ->get('/support-requests/9999/sessions/create')
        ->assertNotFound();
});

// store() Test

it('TC7 creates a session with valid data', function () {
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

it('TC8 allows an empty location', function () {
    $student = makeUser();
    [, $vp] = makeVolunteer();
    $match = makeMatch($student, $vp);

    $this->actingAs($student)->post(route('support-sessions.store', $match->supportRequest), [
        'scheduled_start' => future(),
        'location' => '',
    ])->assertRedirect();

    expect(SupportSession::first()->location)->toBeNull();
});

it('TC9 requires scheduled_start', function () {
    $student = makeUser();
    [, $vp] = makeVolunteer();
    $match = makeMatch($student, $vp);

    $this->actingAs($student)->post(route('support-sessions.store', $match->supportRequest), [
        'scheduled_start' => '',
        'location' => 'Library',
    ])->assertSessionHasErrors(['scheduled_start' => 'The scheduled start field is required.']);

    expect(SupportSession::count())->toBe(0);
});

it('TC10 rejects a past scheduled_start', function () {
    $student = makeUser();
    [, $vp] = makeVolunteer();
    $match = makeMatch($student, $vp);

    $this->actingAs($student)->post(route('support-sessions.store', $match->supportRequest), [
        'scheduled_start' => now()->subDays(30)->format('Y-m-d H:i:s'),
        'location' => 'Library',
    ])->assertSessionHasErrors(['scheduled_start' => 'The scheduled start field must be a date after now.']);

    expect(SupportSession::count())->toBe(0);
});

it('TC11 rejects a non-date scheduled_start', function () {
    $student = makeUser();
    [, $vp] = makeVolunteer();
    $match = makeMatch($student, $vp);

    $this->actingAs($student)->post(route('support-sessions.store', $match->supportRequest), [
        'scheduled_start' => 'not-a-date',
        'location' => 'Library',
    ])->assertSessionHasErrors('scheduled_start');

    expect(SupportSession::count())->toBe(0);
});

it('TC12 rejects a 256-character location', function () {
    $student = makeUser();
    [, $vp] = makeVolunteer();
    $match = makeMatch($student, $vp);

    $this->actingAs($student)->post(route('support-sessions.store', $match->supportRequest), [
        'scheduled_start' => future(),
        'location' => str_repeat('A', 256),
    ])->assertSessionHasErrors(['location' => 'The location field must not be greater than 255 characters.']);

    expect(SupportSession::count())->toBe(0);
});

it('TC13 accepts a 255-character location', function () {
    $student = makeUser();
    [, $vp] = makeVolunteer();
    $match = makeMatch($student, $vp);

    $this->actingAs($student)->post(route('support-sessions.store', $match->supportRequest), [
        'scheduled_start' => future(),
        'location' => str_repeat('A', 255),
    ])->assertSessionHasNoErrors()->assertRedirect();

    expect(strlen(SupportSession::first()->location))->toBe(255);
});

it('TC14 returns 403 when a non-owner posts a session', function () {
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

it('TC15 blocks store without an accepted match', function () {
    $student = makeUser();
    [, $vp] = makeVolunteer();
    $match = makeMatch($student, $vp, MatchStatus::Pending);

    $this->actingAs($student)->post(route('support-sessions.store', $match->supportRequest), [
        'scheduled_start' => future(),
        'location' => 'Library',
    ])->assertSessionHas('failed', 'This request has no accepted match.');

    expect(SupportSession::count())->toBe(0);
});

it('TC16 blocks a duplicate session submission', function () {
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

it('TC17 lists a student\'s own sessions', function () {
    $student = makeUser();
    [, $vp] = makeVolunteer();
    $s1 = makeSession(makeMatch($student, $vp), now()->addDays(10));
    $s2 = makeSession(makeMatch($student, $vp), now()->addDays(15));

    $this->actingAs($student)->get(route('support-sessions.index'))
        ->assertOk()
        ->assertViewHas('supportSessions', fn ($list) => $list->pluck('id')->sort()->values()->all() === [$s1->id, $s2->id]);
});

it('TC18 lists a volunteer\'s assigned sessions', function () {
    $student = makeUser();
    [$volUser, $vp] = makeVolunteer();
    $s1 = makeSession(makeMatch($student, $vp));

    $this->actingAs($volUser)->get(route('support-sessions.index'))
        ->assertOk()
        ->assertViewHas('supportSessions', fn ($list) => $list->pluck('id')->all() === [$s1->id]);
});

it('TC19 shows an empty list for a user with no sessions', function () {
    $this->actingAs(makeUser())->get(route('support-sessions.index'))
        ->assertOk()
        ->assertViewHas('supportSessions', fn ($list) => $list->isEmpty());
});

it('TC20 does not list other people\'s sessions', function () {
    $studentA = makeUser();
    $studentB = makeUser();
    [, $vpW] = makeVolunteer();
    makeSession(makeMatch($studentB, $vpW)); // S3

    $this->actingAs($studentA)->get(route('support-sessions.index'))
        ->assertViewHas('supportSessions', fn ($list) => $list->isEmpty());
});

it('TC21 orders sessions by start time, latest first', function () {
    $student = makeUser();
    [, $vp] = makeVolunteer();
    $s1 = makeSession(makeMatch($student, $vp), now()->addDays(10));
    $s2 = makeSession(makeMatch($student, $vp), now()->addDays(15));

    $this->actingAs($student)->get(route('support-sessions.index'))
        ->assertViewHas('supportSessions', fn ($list) => $list->pluck('id')->all() === [$s2->id, $s1->id]);
});

// show() Test

it('TC22 lets the student view the session', function () {
    $student = makeUser();
    [, $vp] = makeVolunteer();
    $session = makeSession(makeMatch($student, $vp));

    $this->actingAs($student)->get(route('support-sessions.show', $session))
        ->assertOk()->assertViewIs('support-sessions.show');
});

it('TC23 lets the volunteer view the session', function () {
    $student = makeUser();
    [$volUser, $vp] = makeVolunteer();
    $session = makeSession(makeMatch($student, $vp));

    $this->actingAs($volUser)->get(route('support-sessions.show', $session))
        ->assertOk()->assertViewIs('support-sessions.show');
});

it('TC24 returns 403 for a non-participant', function () {
    $student = makeUser();
    [, $vp] = makeVolunteer();
    $session = makeSession(makeMatch($student, $vp));

    $this->actingAs(makeUser())->get(route('support-sessions.show', $session))
        ->assertForbidden();
});

it('TC25 returns 404 for a non-existent session', function () {
    $this->actingAs(makeUser())->get('/support-sessions/9999')->assertNotFound();
});

it('TC26 redirects guests from a session to login', function () {
    $student = makeUser();
    [, $vp] = makeVolunteer();
    $session = makeSession(makeMatch($student, $vp));

    $this->get(route('support-sessions.show', $session))->assertRedirect(route('login'));
})->skip('Waiting for UC1: no named route "login" exists yet');