<?php

namespace App\Listeners;

use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use NotificationChannels\WebPush\PushSubscription;

class PruneExpiredPushSubscriptions
{
    /**
     * Handle the event when a notification fails.
     */
    public function handle(NotificationFailed $event): void
    {
        if ($event->channel !== 'NotificationChannels\WebPush\WebPushChannel') {
            return;
        }

        $data = $event->data;
        $report = $data['report'] ?? null;

        if ($report && method_exists($report, 'isSubscriptionExpired') && $report->isSubscriptionExpired()) {
            $endpoint = $report->getEndpoint();
            Log::info("Deleting expired push subscription: {$endpoint}");
            PushSubscription::where('endpoint', $endpoint)->delete();
            return;
        }

        // If response status is 404 or 410 (Gone)
        if ($report && method_exists($report, 'getResponse') && $report->getResponse()) {
            $statusCode = $report->getResponse()->getStatusCode();
            if (in_array($statusCode, [404, 410])) {
                $endpoint = $report->getEndpoint();
                Log::info("Deleting dead push subscription (HTTP {$statusCode}): {$endpoint}");
                PushSubscription::where('endpoint', $endpoint)->delete();
            }
        }
    }
}
