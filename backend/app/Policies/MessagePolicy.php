<?php

namespace App\Policies;

use App\Models\Message;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Auth\Access\HandlesAuthorization;

class MessagePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any messages.
     */
    public function viewAny(User $user): bool
    {
        // Users can only view their own conversations
        return true;
    }

    /**
     * Determine whether the user can view the message.
     */
    public function view(User $user, Message $message): bool
    {
        // Zero Trust: Only sender or receiver can view
        return $user->id === $message->sender_id || $user->id === $message->receiver_id;
    }

    /**
     * Determine whether the user can create messages.
     */
    public function create(User $user): bool
    {
        // All authenticated users can send messages
        // Additional checks in controller (rate limiting, restricted users)
        return !$user->is_restricted;
    }

    /**
     * Determine whether the user can update the message.
     */
    public function update(User $user, Message $message): bool
    {
        // Messages are immutable - no updates allowed
        return false;
    }

    /**
     * Determine whether the user can delete the message.
     */
    public function delete(User $user, Message $message): bool
    {
        // Only sender can delete their own messages
        // Admins can delete any message via gate
        return $user->id === $message->sender_id || $user->can('delete-any-content');
    }

    /**
     * Determine whether the user can restore the message.
     */
    public function restore(User $user, Message $message): bool
    {
        // No restore for ephemeral messages
        return false;
    }

    /**
     * Determine whether the user can permanently delete the message.
     */
    public function forceDelete(User $user, Message $message): bool
    {
        // Only admins can force delete
        return $user->can('delete-any-content');
    }

    /**
     * Determine whether the user can view conversation with another user.
     */
    public function viewConversation(User $user, int $otherUserId): bool
    {
        // Cannot view conversation with restricted user unless admin
        $otherUser = User::find($otherUserId);
        if (!$otherUser) {
            return false;
        }

        if ($otherUser->is_restricted && !$user->can('manage-users')) {
            return false;
        }

        return true;
    }
}
