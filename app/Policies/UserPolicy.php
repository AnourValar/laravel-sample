<?php

namespace App\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     *
     * @param  \App\User  $user
     * @param  string  $ability
     * @return void|bool
     */
    public function before(User $user, $ability)
    {

    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\User  $user
     * @param  \App\User $model
     * @return mixed
     */
    public function create(?User $user, User $model)
    {
        if ($user?->can('cabinet.user.create')) {
            return true;
        }

        if (
            empty($model->role_details['is_customer'])
            || $model->additional_abilities
            || $model->pid
        ) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can update models.
     *
     * @param  \App\User  $user
     * @param  \App\User $model
     * @return mixed
     */
    public function update(User $user, User $model)
    {
        if ($user->can('cabinet.user.update')) {
            return true;
        }

        if ($model->isDirty('role', 'additional_abilities')) {
            return false;
        }

        return $user->can('cabinet') && $model->id === $user->id;
    }

    /**
     * Determine whether the user can delete models.
     *
     * @param  \App\User  $user
     * @param  \App\User $model
     * @return mixed
     */
    public function delete(User $user, User $model)
    {
        return $user->can('cabinet.user.delete');
    }

    /**
     * Determine whether the user can notify models.
     *
     * @param  \App\User  $user
     * @param  \App\User $model
     * @return mixed
     */
    public function notify(User $user, User $model)
    {
        return $user->can('cabinet.user.notify');
    }
}
