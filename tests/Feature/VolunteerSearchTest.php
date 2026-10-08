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
        'user_id' => User::factory()->create()->id,
        'category_id' => Category::factory()->create()->id,
        'description' => 'I need help understanding SQL joins.',
        'support_mode' => $mode,
    ]);
}

it('lists active volunteers', function () {
    volunteerWith(['supports_f2f' => true], ['python'], [1]);
    VolunteerProfile::factory()->create(['is_active' => false]);

    $this->get(route('volunteers.index'))
        ->assertOk()
        ->assertSee('1 volunteer found');
});

it('filters volunteers by support mode', function () {
    volunteerWith(['supports_f2f' => true, 'supports_virtual' => false]);
    volunteerWith(['supports_f2f' => false, 'supports_virtual' => true]);

    $this->get(route('volunteers.index', ['modes' => [SupportMode::Virtual->value]]))
        ->assertOk()
        ->assertSee('1 volunteer found');
});

it('filters volunteers by availability day', function () {
    volunteerWith([], [], [1, 3]);
    volunteerWith([], [], [5]);

    $this->get(route('volunteers.index', ['days' => [3]]))
        ->assertOk()
        ->assertSee('1 volunteer found');
});

it('orders free volunteers before busy ones', function () {
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

it('pre-fills the mode filter from the support request context', function () {
    $request = supportRequestFor(SupportMode::F2F);

    $f2f = volunteerWith(['supports_f2f' => true, 'supports_virtual' => false]);
    $virtual = volunteerWith(['supports_f2f' => false, 'supports_virtual' => true]);
    $f2f->categories()->attach($request->category_id);
    $virtual->categories()->attach($request->category_id);

    $this->get(route('volunteers.index', ['support_request' => $request->id]))
        ->assertOk()
        ->assertSee('1 volunteer found');
});

it('filters volunteers by the request category in the backend', function () {
    $request = supportRequestFor();

    // Both support the request's mode so only the category filter discriminates.
    $relevant = volunteerWith(['supports_f2f' => true]);
    $other = volunteerWith(['supports_f2f' => true]);
    $relevant->categories()->attach($request->category_id);
    $other->categories()->attach(Category::factory()->create()->id);

    $response = $this->get(route('volunteers.index', ['support_request' => $request->id]))
        ->assertOk()
        ->assertSee('1 volunteer found');

    expect($response->viewData('volunteers')->pluck('id')->all())
        ->toBe([$relevant->id]);
});

it('lets the student clear the pre-filled mode filter', function () {
    $request = supportRequestFor(SupportMode::F2F);

    $f2f = volunteerWith(['supports_f2f' => true, 'supports_virtual' => false]);
    $virtual = volunteerWith(['supports_f2f' => false, 'supports_virtual' => true]);
    $f2f->categories()->attach($request->category_id);
    $virtual->categories()->attach($request->category_id);

    $this->get(route('volunteers.index', ['support_request' => $request->id, 'applied' => 1]))
        ->assertOk()
        ->assertSee('2 volunteers found');
});

it('does not apply the category filter without request context', function () {
    volunteerWith();
    volunteerWith();

    $this->get(route('volunteers.index'))
        ->assertOk()
        ->assertSee('2 volunteers found');
});

it('shows a volunteer profile detail page', function () {
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

it('returns 404 for inactive volunteer profiles', function () {
    $volunteer = VolunteerProfile::factory()->create(['is_active' => false]);

    $this->get(route('volunteers.show', $volunteer))->assertNotFound();
});
