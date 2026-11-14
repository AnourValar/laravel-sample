<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FieldController extends Controller
{
    use \AnourValar\EloquentFile\Traits\ControllerTrait;

    /**
     * Веб-сервис: Список полей
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Services\FieldService $fieldService
     * @return array
     */
    public function index(Request $request, \App\Services\FieldService $fieldService): array
    {
        $user = \App\User::findOrFail($request->route('user'));
        $this->authorize('view', new \App\Field()->forceFill(['user_id' => $user->id]));

        return $fieldService->fields($user);
    }

    /**
     * Веб-сервис: Создание/Изменение поля
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Services\FieldService $fieldService
     * @return array
     */
    public function save(Request $request, \App\Services\FieldService $fieldService): array
    {
        $result = \DB::transaction(function () use ($request) {
            \Atom::lockUser($request->route('user'), 'field');

            $field = \App\Field::where('user_id', '=', $request->route('user'))->where('name', '=', $request->route('name'))->first();
            if (! $field) {
                $field = \App\Field::fields('user_id', 'name')->fill(['user_id' => $request->route('user'), 'name' => $request->route('name')]);
            }

            $field
                ->fields(['data'])
                ->fill((array) $request->input('field'))
                ->validate(['field', $request->route('name')])
                ->authorize('save')
                ->save();

            return ['field' => ['id' => $field->id]];
        });

        $fieldService->flushCache($request->route('user'));
        return $result;
    }

    /**
     * Веб-сервис: Загрузка файла в поле
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Services\FieldService $fieldService
     * @return array
     */
    public function upload(Request $request, \App\Services\FieldService $fieldService): array
    {
        $result = \DB::transaction(function () use ($request) {
            \Atom::lockUser($request->route('user'), 'field');

            $field = \App\Field::where('user_id', '=', $request->route('user'))->where('name', '=', $request->route('name'))->first();
            if (! $field) {
                $field = \App\Field::fields('user_id', 'name')->fill(['user_id' => $request->route('user'), 'name' => $request->route('name')]);
            }

            $field->validate()->authorize('save')->save();
            $fileVirtual = $this->uploadFileFrom($request, $field, ['name' => $field->name_details['file_virtuals'][0] ?? null]);

            return ['file_virtual' => ['id' => $fileVirtual->id]];
        });

        $fieldService->flushCache($request->route('user'));
        return $result;
    }

    /**
     * Веб-сервис: Удаление поля
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Services\FieldService $fieldService
     * @return array
     */
    public function delete(Request $request, \App\Services\FieldService $fieldService): array
    {
        $result = \DB::transaction(function () use ($request) {
            \Atom::lockUser($request->route('user'), 'field');

            $field = \App\Field::query()
                ->acl()
                ->where('user_id', '=', $request->route('user'))
                ->where('name', '=', $request->route('name'))
                ->firstOrFail()
                ->validateDelete()
                ->authorize('delete')
                ->delete();

            return ['field' => ['id' => $field->id]];
        });

        $fieldService->flushCache($request->route('user'));
        return $result;
    }

    /**
     * Веб-сервис: Изменение поля (статус)
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Services\FieldService $fieldService
     * @return array
     */
    public function update(Request $request, \App\Services\FieldService $fieldService): array
    {
        $result = \DB::transaction(function () use ($request) {
            \Atom::lockUser($request->route('user'), 'field');

            $field = \App\Field::query()
                ->acl()
                ->where('user_id', '=', $request->route('user'))
                ->where('name', '=', $request->route('name'))
                ->firstOrFail()
                ->fields(['status'])
                ->fill((array) $request->input('field'))
                ->validate(['field', $request->route('name')])
                ->authorize('update')
                ->save();

            return ['field' => ['id' => $field->id]];
        });

        $fieldService->flushCache($request->route('user'));
        return $result;
    }
}
