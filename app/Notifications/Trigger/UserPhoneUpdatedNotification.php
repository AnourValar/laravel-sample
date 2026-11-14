<?php

namespace App\Notifications\Trigger;

use Illuminate\Notifications\Messages\MailMessage;

class UserPhoneUpdatedNotification extends \AnourValar\EloquentNotification\AbstractNotification
{
    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject(trans('eloquent_notification::user_notification.trigger_mail.user_phone_updated'))
            ->markdown(
                'notifications.trigger_mail.user_phone_updated',
                ['user' => $notifiable]
            );
    }

    /**
     * Get the telegram representation of the notification.
     */
    public function toTelegram(object $notifiable)
    {
        return $this->markdown(view(
            'notifications.trigger_telegram.user_phone_updated',
            ['user' => $notifiable]
        ));
    }

    /**
     * Get the sms representation of the notification.
     */
    public function toSms(object $notifiable)
    {
        return view(
            'notifications.trigger_sms.user_phone_updated',
            ['user' => $notifiable]
        );
    }

    /**
     * Get the push representation of the notification.
     */
    public function toPush(object $notifiable): array
    {
        return [
            'title' => trans('eloquent_notification::user_notification.trigger_push.user_phone_updated'),
            'body' => view(
                'notifications.trigger_push.user_phone_updated',
                ['user' => $notifiable]
            ),
            'data' => [],
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'plaintext' => view(
                'notifications.trigger_array.user_phone_updated',
                ['user' => $notifiable]
            )->render(),
            'user_phone' => $notifiable->phone,
        ];
    }
}
