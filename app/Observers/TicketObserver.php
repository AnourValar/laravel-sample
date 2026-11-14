<?php

namespace App\Observers;

use App\Ticket;

class TicketObserver
{
    /**
     * Handle the "saving" event.
     *
     * @param \App\Ticket $model
     * @return mixed
     */
    public function saving(Ticket $model)
    {
        // closed_at
        if ($model->isDirty('status') && in_array($model->status_details['flow'], ['finish_success', 'finish_failure'])) {
            $model->closed_at ??= now();
        }
    }

    /**
     * Handle the "creating" event.
     *
     * @param \App\Ticket $model
     * @return mixed
     */
    public function creating(Ticket $model)
    {

    }

    /**
     * Handle the "updating" event.
     *
     * @param \App\Ticket $model
     * @return mixed
     */
    public function updating(Ticket $model)
    {

    }

    /**
     * Handle the "created" event.
     *
     * @param \App\Ticket $model
     * @return void
     */
    public function created(Ticket $model)
    {
        // onCreated
        $model->getTypeHandler()->onCreated($model);
    }

    /**
     * Handle the "updated" event.
     *
     * @param \App\Ticket $model
     * @return void
     */
    public function updated(Ticket $model)
    {

    }

    /**
     * Handle the "saved" event.
     *
     * @param \App\Ticket $model
     * @return void
     */
    public function saved(Ticket $model)
    {
        // onClosed
        if ($model->isDirty('closed_at') && $model->closed_at) {
            $model->getTypeHandler()->onClosed($model);
        }
    }

    /**
     * Handle the "deleting" event.
     *
     * @param \App\Ticket $model
     * @return mixed
     */
    public function deleting(Ticket $model)
    {
        // FileVirtuals
        foreach ($model->fileVirtuals()->get() as $item) {
            $item->delete();
        }
    }

    /**
     * Handle the "deleted" event.
     *
     * @param \App\Ticket $model
     * @return void
     */
    public function deleted(Ticket $model)
    {

    }
}
