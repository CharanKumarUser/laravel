<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Whether Laravel should auto-discover events & listeners.
     * Enables automatic updates to job and event mappings.
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * The event to listener mappings for the application.
     *
     * You can still define manual event-listener pairs here if needed.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // Example:
        // \App\Events\UserRegistered::class => [
        //     \App\Listeners\SendWelcomeEmail::class,
        // ],
    ];

    /**
     * Boot the event service provider.
     */
    public function boot(): void
    {
        parent::boot();

        // Optionally log discovered events for debugging
        // Event::listen('*', function ($eventName, array $data) {
        //     \Log::info("Event fired: {$eventName}");
        // });
    }

    /**
     * Define the directories Laravel should scan to auto-discover events,
     * listeners, observers, and jobs.
     */
    public function discoverEventsWithin(): array
    {
        return [
            app_path('Listeners'),
            app_path('Observers'),
            app_path('Events'),
            app_path('Jobs'),
        ];
    }
}
