<?php

namespace App\Notifications\Trigger;

use Illuminate\Notifications\Messages\MailMessage;

class UserLoggedInUniqueIpNotification extends \AnourValar\EloquentNotification\AbstractNotification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(public \App\Journal $journal)
    {
        parent::__construct();
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject(trans('eloquent_notification::user_notification.trigger_mail.user_logged_in_unique_ip'))
            ->markdown(
                'notifications.trigger_mail.user_logged_in_unique_ip',
                ['user' => $notifiable, 'journal' => $this->journal]
            );
    }

    /**
     * Get the telegram representation of the notification.
     */
    public function toTelegram(object $notifiable)
    {
        return $this->markdown(view(
            'notifications.trigger_telegram.user_logged_in_unique_ip',
            ['user' => $notifiable, 'journal' => $this->journal]
        ));
    }

    /**
     * Get the sms representation of the notification.
     */
    public function toSms(object $notifiable)
    {
        return view(
            'notifications.trigger_sms.user_logged_in_unique_ip',
            ['user' => $notifiable, 'journal' => $this->journal]
        );
    }

    /**
     * Get the push representation of the notification.
     */
    public function toPush(object $notifiable): array
    {
        return [
            'title' => trans('eloquent_notification::user_notification.trigger_push.user_logged_in_unique_ip'),
            'body' => view(
                'notifications.trigger_push.user_logged_in_unique_ip',
                ['user' => $notifiable, 'journal' => $this->journal]
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
                'notifications.trigger_array.user_logged_in_unique_ip',
                ['user' => $notifiable, 'journal' => $this->journal]
            )->render(),
            'user_ip' => $this->journal->ip_address,
        ];
    }
}
