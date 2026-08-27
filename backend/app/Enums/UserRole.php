<?php

namespace App\Enums;

enum UserRole: string
{
    case USER = 'user';
    case MODERATOR = 'moderator';
    case ADMIN = 'admin';
    case SUPER_ADMIN = 'super_admin';

    /**
     * Get all roles as array
     */
    public static function all(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    /**
     * Check if role is admin or higher
     */
    public function isAdmin(): bool
    {
        return in_array($this, [self::ADMIN, self::SUPER_ADMIN]);
    }

    /**
     * Check if role is moderator or higher
     */
    public function isModeratorOrHigher(): bool
    {
        return in_array($this, [self::MODERATOR, self::ADMIN, self::SUPER_ADMIN]);
    }

    /**
     * Get role hierarchy level
     */
    public function level(): int
    {
        return match($this) {
            self::USER => 1,
            self::MODERATOR => 2,
            self::ADMIN => 3,
            self::SUPER_ADMIN => 4,
        };
    }

    /**
     * Check if this role can manage target role
     */
    public function canManage(UserRole $target): bool
    {
        return $this->level() > $target->level();
    }
}
