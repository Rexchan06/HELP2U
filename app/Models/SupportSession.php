<?php

namespace App\Models;

use App\Enums\SessionStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SupportSession extends Model
{
    protected $fillable = [
        'volunteer_match_id', 'scheduled_start', 'duration_minutes', 'status',
        'location', 'notes', 'completed_at', 'cancelled_at', 'cancelled_by',
    ];

    protected $attributes = ['status' => 'SCHEDULED'];

    protected function casts(): array
    {
        return [
            'status' => SessionStatus::class,
            'scheduled_start' => 'datetime',
            'duration_minutes' => 'integer',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected function supportMode(): Attribute
    {
        return Attribute::get(fn () => $this->volunteerMatch->supportRequest->support_mode);
    }

    public function volunteerMatch(): BelongsTo
    {
        return $this->belongsTo(VolunteerMatch::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function feedback(): HasOne
    {
        return $this->hasOne(Feedback::class);
    }
}
