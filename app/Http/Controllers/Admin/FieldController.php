<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FieldController extends Controller
{
    /**
     * Действие: Изменение поля пользователя
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Services\FieldService $fieldService
     * @return array
     */
    public function update(Request $request, \App\Services\FieldService $fieldService)
    {
        $result = \DB::transaction(function () use ($request) {
            \Atom::lockUser($request->route('user'), 'field');

            \App\Field::query()
                ->where('user_id', '=', $request->route('user'))
                ->findOrFail($request->route('field'))
                ->fields(['status'])
                ->fill((array) $request->input('field'))
                ->validate('field')
                ->authorize(null)
                ->save();

            $request->session()->flash('alert.primary', trans('controllers/admin/field.update'));
            return ['reload' => true];
        });

        $fieldService->flushCache($request->route('user'));
        return $result;
    }
}
