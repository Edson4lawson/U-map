<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Admin extends Model
{
    protected $fillable = ['username', 'password', 'last_login_at', 'last_login_ip'];

    protected $hidden = ['password'];

    protected $casts = [
        'last_login_at' => 'datetime',
    ];

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }

    public function verifyPassword($password)
    {
        return Hash::check($password, $this->password);
    }
}
