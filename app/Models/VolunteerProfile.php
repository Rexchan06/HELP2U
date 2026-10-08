<?php

namespace App\Models;

use Database\Factories\VolunteerProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VolunteerProfile extends Model
{
    /** @use HasFactory<VolunteerProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id', 'bio', 'supports_f2f', 'supports_virtual', 'meeting_link', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'supports_f2f' => 'boolean',
            'supports_virtual' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function skills(): HasMany
    {
        return $this->hasMany(Skill::class);
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(Availability::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function volunteerMatches(): HasMany
    {
        return $this->hasMany(VolunteerMatch::class);
    }
    /**
     * Human-readable support mode, e.g. "Face-to-face & Virtual".
     */
    public function supportModeLabel(): string
    {
        return match (true) {
            $this->supports_f2f && $this->supports_virtual => 'Face-to-face & Virtual',
            $this->supports_f2f => 'Face-to-face Only',
            $this->supports_virtual => 'Virtual Only',
            default => 'Not specified',
        };
    }

    /**
     * Unique days the volunteer is available on, ordered Monday first.
     *
     * @return array<int, string> e.g. ['Mon', 'Wed', 'Fri']
     */
    public function availabilityDays(): array
    {
        return $this->availabilities
            ->pluck('day_of_week')
            ->unique()
            ->sortBy(fn (int $day) => array_search($day, Availability::DAY_ORDER, true))
            ->map(fn (int $day) => substr(Availability::DAYS[$day] ?? '?', 0, 3))
            ->values()
            ->all();
    }

    /**
     * Human availability summary grouped by time of day, e.g.
     * "Mon, Wed, Fri afternoons".
     */
    public function availabilitySummary(): string
    {
        if ($this->availabilities->isEmpty()) {
            return '';
        }

        $groups = $this->availabilities
            ->groupBy(fn (Availability $a) => $this->timeOfDay($a->start_time))
            ->map(function ($items, string $period) {
                $days = $items->pluck('day_of_week')->unique()->sortBy(
                    fn (int $day) => array_search($day, Availability::DAY_ORDER, true)
                );

                $dayLabels = $days->map(fn (int $day) => substr(Availability::DAYS[$day] ?? '?', 0, 3));

                return $dayLabels->implode(', ').' '.$period;
            })
            ->values()
            ->implode('; ');

        return 'Available '.$groups;
    }

    /**
     * Availability slots for the profile page: one row per day with the
     * day's time range(s), ordered Monday first.
     *
     * @return \Illuminate\Support\Collection<int, array{day: string, time: string}>
     */
    public function availabilitySlots(): \Illuminate\Support\Collection
    {
        return $this->availabilities
            ->sortBy(fn (Availability $a) => array_search($a->day_of_week, Availability::DAY_ORDER, true))
            ->groupBy('day_of_week')
            ->map(fn ($slots, int $day) => [
                'day' => Availability::DAYS[$day] ?? 'Unknown',
                'time' => $slots->map->timeRange()->implode(', '),
            ])
            ->values();
    }

    private function timeOfDay(string $time): string
    {
        $hour = (int) substr($time, 0, 2);

        return match (true) {
            $hour < 12 => 'mornings',
            $hour < 17 => 'afternoons',
            default => 'evenings',
        };
    }

    public function averageRating(): ?float
    {
        $avg = Feedback::whereHas(
            'supportSession.volunteerMatch',
            fn ($q) => $q->where('volunteer_profile_id', $this->id)
        )->avg('rating');

        return $avg === null ? null : round((float) $avg, 2);
    }
}
