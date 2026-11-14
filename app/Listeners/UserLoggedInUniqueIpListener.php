<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UserLoggedInUniqueIpListener
{
    /**
     * Handle the event.
     */
    public function handle(\AnourValar\EloquentJournal\Events\JournalCreated $event): void
    {
        if ($event->journal->event == 'user_token_obtain') {
            $check = \App\Journal::query()
                ->where('user_id', '=', $event->journal->user_id)
                ->where('id', '<', $event->journal->id)
                ->where('event', '=', $event->journal->event)
                ->where('ip_address', '=', $event->journal->ip_address)
                ->first();

            if (! $check) {
                $event->journal->user->notify(new \App\Notifications\Trigger\UserLoggedInUniqueIpNotification($event->journal));
            }
        }
    }
}
