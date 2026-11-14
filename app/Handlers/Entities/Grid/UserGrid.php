<?php

namespace App\Handlers\Entities\Grid;

use AnourValar\Office\Drivers\GridInterface;
use AnourValar\Office\Facades\ExportService;

class UserGrid implements \AnourValar\Office\Facades\ExportGridQueryInterface
{
    /**
     * DI
     *
     * @param \App\Helpers\NumberHelper $numberHelper
     * @return void
     */
    public function __construct(public \App\Helpers\NumberHelper $numberHelper)
    {

    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\Office\Facades\ExportGridQueryInterface::query()
     */
    public function query(): \Illuminate\Database\Eloquent\Builder
    {
        return \App\User::light();
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\Office\Facades\ExportGridInterface::sheetTitle()
     */
    public function sheetTitle(array $request): string
    {
        return trans('handlers/entities/grid/user.title');
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\Office\Facades\ExportGridInterface::columns()
     */
    public function columns(array $request): array
    {
        return [
            ['title' => trans('models/user.attributes.id'), 'width' => 12],
            ['title' => trans('models/user.attributes.first_name'), 'width' => 18],
            ['title' => trans('models/user.attributes.last_name'), 'width' => 18],
            ['title' => trans('models/user.attributes.middle_name'), 'width' => 18],
            ['title' => trans('models/user.attributes.created_at'), 'width' => 15],
        ];
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\Office\Facades\ExportGridInterface::item()
     */
    public function item($row, GridInterface $driver, int $rowNumber, array $request): array
    {
        return [
            $row->id,
            $row->first_name,
            $row->last_name,
            $row->middle_name,
            $row->created_at->setTimezone(config('atom.timezone_client')),
        ];
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\Office\Facades\ExportGridInterface::fileName()
     */
    public function fileName(string $ext, array $request): string
    {
        return trans('handlers/entities/grid/user.filename', ['ext' => $ext]);
    }
}
