<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use \AnourValar\EloquentRequest\ControllerTrait;

    /**
     * Profile
     *
     * @var array
     */
    protected $profile = [
        'filter' => [
            'type' => self::PROFILE_FILTER_ID,
            'read_at' => self::PROFILE_FILTER_IS_NULL,
        ],

        'relation' => [

        ],

        'scope' => [

        ],

        'sort' => [
             'id', 'created_at',
        ],

        'ranges' => [

        ],

        'options' => [
            \AnourValar\EloquentRequest\Builders\FilterAndScopeBuilder::OPTION_GROUP_RELATION,

            \AnourValar\EloquentRequest\Actions\CursorPaginateAction::OPTION_APPLY,
        ],

        'default_request' => [
            'per_page' => 20,
            'sort' => ['created_at' => 'DESC', 'id' => 'DESC'],
        ],
    ];

    /**
     * Веб-сервис: Список уведомлений
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Pagination\CursorPaginator
     */
    public function index(Request $request): \Illuminate\Pagination\CursorPaginator
    {
        return $this->buildBy(
            $this
                ->getBuilder($request)
                ->select(config('models.user.notification_light'))
                ->withCasts(['type' => 'string'])
        );
    }

    /**
     * Веб-сервис: Карточка уведомления
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function edit(Request $request): array
    {
        $notification = $this
            ->getBuilder($request)
            ->select(config('models.user.notification_heavy'))
            ->findOrFail($request->route('notification'));

        return ['notification' => $notification];
    }

    /**
     * Веб-сервис: Фиксация прочтения уведомления
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function markRead(Request $request): array
    {
        $notification = tap($this->getBuilder($request)->findOrFail($request->route('notification')))->markAsRead();

        return ['notification' => $notification->only('id')];
    }

    /**
     * Веб-сервис: Фиксация прочтения всех уведомлений
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function markReadAll(Request $request): array
    {
        $this->getBuilder($request)->whereNull('read_at')->update(['read_at' => now()]);

        return ['notifications' => true];
    }

    /**
     * Веб-сервис: Отправка персонального уведомления (сообщения)
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function personalMessage(Request $request): array
    {
        $data = $request->validate(['message' => ['required', 'not_empty', 'string', 'max:2000']]);

        $user = \App\User::acl()->findOrFail($request->route('user'));
        $user->authorize('notify');

        $user->notify(new \App\Notifications\Trigger\PersonalMessageNotification($data['message']));
        return ['user' => ['id' => $user->id]];
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function getBuilder(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        return \Illuminate\Notifications\DatabaseNotification::query()
            ->where('notifiable_type', '=', 'user')
            ->whereHas('notifiable', function ($query) use ($request) {
                $query->where('id', '=', $request->user()->id);
            });
    }
}
