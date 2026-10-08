<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Availability extends Model
{
    /**
     * day_of_week follows Carbon's convention: 0 = Sunday … 6 = Saturday.
     */
    public const DAYS = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ];

    /**
     * Filter option order, starting the week on Monday.
     *
     * @var array<int, int>
     */
    public const DAY_ORDER = [1, 2, 3, 4, 5, 6, 0];

    protected $fillable = ['volunteer_profile_id', 'day_of_week', 'start_time', 'end_time'];

    protected function casts(): array
    {
        return ['day_of_week' => 'integer'];
    }

    public function volunteerProfile(): BelongsTo
    {
        return $this->belongsTo(VolunteerProfile::class);
    }

    /**
     * Formatted time range, e.g. "2:00 PM - 5:00 PM".
     */
    public function timeRange(): string
    {
        return Carbon::parse($this->start_time)->format('g:i A')
            .' - '.Carbon::parse($this->end_time)->format('g:i A');
    }
}
