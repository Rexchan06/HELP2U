<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Skill extends Model
{
    protected $fillable = ['volunteer_profile_id', 'name'];

    protected function name(): Attribute
    {
        return Attribute::set(fn (string $value) => Str::lower(trim($value)));
    }

    public function volunteerProfile(): BelongsTo
    {
        return $this->belongsTo(VolunteerProfile::class);
    }
}
