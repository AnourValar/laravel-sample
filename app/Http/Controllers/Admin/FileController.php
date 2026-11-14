<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FileController extends Controller
{
    use \AnourValar\EloquentFile\Traits\ControllerTrait;

    /**
     * Действие: Загрузка файла
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function upload(Request $request): array
    {
        \DB::transaction(function () use ($request) {
            $this->uploadFileFrom($request);
        });

        $request->session()->flash('alert.primary', trans('controllers/admin/file.upload'));
        return ['redirect' => url()->previous()];
    }

    /**
     * Действие: Удаление файла
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function delete(Request $request): array
    {
        \DB::transaction(function () use ($request) {
            $this->deleteFileFrom($request);
        });

        $request->session()->flash('alert.warning', trans('controllers/admin/file.delete'));
        return ['redirect' => url()->previous()];
    }
}
