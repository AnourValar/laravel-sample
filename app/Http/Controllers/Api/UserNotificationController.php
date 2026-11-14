<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserNotificationController extends Controller
{
    /**
     * Веб-сервис: Список подключённых уведомлений пользователя
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function notificationIndex(Request $request): array
    {
        $user = \App\User::query()->acl()->findOrFail($request->route('user'));

        return ['data' => \App\UserNotification::light()->where('user_id', '=', $user->id)->orderBy('id', 'ASC')->get()];
    }

    /**
     * Веб-сервис: Изменение подключённых уведомлений пользователя
     *
     * @param \Illuminate\Http\Request $request
     * @param \AnourValar\EloquentNotification\Service $notificationService
     * @return array
     */
    public function notificationSync(Request $request, \AnourValar\EloquentNotification\Service $notificationService): array
    {
        return \DB::transaction(function () use ($request, $notificationService) {
            \Atom::lockUser($request->route('user'), 'notification');

            $user = \App\User::query()->acl()->findOrFail($request->route('user'));
            $user->authorize('update');
            $notificationService->sync($user, (array) $request->input('user_notifications'), 'user_notifications', 'is_public');

            return ['user' => ['id' => $user->id]];
        });
    }
}
