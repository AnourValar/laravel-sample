<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            //\Illuminate\Auth\Listeners\SendEmailVerificationNotification::class,
            \App\Listeners\UserSeedListener::class,
        ],

        \AnourValar\EloquentNotification\Events\TelegramUsernameBlocked::class => [
            \App\Listeners\TelegramUsernameCleanerListener::class,
        ],

        \AnourValar\EloquentJournal\Events\JournalCreated::class => [
            \App\Listeners\UserLoggedInUniqueIpListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // Персональные
        \App\Directory::observe(\App\Observers\DirectoryObserver::class);
        \App\Field::observe(\App\Observers\FieldObserver::class);
        \App\Ticket::observe(\App\Observers\TicketObserver::class);
        \App\User::observe(\App\Observers\UserObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
