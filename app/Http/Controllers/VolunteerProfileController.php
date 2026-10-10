<?php

namespace App\Http\Controllers;

use App\Enums\SupportMode;
use App\Models\Category;
use App\Models\VolunteerProfile;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class VolunteerProfileController extends Controller
{
    // day_of_week numbers stored in the availabilities table (1 = Monday ... 7 = Sunday).
    // If your team uses different numbers (for example 0 = Sunday), change them here only.
    public const DAYS = [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        7 => 'Sunday',
    ];

    public function edit(Request $request)
    {
        $profile = $request->user()->volunteerProfile;

        $modes = [];
        if ($profile?->supports_f2f) {
            $modes[] = SupportMode::F2F->value;
        }
        if ($profile?->supports_virtual) {
            $modes[] = SupportMode::Virtual->value;
        }

        $rows = $profile
            ? $profile->availabilities()
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get()
                ->map(fn ($slot) => [
                    'day'   => $slot->day_of_week,
                    'start' => Carbon::parse($slot->start_time)->format('H:i'),
                    'end'   => Carbon::parse($slot->end_time)->format('H:i'),
                ])
                ->all()
            : [];

        return view('volunteer.profile', [
            'days'               => self::DAYS,
            'categories'         => Category::orderBy('id')->get(),
            'selectedCategories' => $profile ? $profile->categories()->pluck('categories.id')->all() : [],
            'skills'             => $profile ? $profile->skills()->pluck('name')->implode(', ') : '',
            'selectedModes'      => $modes,
            'meetingLink'        => $profile?->meeting_link,
            'availabilityRows'   => $rows,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'categories'           => ['required', 'array', 'min:1'],
            'categories.*'         => ['integer', 'exists:categories,id'],
            'skills'               => ['nullable', 'string', 'max:500'],
            'support_modes'        => ['required', 'array', 'min:1'],
            'support_modes.*'      => [Rule::enum(SupportMode::class)],
            'meeting_link'         => ['nullable', 'url', 'max:255'],
            'availability'         => ['required', 'array', 'min:1'],
            'availability.*.day'   => ['required', Rule::in(array_keys(self::DAYS))],
            'availability.*.start' => ['required', 'date_format:H:i'],
            'availability.*.end'   => ['required', 'date_format:H:i', 'after:availability.*.start'],
        ], [
            'categories.required'        => 'Please choose at least one support category.',
            'categories.min'             => 'Please choose at least one support category.',
            'support_modes.required'     => 'Please choose at least one support mode.',
            'support_modes.min'          => 'Please choose at least one support mode.',
            'meeting_link.url'           => 'Please enter a valid link, for example https://zoom.us/j/123456789.',
            'availability.required'      => 'Please add at least one availability slot.',
            'availability.min'           => 'Please add at least one availability slot.',
            'availability.*.end.after'   => 'Each end time must be later than its start time.',
        ]);

        $modes   = $data['support_modes'];
        $virtual = in_array(SupportMode::Virtual->value, $modes);

        if ($virtual && blank($data['meeting_link'] ?? null)) {
            return back()->withInput()->withErrors([
                'meeting_link' => 'Please enter your virtual meeting link.',
            ]);
        }

        // "Python, Exam Prep, Resume Review" -> one skill per row in the skills table
        $skillNames = collect(explode(',', $data['skills'] ?? ''))
            ->map(fn ($skill) => trim($skill))
            ->filter()
            ->unique(fn ($skill) => mb_strtolower($skill))
            ->values();

        if ($skillNames->contains(fn ($skill) => mb_strlen($skill) > 100)) {
            return back()->withInput()->withErrors([
                'skills' => 'Each skill must be 100 characters or fewer.',
            ]);
        }

        $user = $request->user();

        DB::transaction(function () use ($user, $data, $modes, $virtual, $skillNames) {
            $profile = $user->volunteerProfile ?? new VolunteerProfile();

            $profile->forceFill([
                'user_id'          => $user->id,
                'supports_f2f'     => in_array(SupportMode::F2F->value, $modes),
                'supports_virtual' => $virtual,
                'meeting_link'     => $virtual ? $data['meeting_link'] : null,
            ])->save();

            // Categories: pivot table
            $profile->categories()->sync($data['categories']);

            // Skills: replace the old list with the new one
            $profile->skills()->delete();
            foreach ($skillNames as $name) {
                $profile->skills()->forceCreate(['name' => $name]);
            }

            // Availability: replace the old slots with the new ones
            $profile->availabilities()->delete();
            foreach ($data['availability'] as $slot) {
                $profile->availabilities()->forceCreate([
                    'day_of_week' => (int) $slot['day'],
                    'start_time'  => $slot['start'],
                    'end_time'    => $slot['end'],
                ]);
            }
        });

        return redirect()->route('dashboard')->with('status', 'Your volunteer profile has been saved.');
    }
}