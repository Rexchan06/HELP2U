<?php

namespace Database\Seeders;

use App\Enums\MatchStatus;
use App\Enums\RequestStatus;
use App\Enums\SessionStatus;
use App\Enums\SupportMode;
use App\Models\Category;
use App\Models\SupportRequest;
use App\Models\User;
use App\Models\VolunteerProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Known accounts for the Iteration 1 live demonstration.
 *
 * Run after the default seeders:
 *   php artisan migrate:fresh --seed
 *   php artisan db:seed --class=DemoSeeder
 *
 * Accept/reject is an Iteration 2 feature, so Alex's request is seeded as
 * already accepted by Daniel to let the UC3 scheduling flow be demonstrated.
 */
class DemoSeeder extends Seeder
{
    private const PASSWORD = 'Demo1234';

    public function run(): void
    {
        $academic = Category::where('name', 'Academic')->firstOrFail();
        $technology = Category::where('name', 'Technology')->firstOrFail();
        $newStudent = Category::where('name', 'New Student Support')->firstOrFail();

        $alex = $this->user('B2400001', 'Alex Tan', 'alex.tan@helplive.edu.my');
        $mei = $this->user('B2400002', 'Mei Ling', 'mei.ling@helplive.edu.my');

        $daniel = $this->volunteer(
            $this->user('B2300101', 'Daniel Wong', 'daniel.wong@helplive.edu.my'),
            'Year 3 Computer Science student. Happy to help with programming assignments and debugging.',
            [$academic->id, $technology->id],
            ['Python', 'Java Programming', 'Debugging Help'],
            f2f: true,
            virtual: true,
            days: [1, 2, 3, 4, 5],
            start: '09:00',
            end: '17:00',
        );

        $this->volunteer(
            $this->user('B2300102', 'Priya Nair', 'priya.nair@helplive.edu.my'),
            'Student ambassador. I can show new students around campus and help them settle in.',
            [$newStudent->id],
            ['Campus Orientation', 'Library Navigation'],
            f2f: true,
            virtual: false,
            days: [2, 4],
            start: '10:00',
            end: '13:00',
        );

        // Alex: accepted by Daniel, no session yet -> ready for UC3 scheduling.
        $alexRequest = SupportRequest::create([
            'user_id' => $alex->id,
            'category_id' => $academic->id,
            'description' => 'I need help understanding recursion for my BIT108 Python assignment.',
            'support_mode' => SupportMode::Virtual,
            'preferred_datetime' => Carbon::parse('next monday')->setTime(10, 0),
            'status' => RequestStatus::Matched,
        ]);

        $alexRequest->volunteerMatches()->create([
            'volunteer_profile_id' => $daniel->id,
            'status' => MatchStatus::Accepted,
            'expires_at' => now()->addDay(),
            'responded_at' => now()->subHour(),
        ]);

        // Mei: already booked with Daniel next Wednesday 14:00 -> demonstrates
        // the double-booking check and Daniel's upcoming sessions on his dashboard.
        $meiRequest = SupportRequest::create([
            'user_id' => $mei->id,
            'category_id' => $technology->id,
            'description' => 'Need help installing Python and VS Code on my laptop.',
            'support_mode' => SupportMode::Virtual,
            'status' => RequestStatus::Scheduled,
        ]);

        $meiRequest->volunteerMatches()->create([
            'volunteer_profile_id' => $daniel->id,
            'status' => MatchStatus::Accepted,
            'expires_at' => now()->addDay(),
            'responded_at' => now()->subHours(3),
        ])->supportSession()->create([
            'scheduled_start' => Carbon::parse('next wednesday')->setTime(14, 0),
            'status' => SessionStatus::Scheduled,
        ]);
    }

    private function user(string $studentId, string $name, string $email): User
    {
        return User::create([
            'student_id' => $studentId,
            'name' => $name,
            'email' => $email,
            'email_verified_at' => now(),
            'password' => Hash::make(self::PASSWORD),
        ]);
    }

    /**
     * @param  array<int, int>  $categoryIds
     * @param  array<int, string>  $skills
     * @param  array<int, int>  $days
     */
    private function volunteer(
        User $user, string $bio, array $categoryIds, array $skills,
        bool $f2f, bool $virtual, array $days, string $start, string $end,
    ): VolunteerProfile {
        $profile = VolunteerProfile::create([
            'user_id' => $user->id,
            'bio' => $bio,
            'supports_f2f' => $f2f,
            'supports_virtual' => $virtual,
            'meeting_link' => $virtual ? 'https://teams.microsoft.com/l/meetup-join/help2u-demo' : null,
            'location' => $f2f ? 'HELP University Library, Level 2' : null,
            'is_active' => true,
        ]);

        $profile->categories()->attach($categoryIds);
        $profile->skills()->createMany(array_map(fn (string $name) => ['name' => $name], $skills));

        foreach ($days as $day) {
            $profile->availabilities()->create([
                'day_of_week' => $day,
                'start_time' => $start,
                'end_time' => $end,
            ]);
        }

        return $profile;
    }
}
