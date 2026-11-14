<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * Веб-сервис: Запрос подтверждения PoW
     *
     * @param \Illuminate\Http\Request $request
     * @param \AnourValar\EloquentNotification\ConfirmService $confirmService
     * @return array
     */
    public function confirmPow(Request $request, \AnourValar\EloquentNotification\ConfirmService $confirmService): array
    {
        return $confirmService->requestPow();
    }

    /**
     * Веб-сервис: Запрос подтверждения email
     *
     * @param \Illuminate\Http\Request $request
     * @param \AnourValar\EloquentNotification\ConfirmService $confirmService
     * @return array
     */
    public function confirmEmail(Request $request, \AnourValar\EloquentNotification\ConfirmService $confirmService)
    {
        return $confirmService->requestEmail($request->input('email'), $request->input('email_should_exists'));
    }

    /**
     * Веб-сервис: Запрос подтверждения телефона
     *
     * @param \Illuminate\Http\Request $request
     * @param \AnourValar\EloquentNotification\ConfirmService $confirmService
     * @return array
     */
    public function confirmPhone(Request $request, \AnourValar\EloquentNotification\ConfirmService $confirmService)
    {
        $userDemo = \App::make(\App\Services\SettingService::class)->get('user_demo');
        if ($userDemo['enabled'] && $userDemo['phone'] === $request->input('phone')) {
            $code = $userDemo['code'];
        } else {
            $code = null;
        }

        return $confirmService->requestPhone($request->input('phone'), 'phone', $request->input('phone_should_exists'), $code);
    }

    /**
     * Веб-сервис: FA - подтверждение телефона
     *
     * @param \Illuminate\Http\Request $request
     * @param \AnourValar\EloquentNotification\ConfirmService $confirmService
     * @return array
     */
    public function faPhone(Request $request, \AnourValar\EloquentNotification\ConfirmService $confirmService): array
    {
        // Идентификация
        $phone = $request->input('phone');
        $user = \App\User::login($phone)->first();

        // Криптограмма
        return [
            'cryptogram_fa' => (new \AnourValar\EloquentNotification\FaMapper('phone', ['id' => $user?->id, 'phone' => $phone]))->encrypt(),
            'factors' => $confirmService->fa($user),
        ];
    }

    /**
     * Веб-сервис: FA - подтверждение email
     *
     * @param \Illuminate\Http\Request $request
     * @param \AnourValar\EloquentNotification\ConfirmService $confirmService
     * @return array
     */
    public function faEmail(Request $request, \AnourValar\EloquentNotification\ConfirmService $confirmService): array
    {
        // Идентификация
        $email = $request->input('email');
        $user = \App\User::login($email)->first();

        // Криптограмма
        return [
            'cryptogram_fa' => (new \AnourValar\EloquentNotification\FaMapper('email', ['id' => $user?->id, 'email' => $email]))->encrypt(),
            'factors' => $confirmService->fa($user),
        ];
    }

    /**
     * Веб-сервис: FA - подтверждение пароля
     *
     * @param \Illuminate\Http\Request $request
     * @param \AnourValar\EloquentNotification\ConfirmService $confirmService
     * @return array
     */
    public function faPassword(Request $request, \AnourValar\EloquentNotification\ConfirmService $confirmService): array
    {
        // Валидация
        $data = $request->validate([
            'login' => ['required', 'string', 'min:2', 'max:100'],
            'password' => ['required', 'string', 'min:2', 'max:200'],
        ]);

        // Throttle по логину
        $cacheKey = implode(' / ', [__METHOD__, mb_strtolower(trim($data['login']))]);
        if (! \RateLimiter::attempt($cacheKey, 20, fn () => null, 86400)) {
            throw (new \App\Exceptions\ValidationException(trans('auth.throttle', ['seconds' => \RateLimiter::availableIn($cacheKey)])))->status(429);
        }

        // Проверка пароля
        $user = \App\User::login($data['login'])->first();
        if (! \Hash::check($request->password, $user?->password)) {
            throw new \App\Exceptions\ValidationException(trans('auth.password'));
        }

        // Актуализация хэша пароля
        if (\Hash::needsRehash($user->password)) {
            \App\User::where('id', '=', $user->id)->where('password', '=', $user->password)->update(['password' => \Hash::make($request->password)]);
        }

        // Криптограмма
        return [
            'cryptogram_fa' => (new \AnourValar\EloquentNotification\FaMapper('password', $user->only('id')))->encrypt(),
            'factors' => $confirmService->fa($user),
        ];
    }

    /**
     * Веб-сервис: FA - активная сессия (токен)
     *
     * @param \Illuminate\Http\Request $request
     * @param \AnourValar\EloquentNotification\ConfirmService $confirmService
     * @return array
     */
    public function faSession(Request $request, \AnourValar\EloquentNotification\ConfirmService $confirmService): array
    {
        // Криптограмма
        return [
            'cryptogram_fa' => (new \AnourValar\EloquentNotification\FaMapper('session', $request->user()->only('id')))->encrypt(),
            'factors' => $confirmService->fa($request->user()),
        ];
    }

    /**
     * Веб-сервис: FA - подтверждение TOTP
     *
     * @param \Illuminate\Http\Request $request
     * @param \AnourValar\EloquentNotification\ConfirmService $confirmService
     * @return array
     */
    public function faTotp(Request $request, \AnourValar\EloquentNotification\ConfirmService $confirmService): array
    {
        // Валидация
        $data = $request->validate([
            'login' => ['required', 'string', 'min:2', 'max:100'],
            'code_totp' => ['required', 'string', 'min:6', 'max:6'],
        ]);

        // Проверка кода
        $user = \App\User::login($data['login'])->first();
        $confirmService->validateTotpCryptogram($user?->totp_secret, $data['code_totp']);

        // Криптограмма
        return [
            'cryptogram_fa' => (new \AnourValar\EloquentNotification\FaMapper('totp', $user->only('id')))->encrypt(),
            'factors' => $confirmService->fa($user),
        ];
    }

    /**
     * Веб-сервис: Смена пароля
     *
     * @param \Illuminate\Http\Request $request
     * @param \AnourValar\EloquentNotification\ConfirmService $confirmService
     * @return array
     */
    public function changePassword(Request $request, \AnourValar\EloquentNotification\ConfirmService $confirmService): array
    {
        return \DB::transaction(function () use ($request, $confirmService) {
            $contacts = $confirmService->validateFa($request->input('cryptograms_fa'), 2);
            $roles = \ConfigHelper::keys('models.user.role', ['abilities' => 'cabinet']);
            $user = \App\User::withTrashed()->whereIn('role', $roles)->where($contacts)->first();
            if ($user) {
                \Atom::lockUser($user->id, 'user');
                $user->refresh();
            }

            if (! $user) {
                throw (new \App\Exceptions\ValidationException(trans('controllers/api/auth.createToken_not_exists')))->status(403);
            }
            if ($user->deleted_at) {
                throw (new \App\Exceptions\ValidationException(trans('controllers/api/auth.createToken_deleted')))->status(403);
            }

            $user->password = \App::make(\App\Helpers\StringHelper::class)->hashPassword($request->input('password'));
            $user->validate(null, ['password' => 'required'])->save();
            return ['user' => $user->only('id')];
        });
    }

    /**
     * Веб-сервис: Регистрация (получение токена)
     *
     * @param \Illuminate\Http\Request $request
     * @param \AnourValar\EloquentJournal\Service $journalService
     * @param \AnourValar\EloquentNotification\ConfirmService $confirmService
     * @param \App\Services\UserService $userService
     * @return array
     */
    public function register(
        Request $request,
        \AnourValar\EloquentJournal\Service $journalService,
        \AnourValar\EloquentNotification\ConfirmService $confirmService,
        \App\Services\UserService $userService,
    ): array {
        // Валидация
        $data = $request->validate([
            'device' => ['required', 'string', 'min:2'], // navigator.userAgent
        ]);

        // Создание юзера
        $user = \DB::transaction(function () use ($request, $confirmService) {
            $contacts = $confirmService->validateFa($request->input('cryptograms_fa'), 1, faBlack: ['session']);
            unset($contacts['id']);
            \Atom::lockUser(); // no pid

            $data = array_merge(
                $request->only('password', 'first_name', 'last_name', 'middle_name', 'role', 'role_data'),
                $contacts,
            );
            if (isset($data['password'])) {
                $data['password'] = \App::make(\App\Helpers\StringHelper::class)->hashPassword($data['password']);
            }

            return \App\User::fields(array_keys($data))->fill($data)->validate()->authorize('create')->save();
        });

        // Логирование
        $journalService->user($user)->captureMetric('user_token_obtain');
        event(new \Illuminate\Auth\Events\Registered($user));

        // Выпуск токена
        return ['token' => $userService->obtainToken($user, $data['device'])->plainTextToken];
    }

    /**
     * Веб-сервис: Аутентификация (получение токена)
     *
     * @param \Illuminate\Http\Request $request
     * @param \AnourValar\EloquentJournal\Service $journalService
     * @param \AnourValar\EloquentNotification\ConfirmService $confirmService
     * @param \App\Services\UserService $userService
     * @return array
     */
    public function login(
        Request $request,
        \AnourValar\EloquentJournal\Service $journalService,
        \AnourValar\EloquentNotification\ConfirmService $confirmService,
        \App\Services\UserService $userService,
    ): array {
        // Валидация
        $data = $request->validate([
            'device' => ['required', 'string', 'min:2'], // navigator.userAgent
        ]);

        // Идентификация юзера
        $contacts = $confirmService->validateFa(
            $request->input('cryptograms_fa'),
            fn ($contacts) => $confirmService->faAtLeast(3, \App\User::withTrashed()->where($contacts)->first()) ? 2 : 1,
            faBlack: ['session']
        );
        $user = \DB::transaction(function () use ($contacts) {
            $roles = \ConfigHelper::keys('models.user.role', ['abilities' => 'cabinet']);
            $user = \App\User::withTrashed()->whereIn('role', $roles)->where($contacts)->first();

            if (! $user) {
                throw (new \App\Exceptions\ValidationException(trans('controllers/api/auth.createToken_not_exists')))->status(403);
            }
            if ($user->deleted_at) {
                throw (new \App\Exceptions\ValidationException(trans('controllers/api/auth.createToken_deleted')))->status(403);
            }

            return $user;
        });

        // Чистка
        $user->tokens()->where('expires_at', '<', now())->delete();
        $user->tokens()->orderBy('id', 'DESC')->limit(100)->offset(50)->delete();

        // Логирование
        $journalService->user($user)->captureMetric('user_token_obtain');

        // Выпуск токена
        return ['token' => $userService->obtainToken($user, $data['device'])->plainTextToken];
    }

    /**
     * Веб-сервис: Получение данных по аутентифицированному пользователю и конфиги
     *
     * @param \Illuminate\Http\Request $request
     * @param \AnourValar\EloquentNotification\Service $notificationService
     * @param \AnourValar\EloquentJournal\Service $journalService
     * @param \AnourValar\EloquentNotification\ConfirmService $confirmService
     * @return array
     */
    public function getState(
        Request $request,
        \AnourValar\EloquentNotification\Service $notificationService,
        \AnourValar\EloquentJournal\Service $journalService,
        \AnourValar\EloquentNotification\ConfirmService $confirmService,
    ): array {
        $currentAbilities = array_merge($request->user()->role_details['abilities'], (array) $request->user()->additional_abilities)
            |> array_unique(...)
            |> array_values(...);

        return [
            'current_token_id' => $request->user()->currentAccessToken()->id,
            'current_token_abilities' => $request->user()->currentAccessToken()->abilities,

            'user' => \App\User::heavy()->find($request->user()->id),
            'user_roles' => \ConfigHelper::publish('models.user.role', ['title'], ['title']),
            'user_factors' => $confirmService->fa($request->user()),

            'current_abilities' => $currentAbilities,
            //'user_additional_abilities' => \ConfigHelper::publish('models.user.additional_abilities', ['title', 'acl'], ['title']),

            'user_notification_triggers' => \ConfigHelper::publish('eloquent_notification.trigger', ['title', 'channels', 'optgroup', 'ui'], ['title', 'optgroup']),
            'user_notification_channels' => $notificationService->channels(),

            'field_statuses' => \ConfigHelper::publish('models.field.status', ['title'], ['title']),

            'directory_types' => \ConfigHelper::publish('models.directory.type', ['title', 'is_manually'], ['title']),

            ...$journalService->publishConfig('journal_'), // journal_entities, journal_events

            'ticket_types' => \ConfigHelper::publish('models.ticket.type', ['title', 'options'], ['title']),
            'ticket_statuses' => \ConfigHelper::publish('models.ticket.status', ['title', 'ui'], ['title']),

            'telegram_bot_name' => config('eloquent_notification.bindings.' . \AnourValar\EloquentNotification\Adapters\Telegram\TelegramInterface::class . '.name'),
            'number_multiple' => config('atom.number.multiple'),
        ];
    }
}
