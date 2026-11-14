<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UserSeedListener
{
    /**
     * Handle the event.
     *
     * @throws \AnourValar\LaravelAtom\Exceptions\InternalValidationException
     */
    public function handle(\Illuminate\Auth\Events\Registered $event): void
    {
        // Клиент?
        if (empty($event->user->role_details['is_customer'])) {
            return;
        }

        try {
            // Уведомления
            $this->seedUserNotifications($event->user);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw \AnourValar\LaravelAtom\Exceptions\InternalValidationException::fromValidationException($e);
        }
    }

    /**
     * @param \App\User $user
     * @return void
     */
    private function seedUserNotifications(\App\User $user): void
    {
        foreach (config('eloquent_notification.trigger') as $trigger => $details) {
            $data = [
                'user_id' => $user->id,
                'trigger' => $trigger,
                'channels' => $details['is_public'] ? ['database'] : $details['channels'],
            ];

            \App\UserNotification::fields(array_keys($data))->fill($data)->validate(basic: mt_rand(0, 100))->save();
        }
    }
}
