<?php

namespace Tests\Unit\Observers;

use Tests\TestCase;

class TicketObserverTest extends TestCase
{
    use \AnourValar\LaravelAtom\Traits\OptimizeCheckerTrait;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @return void
     */
    public function test_closed_at_on_status_change()
    {
        $ticket = \App\Ticket::factory()->create(['status' => 'new']);
        $this->assertNull($ticket->refresh()->closed_at);

        // Iteration 1
        \Date::setTestNow('2024-07-20 21:00:00');
        $ticket->forceFill(['status' => 'approve'])->save();
        $this->assertSame('2024-07-20T21:00:00.000000Z', $ticket->refresh()->closed_at->toJSON());

        // Iteration 1
        \Date::setTestNow('2024-07-20 22:00:00');
        $ticket->forceFill(['status' => 'reject'])->save();
        $this->assertSame('2024-07-20T21:00:00.000000Z', $ticket->refresh()->closed_at->toJSON());
    }

    /**
     * @return void
     */
    public function test_deleting_file_virtuals()
    {
        $ticket = \App\Ticket::factory()->create();
        $fileVirtual = $this->createFile($ticket, 'attachment_response');

        $this->assertNotNull(\App\FileVirtual::find($fileVirtual->id));
        $ticket->delete();
        $this->assertNull(\App\FileVirtual::find($fileVirtual->id));
    }
}
