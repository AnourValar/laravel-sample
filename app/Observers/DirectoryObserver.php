<?php

namespace App\Observers;

use App\Directory;

class DirectoryObserver
{
    /**
     * Handle the "saving" event.
     *
     * @param \App\Directory $model
     * @return mixed
     */
    public function saving(Directory $model)
    {
        // search
        if ($model->isDirty('type', 'data')) {
            $model->search = $this->getSearch($model);
        }
    }

    /**
     * Handle the "creating" event.
     *
     * @param \App\Directory $model
     * @return mixed
     */
    public function creating(Directory $model)
    {

    }

    /**
     * Handle the "updating" event.
     *
     * @param \App\Directory $model
     * @return mixed
     */
    public function updating(Directory $model)
    {

    }

    /**
     * Handle the "created" event.
     *
     * @param \App\Directory $model
     * @return void
     */
    public function created(Directory $model)
    {

    }

    /**
     * Handle the "updated" event.
     *
     * @param \App\Directory $model
     * @return void
     */
    public function updated(Directory $model)
    {

    }

    /**
     * Handle the "saved" event.
     *
     * @param \App\Directory $model
     * @return void
     */
    public function saved(Directory $model)
    {

    }

    /**
     * Handle the "deleting" event.
     *
     * @param \App\Directory $model
     * @return mixed
     */
    public function deleting(Directory $model)
    {
        // onDeleted
        $model->getTypeHandler()->onDeleted($model);
    }

    /**
     * Handle the "deleted" event.
     *
     * @param \App\Directory $model
     * @return void
     */
    public function deleted(Directory $model)
    {

    }

    /**
     * @param \App\Directory $directory
     * @return string
     */
    private function getSearch(\App\Directory $directory): string
    {
        return \EloquentRequestSearch::generate($directory->getTypeHandler()->search($directory));
    }
}
