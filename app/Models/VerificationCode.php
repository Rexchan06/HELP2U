<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VerificationCode extends Model
{
    protected $fillable = ['user_id', 'purpose', 'code', 'expires_at'];
    protected $casts = ['expires_at' => 'datetime'];
}