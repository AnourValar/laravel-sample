<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
            'id', 'user_id',
        ],

        'ranges' => [

        ],

        'options' => [
            \AnourValar\EloquentRequest\Builders\FilterAndScopeBuilder::OPTION_GROUP_RELATION,

            \AnourValar\EloquentRequest\Actions\PaginateAction::OPTION_SIMPLE_PAGINATE,
            \AnourValar\EloquentRequest\Actions\PaginateAction::OPTION_PAGE_MAX => 20,
        ],

        'default_request' => [
            'sort' => ['id' => 'ASC'],
        ],
    ];

    /**
     * Интерфейс: Список справочников
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request)
    {
        $directories = $this->buildBy(
            \App\Directory::light()->with('user')
        );

        $request = $this->getBuildRequest();
        $request['users'] = \App\User::find($request->filter('user_id.in'));

        return view('admin.directory.index', compact('directories', 'request'));
    }

    /**
     * Интерфейс: Создание справочника
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function add(Request $request)
    {
        $directory = new \App\Directory();

        return view('admin.directory.add', compact('directory'));
    }

    /**
     * AJAX: Карточка справочника
     *
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    public function renderType(Request $request): string
    {
        $directory = new \App\Directory();

        $value = $request->input('value');
        if (is_string($value) && config("models.directory.type.{$value}")) {
            $directory->type = $value;
        } else {
            return '-';
        }

        $handler = $directory->getTypeHandler();
        if (! $handler instanceof \App\Handlers\Models\Directory\Type\RenderInterface) {
            return '-';
        }

        return $handler->renderEdit($directory, 'directory[data]');
    }

    /**
     * Действие: Создание справочника
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
                ->authorize(null)
                ->save();

            $request->session()->flash('alert.success', trans('controllers/admin/directory.create'));
            return ['redirect' => route('admin.directory.edit', $directory)];
        });
    }

    /**
     * Интерфейс: Карточка справочника
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Directory $directory
     * @return \Illuminate\Contracts\View\View
     */
    public function edit(Request $request, \App\Directory $directory)
    {
        $directory->load('user');

        return view('admin.directory.edit', ['directory' => $directory, 'menuRoute' => 'admin.directory.index']);
    }

    /**
     * Действие: Изменение справочника
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Directory $directory
     * @return array
     */
    public function update(Request $request, \App\Directory $directory): array
    {
        return \DB::transaction(function () use ($request, $directory) {
            \Atom::lockUser($directory->user_id ?? 0, 'directory');

            $directory
                ->refresh()
                ->fields(['data'])
                ->fill((array) $request->input('directory'))
                ->validate('directory')
                ->authorize(null)
                ->save();

            $request->session()->flash('alert.primary', trans('controllers/admin/directory.update'));
            return ['reload' => true];
        });
    }

    /**
     * Действие: Удаление справочника
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Directory $directory
     * @return array
     */
    public function delete(Request $request, \App\Directory $directory): array
    {
        return \DB::transaction(function () use ($request, $directory) {
            \Atom::lockUser(0, 'directory'); // attach/detach/set
            \Atom::lockUser($directory->user_id ?? 0, 'directory');

            $directory
                ->refresh()
                ->validateDelete()
                ->authorize(null)
                ->delete();

            $request->session()->flash('alert.warning', trans('controllers/admin/directory.delete'));
            return ['redirect' => route('admin.directory.index')];
        });
    }
}
