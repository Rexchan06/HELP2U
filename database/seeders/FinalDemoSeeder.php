<?php

namespace Database\Seeders;

use App\Enums\MatchStatus;
use App\Enums\RequestStatus;
use App\Enums\SessionStatus;
use App\Enums\SupportMode;
use App\Models\Category;
use App\Models\Feedback;
use App\Models\SupportRequest;
use App\Models\SupportSession;
use App\Models\User;
use App\Models\VolunteerProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Clean data set for the final live demonstration.
 *
 *   php artisan migrate:fresh --seeder=FinalDemoSeeder
 *
 * Accounts are created already verified, so no registration code is needed.
 * Logging in still sends a 2FA code to the real inbox.
 *
 * Prepared for the demo:
 *  - Student (Rex) and Volunteer (teammate) with real @helplive.edu.my inboxes.
 *  - Four sample volunteers so search and filters show realistic results.
 *    They are never logged into and only receive in-app notifications.
 *  - Session 1: SCHEDULED one week ahead  -> cancel it live.
 *  - Session 2: SCHEDULED two hours ago   -> volunteer marks it completed live,
 *                                            then the student leaves feedback.
 *  - Session 3: COMPLETED with feedback   -> Feedback page is not empty.
 *
 * The third teammate (Tan Yen Tze, B2501146) registers live and sets up a volunteer profile; the
 * student then sends a new request to them (request, accept, schedule).
 */
class FinalDemoSeeder extends Seeder
{
    /** Shared password for the seeded real accounts. */
    private const PASSWORD = 'Demo1234';

    /** Student account (logs in to request, schedule, cancel and give feedback). */
    private const STUDENT = [
        'student_id' => 'B2400169',
        'name' => 'Chan Zi Hao',
        'email' => 'b2400169@helplive.edu.my',
    ];

    /** Prepared volunteer account (marks sessions completed, receives feedback). */
    private const VOLUNTEER = [
        'student_id' => 'B2401138',
        'name' => 'Kaung Myat Si Thu',
        'email' => 'b2401138@helplive.edu.my',
    ];

    public function run(): void
    {
        $this->call(CategorySeeder::class);

        $academic = Category::where('name', 'Academic')->firstOrFail();
        $technology = Category::where('name', 'Technology')->firstOrFail();
        $newStudent = Category::where('name', 'New Student Support')->firstOrFail();
        $general = Category::where('name', 'General Student Support')->firstOrFail();

        $student = $this->user(...self::STUDENT);

        $volunteer = $this->volunteer(
            $this->user(...self::VOLUNTEER),
            'Year 2 IT student. Happy to help with programming, databases and assignment planning.',
            [$academic->id, $technology->id],
            ['PHP', 'Laravel', 'SQL', 'Assignment Planning'],
            f2f: true,
            virtual: true,
            days: [0, 1, 2, 3, 4, 5, 6],
            start: '09:00',
            end: '21:00',
        );

        $this->sampleVolunteers($academic->id, $technology->id, $newStudent->id, $general->id);

        // Session 1: upcoming, to be cancelled live.
        $this->session(
            $student, $volunteer, $technology->id,
            'My laptop cannot connect to the campus Wi-Fi and I need it set up before my lab.',
            SupportMode::F2F,
            start: now()->addDays(7)->setTime(14, 0),
            status: SessionStatus::Scheduled,
            location: 'HELP University Library, Level 2',
        );

        // Session 2: start time has passed, to be marked completed live.
        $this->session(
            $student, $volunteer, $academic->id,
            'I need help normalising my database tables to 3NF for my BIT216 assignment.',
            SupportMode::Virtual,
            start: now()->subHours(2)->startOfHour(),
            status: SessionStatus::Scheduled,
        );

        // Session 3: finished earlier, with feedback already given.
        $past = $this->session(
            $student, $volunteer, $academic->id,
            'Could someone explain SQL JOINs before my quiz? I keep mixing up LEFT and INNER joins.',
            SupportMode::Virtual,
            start: now()->subDays(5)->setTime(15, 0),
            status: SessionStatus::Completed,
        );

        Feedback::create([
            'support_session_id' => $past->id,
            'user_id' => $student->id,
            'rating' => 5,
            'comment' => 'Very clear explanation with examples. JOINs finally make sense!',
        ]);
    }

    private function sampleVolunteers(int $academic, int $technology, int $newStudent, int $general): void
    {
        $this->volunteer(
            $this->user('B2900001', 'Nurul Aisyah', 'demo.aisyah@helplive.edu.my'),
            'Final-year Psychology student. I help new students settle in and find their way around campus.',
            [$newStudent, $general],
            ['Campus Orientation', 'Time Management'],
            f2f: true, virtual: false, days: [1, 3, 5], start: '10:00', end: '14:00',
        );

        $this->volunteer(
            $this->user('B2900002', 'Jason Lim', 'demo.jason@helplive.edu.my'),
            'Year 3 Software Engineering student. Comfortable with Python, Java and debugging.',
            [$academic, $technology],
            ['Python', 'Java Programming', 'Debugging Help'],
            f2f: true, virtual: true, days: [2, 4], start: '13:00', end: '18:00',
        );

        $this->volunteer(
            $this->user('B2900003', 'Siti Hajar', 'demo.siti@helplive.edu.my'),
            'Business student and peer tutor for statistics and academic writing.',
            [$academic],
            ['Statistics', 'Academic Writing', 'APA Referencing'],
            f2f: false, virtual: true, days: [0, 6], start: '09:00', end: '12:00',
        );

        $this->volunteer(
            $this->user('B2900004', 'Ravi Kumar', 'demo.ravi@helplive.edu.my'),
            'IT support club member. I can help set up laptops, Microsoft 365 and the student portal.',
            [$technology, $general],
            ['Laptop Setup', 'Microsoft 365', 'Student Portal'],
            f2f: true, virtual: true, days: [1, 2, 3, 4, 5], start: '17:00', end: '20:00',
        );
    }

    private function session(
        User $student, VolunteerProfile $volunteer, int $categoryId, string $description,
        SupportMode $mode, Carbon $start, SessionStatus $status, ?string $location = null,
    ): SupportSession {
        $request = SupportRequest::create([
            'user_id' => $student->id,
            'category_id' => $categoryId,
            'description' => $description,
            'support_mode' => $mode,
            'status' => $status === SessionStatus::Scheduled ? RequestStatus::Scheduled : RequestStatus::Closed,
        ]);

        $match = $request->volunteerMatches()->create([
            'volunteer_profile_id' => $volunteer->id,
            'status' => MatchStatus::Accepted,
            'expires_at' => $start->copy()->subDay(),
            'responded_at' => $start->copy()->subDays(2),
        ]);

        return $match->supportSession()->create([
            'scheduled_start' => $start,
            'status' => $status,
            'location' => $location,
            'completed_at' => $status === SessionStatus::Completed ? $start->copy()->addHour() : null,
        ]);
    }

    private function user(string $student_id, string $name, string $email): User
    {
        return User::create([
            'student_id' => $student_id,
            'name' => $name,
            'email' => strtolower($email),
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
