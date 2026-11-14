<?php

namespace App\Http\Controllers\Admin;

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
            'per_page' => 40,
            'sort' => ['deleted_at' => 'DESC', 'id' => 'DESC'],
        ],
    ];

    /**
     * Интерфейс: Список пользователей
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request)
    {
        $users = $this->buildBy(
            \App\User::withTrashed()
        );

        $request = $this->getBuildRequest();
        $userTags = $this->getUserTags();

        return view('admin.user.index', compact('users', 'request', 'userTags'));
    }

    /**
     * Интерфейс: Создание пользователя
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function add(Request $request)
    {
        $user = new \App\User();

        return view('admin.user.add', compact('user'));
    }

    /**
     * AJAX: Анкета пользователя
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\View|string
     */
    public function renderRole(Request $request)
    {
        $user = new \App\User();

        $value = $request->input('value');
        if (is_string($value) && config("models.user.role.{$value}")) {
            $user->role = $value;
        }

        if (! $user->role) {
            return '';
        }

        $handler = $user->getRoleHandler();
        if (! $handler instanceof \App\Handlers\Models\User\Role\FormInterface) {
            return '';
        }

        return $handler->renderEdit($user, 'user');
    }

    /**
     * Действие: Создание пользователя
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function create(Request $request)
    {
        return \DB::transaction(function () use ($request) {
            \Atom::lockUser();
            if ($request->input('user.pid')) {
                \Atom::lockUser($request->input('user.pid'), 'user');
            }

            $data = (array) $request->input('user');
            if (isset($data['password'])) {
                $data['password'] = \App::make(\App\Helpers\StringHelper::class)->hashPassword($data['password']);
            }

            $user = \App\User::query()
                ->fields([
                    'pid', 'role', 'role_data', 'email', 'phone', 'password', 'additional_abilities', 'first_name', 'last_name',
                    'middle_name', 'gender', 'birthday', 'comments',
                ])
                ->fill($data)
                ->validate('user')
                ->authorize(null)
                ->save();

            event(new \Illuminate\Auth\Events\Registered($user));

            $request->session()->flash('alert.success', trans('controllers/admin/user.create'));
            return ['redirect' => route('admin.user.edit', [$user])];
        });
    }

    /**
     * Интерфейс: Карточка пользователя
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\User $user
     * @param \AnourValar\EloquentNotification\Service $notificationService
     * @return \Illuminate\Contracts\View\View
     */
    public function edit(Request $request, \App\User $user, \AnourValar\EloquentNotification\Service $notificationService)
    {
        $notificationChannels = $notificationService->channels();
        $userTags = $this->getUserTags();

        return view('admin.user.edit', [
            'user' => $user,
            'notificationChannels' => $notificationChannels,
            'userTags' => $userTags,
            'menuRoute' => 'admin.user.index',
        ]);
    }

    /**
     * Действие: Изменение пользователя
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\User $user
     * @return array
     */
    public function update(Request $request, \App\User $user)
    {
        return \DB::transaction(function () use ($request, $user) {
            \Atom::lockUser(); // email, phone
            \Atom::lockUser(0, 'directory'); // attach/detach/set
            \Atom::lockUser($user->id, 'user');

            // Пользователь
            $data = (array) $request->input('user');
            if (isset($data['password'])) {
                $data['password'] = \App::make(\App\Helpers\StringHelper::class)->hashPassword($data['password']);
            }

            $user
                ->refresh()
                ->fields([
                    'role', 'role_data', 'email', 'phone', 'password', 'additional_abilities', 'first_name', 'last_name',
                    'middle_name', 'gender', 'birthday', 'tag_ids', 'comments',
                ])
                ->fill($data)
                ->validate('user')
                ->authorize(null)
                ->save();

            if (isset($data['password']) && $user->id == $request->user()->id) {
                \Auth::login($user);
            }

            $request->session()->flash('alert.primary', trans('controllers/admin/user.update'));
            return ['redirect' => route('admin.user.edit', $user)];
        });
    }

    /**
     * Действие: Генерация TOTP сессии
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\User $user
     * @param \AnourValar\EloquentNotification\ConfirmService $confirmService
     * @return array
     */
    public function totpGenerate(Request $request, \App\User $user, \AnourValar\EloquentNotification\ConfirmService $confirmService): array
    {
        return \DB::transaction(function () use ($request, $user, $confirmService) {
            \Atom::lockUser($user->id, 'user');
            $secret = $confirmService->generateTotp();
            $secretUrl = $confirmService->urlTotp($request->user()->phone ?? $request->user()->email, $secret);

            $user
                ->refresh()
                ->forceFill(['totp_secret' => $confirmService->cryptogramTotp($secret)])
                ->validate('user')
                ->authorize(null)
                ->save();

            $request->session()->flash('alert.info', trans('controllers/admin/user.totp_generate'));
            $request->session()->flash('totp_secret', $secret);
            $request->session()->flash('totp_secret_url', $secretUrl);
            return ['redirect' => route('admin.user.edit', $user)];
        });
    }

    /**
     * Действие: Удаление TOTP сессии
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\User $user
     * @return array
     */
    public function totpRevoke(Request $request, \App\User $user): array
    {
        return \DB::transaction(function () use ($request, $user) {
            \Atom::lockUser($user->id, 'user');

            $user
                ->refresh()
                ->forceFill(['totp_secret' => null])
                ->validate('user')
                ->authorize(null)
                ->save();

            $request->session()->flash('alert.warning', trans('controllers/admin/user.totp_revoke'));
            return ['redirect' => route('admin.user.edit', $user)];
        });
    }

    /**
     * Действие: Изменение уведомлений пользователя
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\User $user
     * @param \AnourValar\EloquentNotification\Service $notificationService
     * @return array
     */
    public function notificationSync(Request $request, \App\User $user, \AnourValar\EloquentNotification\Service $notificationService)
    {
        return \DB::transaction(function () use ($request, $user, $notificationService) {
            \Atom::lockUser($user->id, 'notification');

            $notificationService->sync($user, (array) $request->input('user_notifications'), 'user_notifications');

            $request->session()->flash('alert.primary', trans('controllers/admin/user.sync_notifications'));
            return ['redirect' => route('admin.user.edit', $user)];
        });
    }

    /**
     * Действие: Удаление пользователя
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\User $user
     * @return array
     */
    public function delete(Request $request, \App\User $user)
    {
        return \DB::transaction(function () use ($request, $user) {
            \Atom::lockUser($user->id, 'user');
            if ($user->pid) {
                \Atom::lockUser($user->pid, 'user');
            }

            $user->refresh()->validateDelete()->authorize(null)->delete();

            $request->session()->flash('alert.warning', trans('controllers/admin/user.delete'));
            return ['redirect' => route('admin.user.edit', $user)];
        });
    }

    /**
     * Действие: Восстановление пользователя
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\User $user
     * @return array
     */
    public function restore(Request $request, \App\User $user)
    {
        return \DB::transaction(function () use ($request, $user) {
            \Atom::lockUser($user->id, 'user');
            if ($user->pid) {
                \Atom::lockUser($user->pid, 'user');
            }

            $user->refresh()->validateRestore()->authorize(null)->restore();

            $request->session()->flash('alert.warning', trans('controllers/admin/user.restore'));
            return ['redirect' => route('admin.user.edit', $user)];
        });
    }

    /**
     * Действие: Войти под другим пользователем
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\User $user
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function loginAs(Request $request, \App\User $user)
    {
        $handler = $user->getRoleHandler();
        if (! $handler instanceof \App\Handlers\Models\User\Role\OnlineInterface) {
            abort(404);
        }

        \Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $request->session()->flash('alert.dark', trans('controllers/admin/user.user_login_as', ['title' => $user->email]));
        return $handler->loginAs($user);
    }

    /**
     * AJAX: Autocomplete
     *
     * @param \Illuminate\Http\Request $request
     * @return mixed
     */
    public function autocomplete(Request $request)
    {
        $request->validate([
            'operation' => ['nullable', 'not_empty', 'string', 'max:50'],
            'abilities' => ['required_without:operation', 'array', 'max:5'],
                'abilities.*' => ['required', 'string', 'max:50'],
        ]);

        if ($request->input('operation')) {
            $roles = \ConfigHelper::keys('models.user.role', [$request->input('operation') => true]);
        } else {
            $roles = \ConfigHelper::keys('models.user.role', ['abilities' => $request->input('abilities')]);
        }

        return $this->buildBy(
            \App\User::whereIn('role', $roles)->light()->sort(),
            ['filter' => ['search' => self::PROFILE_FILTER_SEARCH]],
            ['page' => $request->input('page', 1), 'filter' => ['search' => ['search' => $request->input('term')]]]
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getUserTags(): \Illuminate\Database\Eloquent\Collection
    {
        return \App\Directory::where('type', '=', 'user_tag')->sort()->get()->transform(function (\App\Directory $item) {
            return ['id' => $item->id, 'title' => $item->getTypeHandler()->renderShow($item)];
        })->keyBy('id');
    }
}
