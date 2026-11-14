<?php

namespace App\Observers;

use App\Field;

class FieldObserver
{
    /**
     * Handle the "saving" event.
     *
     * @param \App\Field $model
     * @return mixed
     */
    public function saving(Field $model)
    {
        if ($model->isDirty('data') && $model->getNameHandler() instanceof \App\Handlers\Models\Field\Name\NormalizeInterface) {
            $model->data = $model->getNameHandler()->normalizeData($model);
        }
    }

    /**
     * Handle the "creating" event.
     *
     * @param \App\Field $model
     * @return mixed
     */
    public function creating(Field $model)
    {

    }

    /**
     * Handle the "updating" event.
     *
     * @param \App\Field $model
     * @return mixed
     */
    public function updating(Field $model)
    {

    }

    /**
     * Handle the "created" event.
     *
     * @param \App\Field $model
     * @return void
     */
    public function created(Field $model)
    {

    }

    /**
     * Handle the "updated" event.
     *
     * @param \App\Field $model
     * @return void
     */
    public function updated(Field $model)
    {

    }

    /**
     * Handle the "saved" event.
     *
     * @param \App\Field $model
     * @return void
     */
    public function saved(Field $model)
    {

    }

    /**
     * Handle the "deleting" event.
     *
     * @param \App\Field $model
     * @return mixed
     */
    public function deleting(Field $model)
    {
        // FileVirtuals
        foreach ($model->fileVirtuals()->get() as $item) {
            $item->delete();
        }
    }

    /**
     * Handle the "deleted" event.
     *
     * @param \App\Field $model
     * @return void
     */
    public function deleted(Field $model)
    {

    }
}
