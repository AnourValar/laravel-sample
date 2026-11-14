<?php

namespace App\Policies;

use App\Directory;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class DirectoryPolicy
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
     * @param  \App\Directory  $directory
     * @return mixed
     */
    public function view(User $user, Directory $directory)
    {
        //
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\User  $user
     * @param  \App\Directory  $directory
     * @return mixed
     */
    public function create(User $user, Directory $directory)
    {
        if (empty($directory->type_details['is_manually'])) {
            return false;
        }

        if ($user->can('cabinet.directory.create')) {
            return true;
        }

        return $user->can('cabinet') && $user->id === $directory->user_id;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\User  $user
     * @param  \App\Directory  $directory
     * @return mixed
     */
    public function update(User $user, Directory $directory)
    {
        if (empty($directory->type_details['is_manually'])) {
            return false;
        }

        if ($user->can('cabinet.directory.update')) {
            return true;
        }

        return $user->can('cabinet') && $user->id === $directory->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\User  $user
     * @param  \App\Directory  $directory
     * @return mixed
     */
    public function delete(User $user, Directory $directory)
    {
        if (empty($directory->type_details['is_manually'])) {
            return false;
        }

        if ($user->can('cabinet.directory.delete')) {
            return true;
        }

        return $user->can('cabinet') && $user->id === $directory->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\User  $user
     * @param  \App\Directory  $directory
     * @return mixed
     */
    public function restore(User $user, Directory $directory)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\User  $user
     * @param  \App\Directory  $directory
     * @return mixed
     */
    public function forceDelete(User $user, Directory $directory)
    {
        //
    }
}
