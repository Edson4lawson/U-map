<?php

namespace App\Policies;

use App\Models\Place;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Auth\Access\HandlesAuthorization;

class PlacePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any places.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view places
        return true;
    }

    /**
     * Determine whether the user can view the place.
     */
    public function view(User $user, Place $place): bool
    {
        // All users can view approved places
        if ($place->status === 'approved') {
            return true;
        }

        // Only owner or moderators can view pending/rejected places
        return $user->id === $place->added_by || $user->can('manage-places');
    }

    /**
     * Determine whether the user can create places.
     */
    public function create(User $user): bool
    {
        // All non-restricted users can create places
        return !$user->is_restricted;
    }

    /**
     * Determine whether the user can update the place.
     */
    public function update(User $user, Place $place): bool
    {
        // Owner can update their own place
        if ($user->id === $place->added_by) {
            return true;
        }

        // Moderators can update any place
        return $user->can('manage-places');
    }

    /**
     * Determine whether the user can delete the place.
     */
    public function delete(User $user, Place $place): bool
    {
        // Owner can delete their own place
        if ($user->id === $place->added_by) {
            return true;
        }

        // Admins can delete any place
        return $user->can('delete-any-content');
    }

    /**
     * Determine whether the user can approve the place.
     */
    public function approve(User $user, Place $place): bool
    {
        return $user->can('manage-places');
    }

    /**
     * Determine whether the user can reject the place.
     */
    public function reject(User $user, Place $place): bool
    {
        return $user->can('manage-places');
    }

    /**
     * Determine whether the user can restore the place.
     */
    public function restore(User $user, Place $place): bool
    {
        return $user->can('delete-any-content');
    }

    /**
     * Determine whether the user can permanently delete the place.
     */
    public function forceDelete(User $user, Place $place): bool
    {
        return $user->can('delete-any-content');
    }
}
