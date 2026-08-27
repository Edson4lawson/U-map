<?php

namespace App\Policies;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        // Only moderators and above can list users
        return $user->can('manage-users');
    }

    /**
     * Determine whether the user can view the user.
     */
    public function view(User $user, User $target): bool
    {
        // Users can view their own profile
        if ($user->id === $target->id) {
            return true;
        }

        // Moderators can view any user
        return $user->can('manage-users');
    }

    /**
     * Determine whether the user can create users.
     */
    public function create(User $user): bool
    {
        // Only admins can create users manually
        return $user->can('manage-users');
    }

    /**
     * Determine whether the user can update the user.
     */
    public function update(User $user, User $target): bool
    {
        // Users can update their own profile
        if ($user->id === $target->id) {
            // Cannot change own role
            return true;
        }

        // Admins can update other users
        if ($user->can('manage-users')) {
            // Check role hierarchy
            $userRole = UserRole::from($user->role);
            $targetRole = UserRole::from($target->role);
            return $userRole->canManage($targetRole);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the user.
     */
    public function delete(User $user, User $target): bool
    {
        // Cannot delete self
        if ($user->id === $target->id) {
            return false;
        }

        // Only admins can delete users
        if (!$user->can('manage-users')) {
            return false;
        }

        // Check role hierarchy
        $userRole = UserRole::from($user->role);
        $targetRole = UserRole::from($target->role);
        return $userRole->canManage($targetRole);
    }

    /**
     * Determine whether the user can change user role.
     */
    public function changeRole(User $user, User $target, UserRole $newRole): bool
    {
        // Only admins can change roles
        if (!$user->can('manage-users')) {
            return false;
        }

        // Cannot change own role
        if ($user->id === $target->id) {
            return false;
        }

        // Check role hierarchy
        $userRole = UserRole::from($user->role);
        $targetRole = UserRole::from($target->role);

        // Can only assign roles lower than own
        if ($userRole->level() <= $newRole->level()) {
            return false;
        }

        // Can only manage users with lower role
        return $userRole->canManage($targetRole);
    }

    /**
     * Determine whether the user can restrict/unrestrict another user.
     */
    public function restrict(User $user, User $target): bool
    {
        // Cannot restrict self
        if ($user->id === $target->id) {
            return false;
        }

        // Moderators can restrict users
        return $user->can('manage-users');
    }

    /**
     * Determine whether the user can impersonate another user.
     */
    public function impersonate(User $user, User $target): bool
    {
        // Only super admin can impersonate
        if (!$user->can('impersonate-users')) {
            return false;
        }

        // Cannot impersonate self
        if ($user->id === $target->id) {
            return false;
        }

        // Cannot impersonate other super admins
        if ($target->role === UserRole::SUPER_ADMIN->value) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can restore the user.
     */
    public function restore(User $user, User $target): bool
    {
        return $user->can('manage-users');
    }

    /**
     * Determine whether the user can permanently delete the user.
     */
    public function forceDelete(User $user, User $target): bool
    {
        // Only super admin can force delete users
        if ($user->role !== UserRole::SUPER_ADMIN->value) {
            return false;
        }

        // Cannot delete self
        if ($user->id === $target->id) {
            return false;
        }

        // Cannot delete other super admins
        if ($target->role === UserRole::SUPER_ADMIN->value) {
            return false;
        }

        return true;
    }
}
