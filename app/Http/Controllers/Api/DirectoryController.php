<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DirectoryBatchRequest;
use Illuminate\Http\Request;

class DirectoryController extends Controller
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
            'user_id' => self::PROFILE_FILTER_ID + self::PROFILE_FILTER_IS_NULL,
            'search' => self::PROFILE_FILTER_SEARCH,
        ],

        'relation' => [

        ],

        'scope' => [

        ],

        'sort' => [
            'id',
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
            'sort' => ['id' => 'ASC'],
        ],
    ];

    /**
     * Веб-сервис: Список справочников
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Pagination\Paginator
     */
    public function index(Request $request): \Illuminate\Pagination\Paginator
    {
        return $this->buildBy(
            \App\Directory::acl()->light()
        );
    }

    /**
     * Веб-сервис: Карточка справочника
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function edit(Request $request): array
    {
        return ['directory' => \App\Directory::acl()->heavy()->findOrFail($request->route('directory'))];
    }

    /**
     * Веб-сервис: Создание справочника
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function create(Request $request): array
    {
        return \DB::transaction(function () use ($request) {
            \Atom::lockUser($request->input('directory.user_id') ?? 0, 'directory');

            $directory = \App\Directory::query()
                ->fields(['type', 'user_id', 'data'])
                ->fill((array) $request->input('directory'))
                ->validate('directory')
                ->authorize('create')
                ->save();

            return ['directory' => ['id' => $directory->id]];
        });
    }

    /**
     * Веб-сервис: Изменение справочника
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function update(Request $request): array
    {
        return \DB::transaction(function () use ($request) {
            \Atom::lockUser(\App\Directory::findOrFail($request->route('directory'))->user_id ?? 0, 'directory');

            $directory = \App\Directory::query()
                ->acl()
                ->findOrFail($request->route('directory'))
                ->fields(['data'])
                ->fill((array) $request->input('directory'))
                ->validate('directory')
                ->authorize('update')
                ->save();

            return ['directory' => ['id' => $directory->id]];
        });
    }

    /**
     * Веб-сервис: Удаление справочника
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function delete(Request $request): array
    {
        return \DB::transaction(function () use ($request) {
            \Atom::lockUser(0, 'directory'); // attach/detach/set
            \Atom::lockUser(\App\Directory::findOrFail($request->route('directory'))->user_id ?? 0, 'directory');

            $directory = \App\Directory::query()
                ->acl()
                ->findOrFail($request->route('directory'))
                ->validateDelete()
                ->authorize('delete')
                ->delete();

            return ['directory' => ['id' => $directory->id]];
        });
    }

    /**
     * Веб-сервис: Прикрепление справочников к сущностям (merge)
     *
     * @param \App\Http\Requests\DirectoryBatchRequest $request
     * @return array
     */
    public function attach(DirectoryBatchRequest $request): array
    {
        return $this->batch(
            $request,
            fn (array $current, array $value): array => array_merge($current, $value)
        );
    }

    /**
     * Веб-сервис: Открепление справочников от сущностей (diff)
     *
     * @param \App\Http\Requests\DirectoryBatchRequest $request
     * @return array
     */
    public function detach(DirectoryBatchRequest $request): array
    {
        return $this->batch(
            $request,
            fn (array $current, array $value): array => array_diff($current, $value)
        );
    }

    /**
     * Веб-сервис: Замена справочников у сущностей (set)
     *
     * @param \App\Http\Requests\DirectoryBatchRequest $request
     * @return array
     */
    public function set(DirectoryBatchRequest $request): array
    {
        return $this->batch(
            $request,
            fn (array $current, array $value): array => $value
        );
    }

    /**
     * Пакетное обновление привязки справочников у сущностей
     *
     * @param \App\Http\Requests\DirectoryBatchRequest $request
     * @param callable $resolver
     * @return array
     */
    private function batch(DirectoryBatchRequest $request, callable $resolver): array
    {
        $entity = $request->route('entity');
        $class = \Illuminate\Database\Eloquent\Relations\Relation::morphMap()[$entity];
        $fields = \Arr::except($request->validated(), 'ids');

        return \DB::transaction(function () use ($fields, $resolver, $class, $request, $entity) {
            \Atom::lockUser(0, 'directory');

            $ids = [];
            foreach ($class::acl()->whereIn('id', $request->input('ids'))->cursor() as $model) {
                $data = [];
                foreach ($fields as $key => $value) {
                    $data[$key] = array_values(array_unique($resolver((array) $model->$key, (array) $value)));
                }

                $model
                    ->fields(array_keys($data))
                    ->fill($data)
                    ->validate()
                    ->authorize('update')
                    ->save();

                $ids[] = $model->id;
            }

            sort($ids);
            return [$entity => ['ids' => $ids]];
        });
    }
}
