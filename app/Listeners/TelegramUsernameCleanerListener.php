<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class TelegramUsernameCleanerListener
{
    /**
     * Handle the event.
     */
    public function handle(\AnourValar\EloquentNotification\Events\TelegramUsernameBlocked $event): void
    {
        \DB::transaction(function () use ($event) {
            \Atom::lockUser($event->user->id, 'user');

            \App\User::where('id', '=', $event->user->id)->update(['telegram_username' => null, 'telegram_chatid' => null]);
        });
    }
}
