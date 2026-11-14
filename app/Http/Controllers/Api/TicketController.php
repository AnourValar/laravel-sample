<?php

namespace App\Http\Controllers\Api;

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
            'moderator_id' => self::PROFILE_FILTER_ID + self::PROFILE_FILTER_IS_NULL,
            'type' => self::PROFILE_FILTER_ID,
            'status' => self::PROFILE_FILTER_ID,
            'created_at' => self::PROFILE_FILTER_DATE,
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
            'sort' => ['id' => 'DESC'],
        ],
    ];

    /**
     * Веб-сервис: Список тикетов
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Pagination\Paginator
     */
    public function index(Request $request): \Illuminate\Pagination\Paginator
    {
        return $this->buildBy(
            \App\Ticket::acl()->light()
        );
    }

    /**
     * Веб-сервис: Карточка тикета
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function edit(Request $request): array
    {
        return ['ticket' => \App\Ticket::acl()->heavy()->findOrFail($request->route('ticket'))];
    }

    /**
     * Веб-сервис: Создание тикета
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Services\TicketService $ticketService
     * @return array
     */
    public function create(Request $request, \App\Services\TicketService $ticketService): array
    {
        return \DB::transaction(function () use ($request, $ticketService) {
            $ticketService->lock(new \App\Ticket()->forceFill(['author_id' => $request->input('ticket.author_id')]));

            $ticket = \App\Ticket::query()
                ->fields(['author_id', 'type', 'data_request'])
                ->fill((array) $request->input('ticket'))
                ->validate('ticket')
                ->authorize('create')
                ->save();

            return ['ticket' => ['id' => $ticket->id]];
        });
    }

    /**
     * Веб-сервис: Изменение тикета
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Services\TicketService $ticketService
     * @return array
     */
    public function update(Request $request, \App\Services\TicketService $ticketService): array
    {
        return \DB::transaction(function () use ($request, $ticketService) {
            $ticketService->lock(\App\Ticket::findOrFail($request->route('ticket')));

            $ticket = \App\Ticket::query()
                ->acl()
                ->findOrFail($request->route('ticket'))
                ->fields(['moderator_id', 'data_response', 'status'])
                ->fill((array) $request->input('ticket'))
                ->validate('ticket')
                ->authorize('update')
                ->save();

            return ['ticket' => ['id' => $ticket->id]];
        });
    }
}
