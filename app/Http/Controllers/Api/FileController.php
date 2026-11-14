<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FileController extends Controller
{
    use \AnourValar\EloquentFile\Traits\ControllerTrait;

    /**
     * Веб-сервис: Загрузка файла
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function upload(Request $request): array
    {
        $fileVirtual = \DB::transaction(function () use ($request) {
            return $this->uploadFileFrom($request);
        });

        return ['file_virtual' => ['id' => $fileVirtual->id]];
    }

    /**
     * Веб-сервис: Удаление файла
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function delete(Request $request)
    {
        \DB::transaction(function () use ($request) {
            $this->deleteFileFrom($request);
        });

        return ['file_virtual' => ['id' => (int) $request->route('file_virtual')]];
    }
}
