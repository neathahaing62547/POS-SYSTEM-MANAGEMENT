<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use App\Models\Role;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasFactory;

    protected $fillable = [
        'username',
        'full_name',
        'email',
        'phone',
        'password_hash',
        'status',
        'last_login_at',
    ];
    protected $hidden = [
        'password_hash',
    ];
    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
        ];
    }
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }
    public function orders()
    {
        return $this->hasMany(Order::class, 'cashier_id');
    }
    public function salesReturns()
    {
        return $this->hasMany(SalesReturn::class);
    }
    public function userShifts()
    {
        return $this->hasMany(UserShift::class, 'user_id');
    }
    public function passwordRecoveryOtps()
    {
        return $this->hasMany(PasswordRecoveryOtp::class);
    }
}
