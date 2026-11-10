<?php

use App\Enums\MatchStatus;
use App\Enums\SessionStatus;
use App\Enums\SupportMode;
use App\Models\Category;
use App\Models\SupportRequest;
use App\Models\User;
use App\Models\VolunteerMatch;
use App\Models\VolunteerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

function volunteerWith(array $profileAttributes = [], array $skills = [], array $days = []): VolunteerProfile
{
    $profile = VolunteerProfile::factory()->create($profileAttributes);

    foreach ($skills as $skill) {
        $profile->skills()->create(['name' => $skill]);
    }

    foreach ($days as $day) {
        $profile->availabilities()->create([
            'day_of_week' => $day,
            'start_time' => '09:00',
            'end_time' => '12:00',
        ]);
    }

    return $profile;
}

function supportRequestFor(SupportMode $mode = SupportMode::F2F): SupportRequest
{
    return SupportRequest::create([
        'user_id' => auth()->id(),
        'category_id' => Category::factory()->create()->id,
        'description' => 'I need help understanding SQL joins.',
        'support_mode' => $mode,
    ]);
}

it('TC43 - the search lists only active volunteers', function () {
    volunteerWith(['supports_f2f' => true], ['python'], [1]);
    VolunteerProfile::factory()->create(['is_active' => false]);

    $response = $this->get(route('volunteers.index'))->assertOk();

    expect($response->viewData('volunteers')->total())->toBe(1);
});

it('TC44 - the search filters volunteers by support mode', function () {
    volunteerWith(['supports_f2f' => true, 'supports_virtual' => false]);
    volunteerWith(['supports_f2f' => false, 'supports_virtual' => true]);

    $response = $this->get(route('volunteers.index', ['modes' => [SupportMode::Virtual->value]]))
        ->assertOk();

    expect($response->viewData('volunteers')->total())->toBe(1);
});

it('TC45 - the search filters volunteers by availability day', function () {
    volunteerWith([], [], [1, 3]);
    volunteerWith([], [], [5]);

    $response = $this->get(route('volunteers.index', ['days' => [3]]))->assertOk();

    expect($response->viewData('volunteers')->total())->toBe(1);
});

it('TC46 - free volunteers are ranked before busy ones', function () {
    $free = volunteerWith(['user_id' => User::factory()->create(['name' => 'Free Vol'])->id]);
    $busy = volunteerWith(['user_id' => User::factory()->create(['name' => 'Busy Vol'])->id]);

    VolunteerMatch::create([
        'support_request_id' => supportRequestFor()->id,
        'volunteer_profile_id' => $busy->id,
        'status' => MatchStatus::Accepted,
        'expires_at' => now()->addDay(),
        'responded_at' => now(),
    ])->supportSession()->create([
        'scheduled_start' => now()->addDay(),
        'status' => SessionStatus::Scheduled,
    ]);

    $response = $this->get(route('volunteers.index'))->assertOk();

    expect($response->viewData('volunteers')->pluck('id')->all())
        ->toBe([$free->id, $busy->id]);
});

it('TC47 - the mode filter is pre-filled from the support request context', function () {
    $request = supportRequestFor(SupportMode::F2F);

    $f2f = volunteerWith(['supports_f2f' => true, 'supports_virtual' => false]);
    $virtual = volunteerWith(['supports_f2f' => false, 'supports_virtual' => true]);
    $f2f->categories()->attach($request->category_id);
    $virtual->categories()->attach($request->category_id);

    $response = $this->get(route('volunteers.index', ['support_request' => $request->id]))
        ->assertOk();

    expect($response->viewData('volunteers')->total())->toBe(1);
});

it('TC48 - the backend filters volunteers by the request category', function () {
    $request = supportRequestFor();

    // Both support the request's mode so only the category filter discriminates.
    $relevant = volunteerWith(['supports_f2f' => true]);
    $other = volunteerWith(['supports_f2f' => true]);
    $relevant->categories()->attach($request->category_id);
    $other->categories()->attach(Category::factory()->create()->id);

    $response = $this->get(route('volunteers.index', ['support_request' => $request->id]))
        ->assertOk();

    expect($response->viewData('volunteers')->pluck('id')->all())
        ->toBe([$relevant->id]);
});

it('TC49 - the student can clear the pre-filled mode filter', function () {
    $request = supportRequestFor(SupportMode::F2F);

    $f2f = volunteerWith(['supports_f2f' => true, 'supports_virtual' => false]);
    $virtual = volunteerWith(['supports_f2f' => false, 'supports_virtual' => true]);
    $f2f->categories()->attach($request->category_id);
    $virtual->categories()->attach($request->category_id);

    $response = $this->get(route('volunteers.index', ['support_request' => $request->id, 'applied' => 1]))
        ->assertOk();

    expect($response->viewData('volunteers')->total())->toBe(2);
});

it('TC50 - the category filter is not applied without request context', function () {
    volunteerWith();
    volunteerWith();

    $response = $this->get(route('volunteers.index'))->assertOk();

    expect($response->viewData('volunteers')->total())->toBe(2);
});

it('TC51 - volunteers available on the request preferred day get a score boost', function () {
    $request = supportRequestFor();
    $request->update(['preferred_datetime' => now()->next('Monday')]);

    $availableMonday = volunteerWith(['supports_f2f' => true], [], [1]);
    $availableTuesday = volunteerWith(['supports_f2f' => true], [], [2]);
    $availableMonday->categories()->attach($request->category_id);
    $availableTuesday->categories()->attach($request->category_id);

    $response = $this->get(route('volunteers.index', ['support_request' => $request->id]))
        ->assertOk();

    expect($response->viewData('volunteers')->pluck('id')->all())
        ->toBe([$availableMonday->id, $availableTuesday->id]);
});

it('TC52 - the top-ranked volunteer is badged as Best Match', function () {
    $request = supportRequestFor();
    volunteerWith(['supports_f2f' => true])->categories()->attach($request->category_id);

    $this->get(route('volunteers.index', ['support_request' => $request->id]))
        ->assertOk()
        ->assertSee('Best Match');
});

it('TC53 - other matching volunteers are listed below the best match', function () {
    $request = supportRequestFor();
    $request->update(['preferred_datetime' => now()->next('Monday')]);

    $best = volunteerWith(['supports_f2f' => true], [], [1]);
    $other = volunteerWith(['supports_f2f' => true], [], [2]);
    $best->categories()->attach($request->category_id);
    $other->categories()->attach($request->category_id);

    $response = $this->get(route('volunteers.index', ['support_request' => $request->id]))
        ->assertOk()
        ->assertSee('Best Match')
        ->assertSee('Other volunteers');

    // The best match is still the highest-scored volunteer.
    expect($response->viewData('volunteers')->pluck('id')->first())->toBe($best->id);
});

it('TC54 - no Best Match badge is shown without request context', function () {
    volunteerWith();

    $this->get(route('volunteers.index'))
        ->assertOk()
        ->assertDontSee('Best Match');
});

it('TC55 - the volunteer profile detail page shows all profile fields', function () {
    $volunteer = volunteerWith(
        ['bio' => 'Third-year CS student happy to help.'],
        ['python'],
        [1, 3],
    );

    $this->get(route('volunteers.show', $volunteer))
        ->assertOk()
        ->assertSee($volunteer->user->name)
        ->assertSee('Third-year CS student happy to help.')
        ->assertSee('Python')
        ->assertSee('Monday')
        ->assertSee('Wednesday');
});

it('TC56 - inactive volunteer profiles return 404', function () {
    $volunteer = VolunteerProfile::factory()->create(['is_active' => false]);

    $this->get(route('volunteers.show', $volunteer))->assertNotFound();
});
