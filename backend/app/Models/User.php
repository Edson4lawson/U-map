<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use NotificationChannels\WebPush\HasPushSubscriptions;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasPushSubscriptions;

    protected $fillable = [
        'name',
        'email',
        'password',
        'study_status',
        'study_location',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'social_provider',
        'social_id',
        'avatar',
        'faculty',
        'study_level',
        'student_id',
        'is_restricted',
        'muted_until',
        'suspended_until',
        'is_banned',
        'admin_note',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'muted_until' => 'datetime',
            'suspended_until' => 'datetime',
            'password' => 'hashed',
            'is_restricted' => 'boolean',
            'is_banned' => 'boolean',
            'role' => UserRole::class,
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role?->isAdmin() ?? false;
    }

    public function isMuted(): bool
    {
        return $this->muted_until && $this->muted_until->isFuture();
    }

    public function isSuspended(): bool
    {
        return $this->suspended_until && $this->suspended_until->isFuture();
    }

    public function isBanned(): bool
    {
        return (bool) $this->is_banned;
    }

    public function sanctions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserSanction::class)->orderByDesc('created_at');
    }

    public function reportsReceived(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Report::class, 'reported_user_id');
    }

    public function reportsSent(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    public function devices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserDevice::class);
    }
}
