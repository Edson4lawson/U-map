<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class UserWarningNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $reason
    ) {}

    public function via($notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('⚠️ Avertissement de Modération - U-Map')
            ->body("Un administrateur vous a adressé un avertissement : {$this->reason}")
            ->icon('/pwa-192.png')
            ->badge('/pwa-192.png')
            ->tag('moderation-warning')
            ->data(['url' => '/'])
            ->options(['TTL' => 86400]);
    }
}
