<?php

namespace App\Handlers\Models\FileVirtual\Entity;

use AnourValar\EloquentFile\FileVirtual;
use Illuminate\Contracts\Auth\Authenticatable;

class TicketEntity implements \AnourValar\EloquentFile\Handlers\Models\FileVirtual\Entity\EntityInterface
{
    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentFile\Handlers\Models\FileVirtual\Entity\EntityInterface::canUpload()
     */
    public function canUpload(FileVirtual $fileVirtual, ?Authenticatable $user): bool
    {
        if ($fileVirtual->name == 'attachment_request') {
            return $user?->can('createUpload', $fileVirtual->entitable);
        }

        if ($fileVirtual->name == 'attachment_response') {
            return $user?->can('update', $fileVirtual->entitable) || $user?->can('admin.ticket.update');
        }

        throw new \RuntimeException('Incorrect usage.');
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentFile\Handlers\Models\FileVirtual\Entity\EntityInterface::canDownload()
     */
    public function canDownload(FileVirtual $fileVirtual, ?Authenticatable $user): bool
    {
        return false; // flow is not actual
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentFile\Handlers\Models\FileVirtual\Entity\EntityInterface::canDelete()
     */
    public function canDelete(FileVirtual $fileVirtual, ?Authenticatable $user): bool
    {
        return $this->canUpload($fileVirtual, $user);
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentFile\Handlers\Models\FileVirtual\Entity\EntityInterface::lockOnChange()
     */
    public function lockOnChange(FileVirtual $fileVirtual): void
    {
        \App::make(\App\Services\TicketService::class)->lock($fileVirtual->entitable);
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentFile\Handlers\Models\FileVirtual\Entity\EntityInterface::validate()
     */
    public function validate(FileVirtual $fileVirtual, \Illuminate\Validation\Validator $validator): void
    {
        if (! \App\Ticket::find($fileVirtual->entity_id)) {
            $validator->errors()->add(
                'entity_id',
                trans('eloquent_file::file_virtual.entity_handler.ticket.entity_id_not_exists')
            );
        }
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentFile\Handlers\Models\FileVirtual\Entity\EntityInterface::validateDelete()
     */
    public function validateDelete(FileVirtual $fileVirtual, \Illuminate\Validation\Validator $validator): void
    {

    }
}
