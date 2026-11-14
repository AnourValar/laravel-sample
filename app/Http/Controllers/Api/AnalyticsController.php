<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    use \AnourValar\LaravelAtom\Http\AnalyticsTrait;

    /**
     * Веб-сервис: получение аналитических данных
     *
     * @param \Illuminate\Http\Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        return $this->retrieveAnalytics()['response'];
    }
}
