<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordRecoveryOtp extends Model
{
    protected $fillable = [
        'user_id',
        'email',
        'otp',
        'type',
        'expires_at',
        'verified_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
