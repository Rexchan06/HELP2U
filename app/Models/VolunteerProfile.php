<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'bio', 'support_f2f', 'support_virtual', 'meeting_link', 'is_active'])]
class VolunteerProfile extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'support_f2f' => 'boolean',
            'support_virtual' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the user that owns the volunteer profile.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
