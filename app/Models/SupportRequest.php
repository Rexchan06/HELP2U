<?php

namespace App\Models;

use App\Enums\RequestStatus;
use App\Enums\SupportMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportRequest extends Model
{
    protected $fillable = [
        'user_id', 'category_id', 'description', 'support_mode', 'preferred_datetime', 'status',
    ];

    protected $attributes = ['status' => 'OPEN'];

    protected function casts(): array
    {
        return [
            'support_mode' => SupportMode::class,
            'status' => RequestStatus::class,
            'preferred_datetime' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function volunteerMatches(): HasMany
    {
        return $this->hasMany(VolunteerMatch::class);
    }
}
