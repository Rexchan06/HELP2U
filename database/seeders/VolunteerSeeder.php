<?php

namespace Database\Seeders;

use App\Enums\MatchStatus;
use App\Enums\SessionStatus;
use App\Enums\SupportMode;
use App\Models\Availability;
use App\Models\Category;
use App\Models\SupportRequest;
use App\Models\User;
use App\Models\VolunteerMatch;
use App\Models\VolunteerProfile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

/**
 * Seeds demo volunteers so the "Find Volunteers" directory has data to
 * search and filter. Two volunteers are seeded as busy and a few carry
 * ratings, which demonstrates the free-first ordering.
 */
class VolunteerSeeder extends Seeder
{
    private const SKILL_POOL = [
        'calculus support', 'linear algebra', 'statistics basics', 'exam prep',
        'python', 'java programming', 'debugging help', 'sql basics',
        'academic writing', 'proofreading', 'presentation skills',
        'chemistry', 'physics tutor', 'excel basics',
    ];

    public function run(): void
    {
        $categoryIds = Category::pluck('id')->all();

        /** @var Collection<int, VolunteerProfile> $profiles */
        $profiles = VolunteerProfile::factory()->count(12)->create();

        foreach ($profiles as $profile) {
            $profile->skills()->createMany(
                collect(self::SKILL_POOL)->shuffle()->take(random_int(2, 4))
                    ->map(fn (string $name) => ['name' => $name])
                    ->all()
            );

            foreach (collect(Availability::DAY_ORDER)->shuffle()->take(random_int(2, 4)) as $day) {
                $start = random_int(8, 15);
                $profile->availabilities()->create([
                    'day_of_week' => $day,
                    'start_time' => sprintf('%02d:00', $start),
                    'end_time' => sprintf('%02d:00', $start + random_int(2, 4)),
                ]);
            }

            if ($categoryIds !== []) {
                $profile->categories()->attach(
                    collect($categoryIds)->shuffle()->take(random_int(1, 2))->all()
                );
            }
        }

        $requester = User::orderBy('id')->first() ?? User::factory()->create();

        $this->seedBusyVolunteers($profiles, $requester, $categoryIds);
        $this->seedReviews($profiles->slice(2, 5)->values(), $requester, $categoryIds);
    }

    /**
     * Mark two volunteers busy: one is being asked (pending match), the
     * other is committed to an upcoming session.
     *
     * @param  Collection<int, VolunteerProfile>  $profiles
     * @param  array<int, int>  $categoryIds
     */
    private function seedBusyVolunteers(Collection $profiles, User $requester, array $categoryIds): void
    {
        if ($profiles->count() < 2) {
            return;
        }

        $request = $this->demoRequest($requester, $categoryIds);

        VolunteerMatch::create([
            'support_request_id' => $request->id,
            'volunteer_profile_id' => $profiles[0]->id,
            'status' => MatchStatus::Pending,
            'expires_at' => now()->addDay(),
        ]);

        VolunteerMatch::create([
            'support_request_id' => $request->id,
            'volunteer_profile_id' => $profiles[1]->id,
            'status' => MatchStatus::Accepted,
            'expires_at' => now()->addDay(),
            'responded_at' => now()->subHour(),
        ])->supportSession()->create([
            'scheduled_start' => now()->addDays(2),
            'status' => SessionStatus::Scheduled,
        ]);
    }

    /**
     * Give a few volunteers one or two completed, reviewed sessions.
     *
     * @param  Collection<int, VolunteerProfile>  $profiles
     * @param  array<int, int>  $categoryIds
     */
    private function seedReviews(Collection $profiles, User $requester, array $categoryIds): void
    {
        foreach ($profiles as $index => $profile) {
            foreach (range(1, $index % 2 === 0 ? 2 : 1) as $i) {
                VolunteerMatch::create([
                    'support_request_id' => $this->demoRequest($requester, $categoryIds)->id,
                    'volunteer_profile_id' => $profile->id,
                    'status' => MatchStatus::Accepted,
                    'expires_at' => now()->subDays(8),
                    'responded_at' => now()->subDays(9),
                ])->supportSession()->create([
                    'scheduled_start' => now()->subWeek()->subHours($i),
                    'status' => SessionStatus::Completed,
                    'completed_at' => now()->subWeek()->subHours($i)->addHour(),
                ])->feedback()->create([
                    'user_id' => $requester->id,
                    'rating' => [5, 4, 3, 5, 4][$index] ?? 4,
                    'comment' => fake()->sentence(),
                ]);
            }
        }
    }

    /**
     * @param  array<int, int>  $categoryIds
     */
    private function demoRequest(User $requester, array $categoryIds): SupportRequest
    {
        return SupportRequest::create([
            'user_id' => $requester->id,
            'category_id' => $categoryIds[array_rand($categoryIds)] ?? Category::factory()->create()->id,
            'description' => 'Seeded request used to generate volunteer matches.',
            'support_mode' => fake()->randomElement(SupportMode::cases()),
        ]);
    }
}
