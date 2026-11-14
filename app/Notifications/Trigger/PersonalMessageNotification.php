<?php

namespace App\Notifications\Trigger;

class PersonalMessageNotification extends \AnourValar\EloquentNotification\AbstractNotification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(public string $message)
    {
        parent::__construct();
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'plaintext' => $this->message,
        ];
    }
}
