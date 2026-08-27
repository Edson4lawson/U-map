<?php

namespace App\Broadcasting;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatChannel
{
    use InteractsWithSockets, SerializesModels;

    /**
     * Create a new channel instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Authenticate the user's access to the channel.
     */
    public function join(User $user, $userId1, $userId2): bool
    {
        // L'utilisateur peut rejoindre le canal s'il fait partie de la conversation
        return $user->id == $userId1 || $user->id == $userId2;
    }
}
