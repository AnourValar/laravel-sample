<?php

namespace App\Notifications\Trigger;

use Illuminate\Notifications\Messages\MailMessage;

class UserPasswordUpdatedNotification extends \AnourValar\EloquentNotification\AbstractNotification
{
    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject(trans('eloquent_notification::user_notification.trigger_mail.user_password_updated'))
            ->markdown(
                'notifications.trigger_mail.user_password_updated',
                ['user' => $notifiable]
            );
    }

    /**
     * Get the telegram representation of the notification.
     */
    public function toTelegram(object $notifiable)
    {
        return $this->markdown(view(
            'notifications.trigger_telegram.user_password_updated',
            ['user' => $notifiable]
        ));
    }

    /**
     * Get the sms representation of the notification.
     */
    public function toSms(object $notifiable)
    {
        return view(
            'notifications.trigger_sms.user_password_updated',
            ['user' => $notifiable]
        );
    }

    /**
     * Get the push representation of the notification.
     */
    public function toPush(object $notifiable): array
    {
        return [
            'title' => trans('eloquent_notification::user_notification.trigger_push.user_password_updated'),
            'body' => view(
                'notifications.trigger_push.user_password_updated',
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
                'notifications.trigger_array.user_password_updated',
                ['user' => $notifiable]
            )->render(),
        ];
    }
}
