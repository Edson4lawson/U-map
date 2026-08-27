<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        // Broadcast sur un canal privé pour la conversation spécifique, et sur le canal de l'utilisateur destinataire
        return [
            new PrivateChannel('chat.' . min($this->message->sender_id, $this->message->receiver_id) . '.' . max($this->message->sender_id, $this->message->receiver_id)),
            new PrivateChannel('user.' . $this->message->receiver_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * Données à broadcaster
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'content' => $this->message->decrypted_content,
            'sender_id' => $this->message->sender_id,
            'receiver_id' => $this->message->receiver_id,
            'created_at' => $this->message->created_at,
            'sender' => [
                'id' => $this->message->sender->id,
                'name' => $this->message->sender->name,
            ],
        ];
    }
}
