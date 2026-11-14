<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserTokenController extends Controller
{
    /**
     * Веб-сервис: Список токенов текущего пользователя
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function tokenIndex(Request $request): array
    {
        $tokens = $request
            ->user()
            ->tokens()
            ->withCasts(config('models.user.token_casts'))
            ->orderBy('id', 'ASC')
            ->get()
            ->transform(fn ($token) => $token->only(config('models.user.token_light')));

        return ['data' => $tokens];
    }

    /**
     * Веб-сервис: Удаление токена текущего пользователя
     *
     * @param \Illuminate\Http\Request $request
     * @param \AnourValar\EloquentJournal\Service $journalService
     * @return array
     */
    public function tokenDelete(Request $request, \AnourValar\EloquentJournal\Service $journalService): array
    {
        return \DB::transaction(function () use ($request, $journalService) {
            // Авторизация
            $token = $request->user()->tokens()->where('id', '=', $request->route('token'))->firstOrFail();

            // Удаление
            $token->delete();

            // Логирование
            $journalService->captureMetric('user_token_delete');

            return ['user_token' => ['id' => $token->id]];
        });
    }

    /**
     * Веб-сервис: Удаление всех wildcard-токенов текущего пользователя (кроме представленного)
     *
     * @param \Illuminate\Http\Request $request
     * @param \AnourValar\EloquentJournal\Service $journalService
     * @return array
     */
    public function tokenDeleteExcept(Request $request, \AnourValar\EloquentJournal\Service $journalService): array
    {
        return \DB::transaction(function () use ($request, $journalService) {
            // Удаление
            $ids = [];
            foreach ($request->user()->tokens()->get() as $item) {
                if ($item->abilities == ['*'] && $item->id != $request->route('token')) {
                    $ids[] = $item->id;
                }
            }
            if ($ids) {
                $request->user()->tokens()->whereIn('id', $ids)->delete();
            }

            // Логирование
            $journalService->captureMetric('user_token_delete');

            return ['user_token' => ['ids' => $ids]];
        });
    }

    /**
     * Веб-сервис: Изменение (удаление) push-токена
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function tokenPush(Request $request): array
    {
        $data = $request->validate(['push_token' => ['nullable', 'not_empty', 'string', 'min:2', 'max:200']]);

        $token = $request->user()->tokens()->where('id', '=', $request->route('token'))->firstOrFail();
        $token->forceFill(['push_token' => $data['push_token']])->save();

        return ['user_token' => ['id' => $token->id]];
    }
}
