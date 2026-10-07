<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VolunteerProfile extends Model
{
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
    public function averageRating(): ?float
    {
        $avg = Feedback::whereHas(
            'supportSession.volunteerMatch',
            fn ($q) => $q->where('volunteer_profile_id', $this->id)
        )->avg('rating');

        return $avg === null ? null : round((float) $avg, 2);
    }
}
