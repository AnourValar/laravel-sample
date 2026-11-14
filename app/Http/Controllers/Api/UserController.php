<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use \AnourValar\EloquentRequest\ControllerTrait;

    /**
     * Profile
     *
     * @var array
     */
    protected $profile = [
        'filter' => [
            'pid' => self::PROFILE_FILTER_ID,
            'role' => self::PROFILE_FILTER_ID,
            'tag_ids' => self::PROFILE_FILTER_JSON,
            'search' => self::PROFILE_FILTER_SEARCH,
            'created_at' => self::PROFILE_FILTER_DATE,
            'deleted_at' => self::PROFILE_FILTER_IS_NULL,
        ],

        'custom_casts' => [
            'tag_ids' => 'integer',
        ],

        'relation' => [

        ],

        'scope' => [

        ],

        'sort' => [
            'id', 'deleted_at',
        ],

        'ranges' => [

        ],

        'options' => [
            \AnourValar\EloquentRequest\Builders\FilterAndScopeBuilder::OPTION_GROUP_RELATION,

            \AnourValar\EloquentRequest\Actions\PaginateAction::OPTION_SIMPLE_PAGINATE,
            \AnourValar\EloquentRequest\Actions\PaginateAction::OPTION_PAGE_MAX => 20,
        ],

        'default_request' => [
            'per_page' => 20,
            'sort' => ['id' => 'DESC'],
        ],
    ];

    /**
     * Profile export (merge)
     *
     * @var array
     */
    protected $profileExport = [
        'options' => [
            \AnourValar\EloquentRequest\Builders\FilterAndScopeBuilder::OPTION_GROUP_RELATION,

            \AnourValar\EloquentRequest\Actions\GeneratorAction::OPTION_APPLY_CHUNK => 2000,
            \AnourValar\EloquentRequest\Actions\GeneratorAction::OPTION_LIMIT => 20000,
        ],
    ];

    /**
     * Веб-сервис: Список пользователей
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Pagination\Paginator
     */
    public function index(Request $request): \Illuminate\Pagination\Paginator
    {
        return $this->buildBy(
            \App\User::withTrashed()->acl()->light()
        );
    }

    /**
     * Действие: Экспорт списка пользователей
     *
     * @param \App\Handlers\Entities\Grid\UserGrid $grid
     * @param \AnourValar\Office\Facades\ExportService $exportService
     * @param \AnourValar\Office\Format $format
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export(
        \App\Handlers\Entities\Grid\UserGrid $grid,
        \AnourValar\Office\Facades\ExportService $exportService,
        \AnourValar\Office\Format $format
    ) {
        if (! in_array($format, [\AnourValar\Office\Format::Xlsx, \AnourValar\Office\Format::Csv])) {
            throw new \App\Exceptions\ValidationException('Format is not supported.');
        }

        $generatorData = $this->buildBy($grid->query()->withTrashed()->acl(), array_replace($this->profile, $this->profileExport));
        $request = $this->getBuildRequest()->get();

        return response()->streamDownload(
            function () use ($generatorData, $grid, $exportService, $format, $request) {
                echo $exportService->grid($generatorData, $grid, $format, $request);
            },
            $grid->fileName($format->fileExtension(), $request),
            [
                'Access-Control-Expose-Headers' => 'Content-Disposition',
                'Content-Type' => $format->contentType(),
            ]
        );
    }

    /**
     * Веб-сервис: Карточка пользователя
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function edit(Request $request): array
    {
        return ['user' => \App\User::withTrashed()->acl()->heavy()->findOrFail($request->route('user'))];
    }

    /**
     * Веб-сервис: Создание пользователя
     *
     * @param \Illuminate\Http\Request $request
     * @param \AnourValar\EloquentNotification\ConfirmService $confirmService
     * @return array
     */
    public function create(
        Request $request,
        \AnourValar\EloquentNotification\ConfirmService $confirmService,
    ): array {
        return \DB::transaction(function () use ($request, $confirmService) {
            \Atom::lockUser();
            if ($request->input('user.pid')) {
                \Atom::lockUser($request->input('user.pid'), 'user');
            }

            $data = (array) $request->input('user');

            if (isset($data['email'])) {
                $data['email'] = $confirmService->validateEmail(
                    $request->input('cryptogram_email'),
                    $request->input('code_email'),
                    $data['email'],
                    'code_email',
                );
            }

            if (isset($data['phone'])) {
                $data['phone'] = $confirmService->validatePhone(
                    $request->input('cryptogram_phone'),
                    $request->input('code_phone'),
                    $data['phone'],
                    'code_phone',
                );
            }

            if (isset($data['password'])) {
                $data['password'] = \App::make(\App\Helpers\StringHelper::class)->hashPassword($data['password']);
            }

            $user = \App\User::query()
                ->fields([
                    'pid', 'role', 'role_data', 'email', 'phone', 'password', 'first_name', 'last_name', 'middle_name',
                    'gender', 'birthday',
                ])
                ->fill($data)
                ->validate('user')
                ->authorize('create')
                ->save();

            event(new \Illuminate\Auth\Events\Registered($user));
            return ['user' => ['id' => $user->id]];
        });
    }

    /**
     * Веб-сервис: Изменение пользователя
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function update(Request $request): array
    {
        return \DB::transaction(function () use ($request) {
            \Atom::lockUser($request->route('user'), 'user');

            $user = \App\User::query()
                ->acl()
                ->findOrFail($request->route('user'))
                ->fields([
                    'role', 'role_data', 'first_name', 'last_name', 'middle_name',
                    'gender', 'birthday',
                ])
                ->fill((array) $request->input('user'))
                ->validate('user')
                ->authorize('update')
                ->save();

            return ['user' => ['id' => $user->id]];
        });
    }

    /**
     * Веб-сервис: Изменение email`а
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function updateEmail(Request $request): array
    {
        return \DB::transaction(function () use ($request) {
            \Atom::lockUser();
            \Atom::lockUser($request->route('user'), 'user');

            $user = \App\User::query()
                ->acl()
                ->findOrFail($request->route('user'))
                ->fields(['email'])
                ->fill((array) $request->input('user'))
                ->validate('user')
                ->authorize('update')
                ->save();

            return ['user' => ['id' => $user->id]];
        });
    }

    /**
     * Веб-сервис: Изменение телефона
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function updatePhone(Request $request): array
    {
        return \DB::transaction(function () use ($request) {
            \Atom::lockUser();
            \Atom::lockUser($request->route('user'), 'user');

            $user = \App\User::query()
                ->acl()
                ->findOrFail($request->route('user'))
                ->fields(['phone'])
                ->fill((array) $request->input('user'))
                ->validate('user')
                ->authorize('update')
                ->save();

            return ['user' => ['id' => $user->id]];
        });
    }

    /**
     * Веб-сервис: Изменение имени в телеграме
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function updateTelegram(Request $request): array
    {
        return \DB::transaction(function () use ($request) {
            \Atom::lockUser($request->route('user'), 'user');

            $user = \App\User::query()
                ->acl()
                ->findOrFail($request->route('user'))
                ->fields(['telegram_username'])
                ->fill((array) $request->input('user'))
                ->validate('user')
                ->authorize('update')
                ->save();

            return ['user' => ['id' => $user->id]];
        });
    }

    /**
     * Веб-сервис: Удаление email
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function revokeEmail(Request $request): array
    {
        return \DB::transaction(function () use ($request) {
            \Atom::lockUser();
            \Atom::lockUser($request->route('user'), 'user');

            $user = \App\User::query()
                ->acl()
                ->findOrFail($request->route('user'))
                ->fields(['email'])
                ->fill(['email' => null])
                ->validate()
                ->authorize('update')
                ->save();

            return ['user' => ['id' => $user->id]];
        });
    }

    /**
     * Веб-сервис: Удаление телефона
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function revokePhone(Request $request): array
    {
        return \DB::transaction(function () use ($request) {
            \Atom::lockUser();
            \Atom::lockUser($request->route('user'), 'user');

            $user = \App\User::query()
                ->acl()
                ->findOrFail($request->route('user'))
                ->fields(['phone'])
                ->fill(['phone' => null])
                ->validate()
                ->authorize('update')
                ->save();

            return ['user' => ['id' => $user->id]];
        });
    }

    /**
     * Веб-сервис: Удаление пользователя
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function delete(Request $request): array // @TODO: удаление перс. данных?
    {
        return \DB::transaction(function () use ($request) {
            \Atom::lockUser($request->route('user'), 'user');
            if ($pid = \App\User::withTrashed()->findOrFail($request->route('user'))->pid) {
                \Atom::lockUser($pid, 'user');
            }

            $user = \App\User::query()
                ->acl()
                ->findOrFail($request->route('user'))
                ->validateDelete()
                ->authorize('delete')
                ->delete();

            return ['user' => ['id' => $user->id]];
        });
    }

    /**
     * Веб-сервис: Восстановление пользователя
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function restore(Request $request): array
    {
        return \DB::transaction(function () use ($request) {
            \Atom::lockUser($request->route('user'), 'user');
            if ($pid = \App\User::withTrashed()->findOrFail($request->route('user'))->pid) {
                \Atom::lockUser($pid, 'user');
            }

            $user = \App\User::query()
                ->onlyTrashed()
                ->acl()
                ->findOrFail($request->route('user'))
                ->validateRestore()
                ->authorize('delete')
                ->restore();

            return ['user' => ['id' => $user->id]];
        });
    }

    /**
     * Веб-сервис: Генерация TOTP сессии
     *
     * @param \Illuminate\Http\Request $request
     * @param \AnourValar\EloquentNotification\ConfirmService $confirmService
     * @return array
     */
    public function totpGenerate(Request $request, \AnourValar\EloquentNotification\ConfirmService $confirmService): array
    {
        $secret = $confirmService->generateTotp();

        return [
            'secret' => $secret,
            'secret_url' => $confirmService->urlTotp($request->user()->phone ?? $request->user()->email, $secret),
            'cryptogram_totp' => $confirmService->cryptogramTotp($secret),
        ];
    }

    /**
     * Веб-сервис: Сохранение TOTP сессии
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function totpUpdate(Request $request): array
    {
        return \DB::transaction(function () use ($request) {
            \Atom::lockUser($request->user()->id, 'user');

            $user = \App\User::query()
                ->acl()
                ->findOrFail($request->user()->id)
                ->fields(['totp_secret'])
                ->fill(['totp_secret' => $request->input('cryptogram_new_totp')])
                ->validate()
                ->authorize('update')
                ->save();

            return ['user' => ['id' => $user->id]];
        });
    }

    /**
     * Веб-сервис: Удаление TOTP сессии
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function totpRevoke(Request $request): array
    {
        return \DB::transaction(function () use ($request) {
            \Atom::lockUser($request->user()->id, 'user');

            $user = \App\User::query()
                ->acl()
                ->findOrFail($request->user()->id)
                ->fields(['totp_secret'])
                ->fill(['totp_secret' => null])
                ->validate()
                ->authorize('update')
                ->save();

            return ['user' => ['id' => $user->id]];
        });
    }
}
