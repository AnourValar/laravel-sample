<?php

namespace App\Policies;

use App\Ticket;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class TicketPolicy
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
     * @param  \App\Ticket  $ticket
     * @return mixed
     */
    public function view(User $user, Ticket $ticket)
    {
        //
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\User|null  $user
     * @param  \App\Ticket  $ticket
     * @return mixed
     */
    public function create(?User $user, Ticket $ticket)
    {
        if (empty($ticket->type_details['is_manual'])) {
            return false;
        }

        if (
            ! empty($ticket->type_details['is_singleton'])
            && \App\Ticket::query()
                ->where('type', '=', $ticket->type)
                ->where('author_id', '=', $ticket->author_id)
                ->whereNull('closed_at')
                ->first()
        ) {
            return Response::deny(trans('policies/ticket.create_singleton_exists'));
        }

        if ($ticket->author_id === null) {
            return true;
        }

        if ($user?->can('cabinet.ticket.create')) {
            return true;
        }

        return $user?->can('cabinet') && $user->id === $ticket->author_id;
    }

    /**
     * Determine whether the user can create-upload models.
     *
     * @param  \App\User  $user
     * @param  \App\Ticket  $ticket
     * @return mixed
     */
    public function createUpload(User $user, Ticket $ticket)
    {
        if (now()->diffInMinutes($ticket->created_at, true) > 2) {
            return Response::deny(trans('policies/ticket.create_upload_expired'));
        }

        if ($user->can('cabinet.ticket.create')) {
            return true;
        }

        return $user->can('cabinet') && $user->id === $ticket->author_id;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\User  $user
     * @param  \App\Ticket  $ticket
     * @return mixed
     */
    public function update(User $user, Ticket $ticket)
    {
        if ($ticket->isDirty('data_response') && $ticket->data_response && ! $ticket->moderator_id) {
            return Response::deny(trans('policies/ticket.update_data_response_without_moderator'));
        }

        if ($ticket->closed_at && now()->diffInMinutes($ticket->closed_at, true) > 2) {
            return Response::deny(trans('policies/ticket.update_expired'));
        }

        return $user->can('cabinet.ticket.update');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\User  $user
     * @param  \App\Ticket  $ticket
     * @return mixed
     */
    public function delete(User $user, Ticket $ticket)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\User  $user
     * @param  \App\Ticket  $ticket
     * @return mixed
     */
    public function restore(User $user, Ticket $ticket)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\User  $user
     * @param  \App\Ticket  $ticket
     * @return mixed
     */
    public function forceDelete(User $user, Ticket $ticket)
    {
        //
    }
}
