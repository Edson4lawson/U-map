<?php

namespace App\Providers;

use App\Listeners\PruneExpiredPushSubscriptions;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Prune expired or invalid WebPush subscriptions on delivery failure
        Event::listen(NotificationFailed::class, PruneExpiredPushSubscriptions::class);
    }
}
