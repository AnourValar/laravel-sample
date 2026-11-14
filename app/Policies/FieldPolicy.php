<?php

namespace App\Policies;

use App\Field;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class FieldPolicy
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
     * Determine whether the user can view any models.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function viewAny(User $user)
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\User  $user
     * @param  \App\Field  $field
     * @return mixed
     */
    public function view(User $user, Field $field)
    {
        if ($user->can('cabinet.field.read')) {
            return true;
        }

        return $user->can('cabinet') && $user->id === $field->user_id;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\User  $user
     * @param  \App\Field  $field
     * @return mixed
     */
    public function create(User $user, Field $field)
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\User  $user
     * @param  \App\Field  $field
     * @return mixed
     */
    public function update(User $user, Field $field)
    {
        return $user->can('cabinet.field.update');
    }

    /**
     * Determine whether the user can save the model.
     *
     * @param  \App\User  $user
     * @param  \App\Field  $field
     * @return mixed
     */
    public function save(User $user, Field $field)
    {
        if (! $user->can('cabinet')) {
            return false;
        }

        if (! empty($field->name_details['is_readonly'])) {
            return false;
        }

        if ($user->id !== $field->user_id) {
            return false;
        }

        if ($field->status_details['flow'] != 'draft') {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\User  $user
     * @param  \App\Field  $field
     * @return mixed
     */
    public function delete(User $user, Field $field)
    {
        return $this->save($user, $field);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\User  $user
     * @param  \App\Field  $field
     * @return mixed
     */
    public function restore(User $user, Field $field)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\User  $user
     * @param  \App\Field  $field
     * @return mixed
     */
    public function forceDelete(User $user, Field $field)
    {
        //
    }
}
