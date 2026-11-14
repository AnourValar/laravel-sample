<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FileController extends Controller
{
    use \AnourValar\EloquentFile\Traits\ControllerTrait;

    /**
     * Действие: Скачивание (проксирование) файла
     *
     * @param \Illuminate\Http\Request $request
     * @return mixed
     */
    public function download(Request $request)
    {
        return $this->proxyUrlSigned($request);
    }
}
