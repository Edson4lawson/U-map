<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Auth\Access\HandlesAuthorization;

class EventPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any events.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view events
        return true;
    }

    /**
     * Determine whether the user can view the event.
     */
    public function view(User $user, Event $event): bool
    {
        // All users can view published events
        if ($event->status === 'published') {
            return true;
        }

        // Only organizer or moderators can view draft/private events
        return $user->id === $event->organizer_id || $user->can('manage-events');
    }

    /**
     * Determine whether the user can create events.
     */
    public function create(User $user): bool
    {
        // All non-restricted users can create events
        return !$user->is_restricted;
    }

    /**
     * Determine whether the user can update the event.
     */
    public function update(User $user, Event $event): bool
    {
        // Organizer can update their own event
        if ($user->id === $event->organizer_id) {
            return true;
        }

        // Moderators can update any event
        return $user->can('manage-events');
    }

    /**
     * Determine whether the user can delete the event.
     */
    public function delete(User $user, Event $event): bool
    {
        // Organizer can delete their own event
        if ($user->id === $event->organizer_id) {
            return true;
        }

        // Admins can delete any event
        return $user->can('delete-any-content');
    }

    /**
     * Determine whether the user can publish the event.
     */
    public function publish(User $user, Event $event): bool
    {
        // Organizer can publish their own event
        if ($user->id === $event->organizer_id) {
            return true;
        }

        // Moderators can publish any event
        return $user->can('manage-events');
    }

    /**
     * Determine whether the user can unpublish the event.
     */
    public function unpublish(User $user, Event $event): bool
    {
        return $this->publish($user, $event);
    }

    /**
     * Determine whether the user can restore the event.
     */
    public function restore(User $user, Event $event): bool
    {
        return $user->can('delete-any-content');
    }

    /**
     * Determine whether the user can permanently delete the event.
     */
    public function forceDelete(User $user, Event $event): bool
    {
        return $user->can('delete-any-content');
    }
}
