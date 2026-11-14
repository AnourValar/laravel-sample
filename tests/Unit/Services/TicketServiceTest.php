<?php

namespace Tests\Unit\Services;

use Tests\TestCase;

class TicketServiceTest extends TestCase
{
    use \AnourValar\LaravelAtom\Traits\OptimizeCheckerTrait;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @return void
     */
    public function test_lock_by_author()
    {
        $ticket = new \App\Ticket()->forceFill(['id' => 10001, 'author_id' => 555]);

        \Atom::spy();
        \App::make(\App\Services\TicketService::class)->lock($ticket);

        \Atom::shouldHaveReceived('lockUser')->once()->with(555, 'ticket');
        \Atom::shouldNotHaveReceived('lockTicket');
    }

    /**
     * @return void
     */
    public function test_lock_by_ticket_when_no_author()
    {
        $ticket = new \App\Ticket()->forceFill(['id' => 10002, 'author_id' => null]);

        \Atom::spy();
        \App::make(\App\Services\TicketService::class)->lock($ticket);

        \Atom::shouldNotHaveReceived('lockUser');
        \Atom::shouldHaveReceived('lockTicket')->once()->with(10002);
    }

    /**
     * @return void
     */
    public function test_lock_skips_unsaved_ticket_without_author()
    {
        $ticket = new \App\Ticket()->forceFill(['author_id' => null]);

        \Atom::spy();
        \App::make(\App\Services\TicketService::class)->lock($ticket);

        \Atom::shouldNotHaveReceived('lockUser');
        \Atom::shouldNotHaveReceived('lockTicket');
    }

    /**
     * @return void
     */
    public function test_lock_skips_null()
    {
        \Atom::spy();
        \App::make(\App\Services\TicketService::class)->lock(null);

        \Atom::shouldNotHaveReceived('lockUser');
        \Atom::shouldNotHaveReceived('lockTicket');
    }
}
