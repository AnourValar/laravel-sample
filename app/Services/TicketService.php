<?php

namespace App\Services;

class TicketService
{
    /**
     * Пессимистическая блокировка
     *
     * @param \App\Ticket|null $ticket
     * @return void
     */
    public function lock(?\App\Ticket $ticket): void
    {
        if ($ticket?->author_id) {
            \Atom::lockUser($ticket->author_id, 'ticket');
        } elseif ($ticket?->id) {
            \Atom::lockTicket($ticket->id);
        }
    }
}
