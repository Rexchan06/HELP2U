<?php

namespace App\Models;

use App\Enums\CodePurpose;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TwoFactorCode extends Model
{
    protected $fillable = [
        'user_id', 'code_hash', 'purpose', 'attempts', 'expires_at', 'used_at',
    ];

    protected $hidden = ['code_hash'];

    protected function casts(): array
    {
        return [
            'purpose' => CodePurpose::class,
            'attempts' => 'integer',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
