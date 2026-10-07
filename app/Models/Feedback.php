<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    protected $fillable = [
        'support_session_id', 'user_id', 'rating', 'comment', 'share_with_volunteer',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'share_with_volunteer' => 'boolean',
        ];
    }

    public function supportSession(): BelongsTo
    {
        return $this->belongsTo(SupportSession::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
