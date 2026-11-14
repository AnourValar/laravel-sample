<?php

namespace App\Observers;

use AnourValar\EloquentNotification\Adapters\Telegram\TelegramInterface;
use App\User;

class UserObserver
{
    /**
     * Handle the "saving" event.
     *
     * @param  \App\User  $model
     * @return mixed
     */
    public function saving(User $model)
    {
        // search
        if ($model->isDirty('email', 'phone', 'first_name', 'last_name', 'middle_name', 'role', 'role_data')) {
            $model->search = $this->getSearch($model);
        }

        // telegram_chatid
        if ($model->isDirty('telegram_username') && $model->telegram_username) {
            $model->telegram_chatid = \App::make(TelegramInterface::class)->fetchChatId($model->telegram_username);
            if (! $model->telegram_chatid) {
                $model->telegram_username = null;
            }
        }
    }

    /**
     * Handle the "creating" event.
     *
     * @param  \App\User  $model
     * @return mixed
     */
    public function creating(User $model)
    {

    }

    /**
     * Handle the "updating" event.
     *
     * @param  \App\User  $model
     * @return mixed
     */
    public function updating(User $model)
    {

    }

    /**
     * Handle the "created" event.
     *
     * @param  \App\User  $model
     * @return void
     */
    public function created(User $model)
    {

    }

    /**
     * Handle the "updated" event.
     *
     * @param  \App\User  $model
     * @return void
     */
    public function updated(User $model)
    {
        // trigger?
        if ($model->isDirty('email')) {
            $model->notify(new \App\Notifications\Trigger\UserEmailUpdatedNotification());
        }

        // trigger?
        if ($model->isDirty('phone')) {
            $model->notify(new \App\Notifications\Trigger\UserPhoneUpdatedNotification());
        }

        // trigger?
        if ($model->isDirty('password')) {
            $model->notify(new \App\Notifications\Trigger\UserPasswordUpdatedNotification());
        }
    }

    /**
     * Handle the "saved" event.
     *
     * @param  \App\User  $model
     * @return void
     */
    public function saved(User $model)
    {
        // role_data
        if ($model->isDirty('role', 'role_data')) {
            $model->getRoleHandler()->onChanged($model);
        }
    }

    /**
     * Handle the "deleting" event.
     *
     * @param  \App\User  $model
     * @return mixed
     */
    public function deleting(User $model)
    {

    }

    /**
     * Handle the "deleted" event.
     *
     * @param  \App\User  $model
     * @return void
     */
    public function deleted(User $model)
    {

    }

    /**
     * @param \App\User $user
     * @return string
     */
    private function getSearch(\App\User $user): string
    {
        return \EloquentRequestSearch::generate(
            array_merge(
                [$user->email, $user->phone, $user->first_name, $user->last_name, $user->middle_name, $user->telegram_username],
                $user->getRoleHandler()->search($user)
            )
        );
    }
}
