<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    use \AnourValar\EloquentRequest\ControllerTrait;

    /**
     * Profile
     *
     * @var array
     */
    protected $profile = [
        'filter' => [
            'author_id' => self::PROFILE_FILTER_ID,
            'type' => self::PROFILE_FILTER_ID,
            'status' => self::PROFILE_FILTER_ID,
            'moderator_id' => self::PROFILE_FILTER_ID + self::PROFILE_FILTER_IS_NULL,
            'created_at' => self::PROFILE_FILTER_DATE,
        ],

        'relation' => [

        ],

        'scope' => [

        ],

        'sort' => [
            'id', 'closed_at',
        ],

        'ranges' => [

        ],

        'options' => [
            \AnourValar\EloquentRequest\Builders\FilterAndScopeBuilder::OPTION_GROUP_RELATION,

            \AnourValar\EloquentRequest\Actions\PaginateAction::OPTION_SIMPLE_PAGINATE,
            \AnourValar\EloquentRequest\Actions\PaginateAction::OPTION_PAGE_MAX => 20,
        ],

        'default_request' => [
            'sort' => ['id' => 'DESC'],
        ],
    ];

    /**
     * Интерфейс: Список тикетов
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request)
    {
        $tickets = $this->buildBy(
            \App\Ticket::light()
        );

        $request = $this->getBuildRequest();
        $request['authors'] = \App\User::find($request->filter('author_id.in'));
        $request['moderators'] = \App\User::find($request->filter('moderator_id.in'));

        return view('admin.ticket.index', compact('tickets', 'request'));
    }

    /**
     * Интерфейс: Карточка тикета
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Ticket $ticket
     * @return \Illuminate\Contracts\View\View
     */
    public function edit(Request $request, \App\Ticket $ticket)
    {
        $ticket->load(['author', 'moderator', 'fileVirtuals' => fn ($query) => $query->with('entitable', 'filePhysical')]);

        return view('admin.ticket.edit', ['ticket' => $ticket, 'menuRoute' => 'admin.ticket.index']);
    }

    /**
     * Действие: Изменение тикета
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Ticket $ticket
     * @param \App\Services\TicketService $ticketService
     * @return array
     */
    public function update(Request $request, \App\Ticket $ticket, \App\Services\TicketService $ticketService): array
    {
        return \DB::transaction(function () use ($request, $ticket, $ticketService) {
            $ticketService->lock($ticket);

            $ticket
                ->refresh()
                ->fields(['moderator_id', 'data_response', 'status'])
                ->fill((array) $request->input('ticket'))
                ->validate('ticket')
                ->authorize(null)
                ->save();

            $request->session()->flash('alert.primary', trans('controllers/admin/ticket.update'));

            return ['reload' => true];
        });
    }
}
