<?php

namespace App\Notifications;

use App\Models\Message;
use App\Services\MessageEncryptionService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class NewMessagePushNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Message $message
    ) {}

    public function via($notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        $sender = $this->message->sender;
        $senderName = $sender?->name ?? 'Nouvel utilisateur';

        // Extract message content safely
        $rawContent = $this->message->content;
        
        // Format snippet
        $body = 'Vous a envoyé un message';
        if (!empty($rawContent)) {
            if (str_starts_with($rawContent, '[POSITION:')) {
                $body = '📍 Vous a partagé sa position en direct';
            } elseif (str_starts_with($rawContent, '[LIEU:')) {
                $body = '🏛️ Vous a partagé un lieu du campus';
            } else {
                $body = mb_substr(strip_tags($rawContent), 0, 100);
            }
        }

        $senderId = $this->message->sender_id;
        $url = '/chat?chat=' . $senderId;

        return (new WebPushMessage)
            ->title("💬 {$senderName}")
            ->body($body)
            ->icon('/pwa-192.png')
            ->badge('/pwa-192.png')
            ->tag("chat-{$senderId}")
            ->data([
                'url' => $url,
                'sender_id' => $senderId,
                'message_id' => $this->message->id,
            ])
            ->options(['TTL' => 86400]); // 24 hours TTL
    }
}
