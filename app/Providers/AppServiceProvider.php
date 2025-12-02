<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Justifications\Observers\JustificationCreatedEvent;
use App\Justifications\Observers\JustificationStatusChangedEvent;
use App\Justifications\Observers\JustificationAuditObserver;
use App\Justifications\Observers\JustificationNotificationObserver;
use Illuminate\Support\Facades\Event;

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
        Event::listen(JustificationCreatedEvent::class, [JustificationNotificationObserver::class, 'handle']);
        Event::listen(JustificationCreatedEvent::class, [JustificationAuditObserver::class, 'handle']);
        Event::listen(JustificationStatusChangedEvent::class, [JustificationNotificationObserver::class, 'handle']);
        Event::listen(JustificationStatusChangedEvent::class, [JustificationAuditObserver::class, 'handle']);
    }
}
