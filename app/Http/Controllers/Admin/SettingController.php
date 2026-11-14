<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Интерфейс: Список настроек
     *
     * @param \App\Services\SettingService $settingService
     * @return \Illuminate\Contracts\View\View
     */
    public function index(\App\Services\SettingService $settingService)
    {
        $settings = $settingService->get();

        return view('admin.setting.index', compact('settings'));
    }

    /**
     * Действие: Изменение настроек (batch)
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Services\SettingService $settingService
     * @return array
     */
    public function update(Request $request, \App\Services\SettingService $settingService): array
    {
        return \DB::transaction(function () use ($request, $settingService) {
            \Atom::lockSetting();

            $settingService->put($request->input('settings'), 'settings');

            $request->session()->flash('alert.primary', trans('controllers/admin/setting.update'));
            return ['redirect' => route('admin.setting.index')];
        });
    }
}
