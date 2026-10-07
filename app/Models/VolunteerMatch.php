<?php

namespace App\Models;

use App\Enums\MatchStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class VolunteerMatch extends Model
{
    protected $fillable = [
        'support_request_id', 'volunteer_profile_id', 'status', 'expires_at', 'responded_at',
    ];

    protected $attributes = ['status' => 'PENDING'];

    protected function casts(): array
    {
        return [
            'status' => MatchStatus::class,
            'expires_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    public function supportRequest(): BelongsTo
    {
        return $this->belongsTo(SupportRequest::class);
    }

    public function volunteerProfile(): BelongsTo
    {
        return $this->belongsTo(VolunteerProfile::class);
    }

    public function supportSession(): HasOne
    {
        return $this->hasOne(SupportSession::class);
    }
}
