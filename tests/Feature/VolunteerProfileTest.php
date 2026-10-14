<?php

// Use Case 1 - Volunteer Profile (test sheet TC20 to TC28)

use App\Enums\SupportMode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

/** A logged-in student. */
function volunteerTestStudent(): User
{
    return User::factory()->create([
        'student_id' => 'B2501146',
        'name'       => 'Tan Yen Tze',
        'email'      => 'yentze@helplive.edu.my',
        'password'   => 'Password123',
    ]);
}

/** posts the four support categories in the database and returns their ids by name. */
function volunteerTestCategoryIds(): array
{
    foreach (['Academic', 'Technology', 'New Student Support', 'General Student Support'] as $name) {
        DB::table('categories')->insert([
            'name'       => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    return array_map('intval', DB::table('categories')->pluck('id', 'name')->all());
}

/** The complete, valid form from the test sheet (TC21). Each test changes only what it needs. */
function volunteerPayload(array $categoryIds, array $overrides = []): array
{
    return array_merge([
        'categories'    => [$categoryIds['Academic'], $categoryIds['Technology']],
        'skills'        => 'Python, Exam Prep, Resume Review',
        'support_modes' => [SupportMode::F2F->value, SupportMode::Virtual->value],
        'meeting_link'  => 'https://zoom.us/j/123456789',
        'availability'  => [
            ['day' => '1', 'start' => '09:00', 'end' => '11:00'],
            ['day' => '3', 'start' => '14:00', 'end' => '16:00'],
        ],
    ], $overrides);
}

it('TC20 - edit() function shows the support categories from the database', function () {
    volunteerTestCategoryIds();

    $this->actingAs(volunteerTestStudent())
        ->get(route('volunteer.profile'))
        ->assertOk()
        ->assertSee('Academic')
        ->assertSee('Technology')
        ->assertSee('New Student Support')
        ->assertSee('General Student Support');
});

it('TC21 - store() function saves a complete volunteer profile', function () {
    $ids = volunteerTestCategoryIds();
    $student = volunteerTestStudent();

    $this->actingAs($student)
        ->post(route('volunteer.profile.store'), volunteerPayload($ids))
        ->assertRedirect(route('dashboard'));

    expect(session('status') ?? session('success'))->toBe('Your volunteer profile has been saved.');

    // volunteer_profiles: 1 row
    expect(DB::table('volunteer_profiles')->where('user_id', $student->id)->count())->toBe(1);
    $profile = DB::table('volunteer_profiles')->where('user_id', $student->id)->first();

    expect((bool) $profile->supports_f2f)->toBeTrue()
        ->and((bool) $profile->supports_virtual)->toBeTrue()
        ->and($profile->meeting_link)->toBe('https://zoom.us/j/123456789');

    // category_volunteer_profile: 2 rows
    $savedCategories = DB::table('category_volunteer_profile')
        ->where('volunteer_profile_id', $profile->id)
        ->pluck('category_id')
        ->map(fn ($id) => (int) $id)
        ->sort()
        ->values()
        ->all();

    expect($savedCategories)->toBe([$ids['Academic'], $ids['Technology']]);

    // skills: 3 rows
    expect(DB::table('skills')->where('volunteer_profile_id', $profile->id)->count())->toBe(3);
    foreach (['python', 'exam prep', 'resume review'] as $skill) {
        $this->assertDatabaseHas('skills', ['volunteer_profile_id' => $profile->id, 'name' => $skill]);
    }

    // availabilities: 2 rows (Monday = 1, Wednesday = 3)
    $slots = DB::table('availabilities')
        ->where('volunteer_profile_id', $profile->id)
        ->orderBy('day_of_week')
        ->get();

    expect($slots)->toHaveCount(2)
        ->and((int) $slots[0]->day_of_week)->toBe(1)
        ->and($slots[0]->start_time)->toContain('09:00')
        ->and($slots[0]->end_time)->toContain('11:00')
        ->and((int) $slots[1]->day_of_week)->toBe(3)
        ->and($slots[1]->start_time)->toContain('14:00')
        ->and($slots[1]->end_time)->toContain('16:00');
});

it('TC22 - edit() function shows the saved volunteer profile when the page is reopened', function () {
    $ids = volunteerTestCategoryIds();
    $student = volunteerTestStudent();

    $this->actingAs($student)->post(route('volunteer.profile.store'), volunteerPayload($ids));

    $this->actingAs($student->fresh())
        ->get(route('volunteer.profile'))
        ->assertOk()
        ->assertViewHas('skills', function ($skills) {
            $list = explode(', ', $skills);
            sort($list);

            return $list === ['exam prep', 'python', 'resume review'];
        })
        ->assertViewHas('meetingLink', 'https://zoom.us/j/123456789')
        ->assertViewHas('selectedModes', [SupportMode::F2F->value, SupportMode::Virtual->value])
        ->assertViewHas('selectedCategories', function ($saved) use ($ids) {
            $saved = array_map('intval', $saved);
            sort($saved);

            return $saved === [$ids['Academic'], $ids['Technology']];
        })
        ->assertViewHas('availabilityRows', function ($rows) {
            return count($rows) === 2
                && (int) $rows[0]['day'] === 1 && $rows[0]['start'] === '09:00' && $rows[0]['end'] === '11:00'
                && (int) $rows[1]['day'] === 3 && $rows[1]['start'] === '14:00' && $rows[1]['end'] === '16:00';
        });
});

it('TC23 - store() function rejects the form when no category is ticked', function () {
    $ids = volunteerTestCategoryIds();

    $this->actingAs(volunteerTestStudent())
        ->post(route('volunteer.profile.store'), volunteerPayload($ids, ['categories' => []]))
        ->assertSessionHasErrors(['categories' => 'Please choose at least one support category.']);

    $this->assertDatabaseCount('volunteer_profiles', 0);
});

it('TC24 - store() function rejects the form when no support mode is chosen', function () {
    $ids = volunteerTestCategoryIds();

    $this->actingAs(volunteerTestStudent())
        ->post(route('volunteer.profile.store'), volunteerPayload($ids, ['support_modes' => []]))
        ->assertSessionHasErrors(['support_modes' => 'Please choose at least one support mode.']);

    $this->assertDatabaseCount('volunteer_profiles', 0);
});

it('TC25 - store() function rejects Virtual support when the meeting link is empty', function () {
    $ids = volunteerTestCategoryIds();

    $this->actingAs(volunteerTestStudent())
        ->post(route('volunteer.profile.store'), volunteerPayload($ids, [
            'support_modes' => [SupportMode::Virtual->value],
            'meeting_link'  => '',
        ]))
        ->assertSessionHasErrors(['meeting_link' => 'Please enter your virtual meeting link.']);

    $this->assertDatabaseCount('volunteer_profiles', 0);
});

it('TC26 - store() function rejects a meeting link that is not a web address', function () {
    $ids = volunteerTestCategoryIds();

    $this->actingAs(volunteerTestStudent())
        ->post(route('volunteer.profile.store'), volunteerPayload($ids, [
            'support_modes' => [SupportMode::Virtual->value],
            'meeting_link'  => 'hello',
        ]))
        ->assertSessionHasErrors(['meeting_link' => 'Please enter a valid link, for example https://zoom.us/j/123456789.']);

    $this->assertDatabaseCount('volunteer_profiles', 0);
});

it('TC27 - store() function rejects an availability slot that ends before it starts', function () {
    $ids = volunteerTestCategoryIds();

    $this->actingAs(volunteerTestStudent())
        ->post(route('volunteer.profile.store'), volunteerPayload($ids, [
            'availability' => [['day' => '1', 'start' => '11:00', 'end' => '09:00']],
        ]))
        ->assertSessionHasErrors(['availability.0.end' => 'Each end time must be later than its start time.']);

    $this->assertDatabaseCount('volunteer_profiles', 0);
});

it('TC28 - store() function rejects the form when there is no availability slot', function () {
    $ids = volunteerTestCategoryIds();

    $this->actingAs(volunteerTestStudent())
        ->post(route('volunteer.profile.store'), volunteerPayload($ids, ['availability' => []]))
        ->assertSessionHasErrors(['availability' => 'Please add at least one availability slot.']);

    $this->assertDatabaseCount('volunteer_profiles', 0);
});