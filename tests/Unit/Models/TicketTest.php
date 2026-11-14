<?php

namespace Tests\Unit\Models;

use Tests\TestCase;

class TicketTest extends TestCase
{
    use \AnourValar\LaravelAtom\Traits\OptimizeCheckerTrait;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @return void
     */
    public function test_scope_light_heavy()
    {
        \Date::setTestNow('2024-07-20 21:00:00');

        $ticket = \App\Ticket::factory()->make([
            'author_id' => $authorId = \App\User::factory()->create(['role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true])])->id,
            'moderator_id' => $moderatorId = \App\User::factory()->create(['role' => \ConfigHelper::randomKey('models.user.role', ['is_staff' => true])])->id,
            'type' => 'user_moderate',
            'data_request' => null,
            'data_response' => ['fields' => ['profile_form' => 'foo']],
            'status' => 'new',
            'created_at' => now(),
        ]);
        $ticket->validate()->save();
        $fileVirtual1 = $this->createFile($ticket, 'attachment_request');
        $fileVirtual2 = $this->createFile($ticket, 'attachment_response');

        // light
        $this->assertSame(
            [
                'id' => $ticket->id,
                'author_id' => $authorId,
                'moderator_id' => $moderatorId,
                'type' => 'user_moderate',
                'data_request' => null,
                'status' => 'new',
                'closed_at' => null,
                'created_at' => '2024-07-20T21:00:00.000000Z',
                'author' => $ticket->author()->light()->first()->toArray(),
                'moderator' => $ticket->moderator()->light()->first()->toArray(),
                'file_virtuals' => [
                    \App\FileVirtual::light()->find($fileVirtual1->id)->toArray(),
                    \App\FileVirtual::light()->find($fileVirtual2->id)->toArray(),
                ],
            ],
            \App\Ticket::light()->find($ticket->id)->toArray()
        );

        // heavy
        $this->assertSame(
            [
                'id' => $ticket->id,
                'author_id' => $authorId,
                'moderator_id' => $moderatorId,
                'type' => 'user_moderate',
                'data_request' => null,
                'status' => 'new',
                'closed_at' => null,
                'created_at' => '2024-07-20T21:00:00.000000Z',
                'data_response' => ['fields' => ['profile_form' => 'foo']],
                'author' => $ticket->author()->light()->first()->toArray(),
                'moderator' => $ticket->moderator()->light()->first()->toArray(),
                'file_virtuals' => [
                    \App\FileVirtual::light()->find($fileVirtual1->id)->toArray(),
                    \App\FileVirtual::light()->find($fileVirtual2->id)->toArray(),
                ],
            ],
            \App\Ticket::heavy()->find($ticket->id)->toArray()
        );
    }

    /**
     * @return void
     */
    public function test_jsonNested_data_request()
    {
        $this->assertSame(
            [
                'bar' => '2',
                'foo' => '1',
            ],
            (new \App\Ticket())->forceFill(['data_request' => [
                'foo' => 1,
                'bar' => '2',
                'c' => ' ',
            ]])->data_request
        );


        $this->assertNull(
            (new \App\Ticket())->forceFill(['data_request' => [
                'foo' => '',
            ]])->data_request
        );
    }

    /**
     * @return void
     */
    public function test_jsonNested_data_response()
    {
        $this->assertSame(
            [
                'bar' => '2',
                'foo' => '1',
            ],
            (new \App\Ticket())->forceFill(['data_response' => [
                'foo' => 1,
                'bar' => '2',
                'c' => ' ',
            ]])->data_response
        );


        $this->assertNull(
            (new \App\Ticket())->forceFill(['data_response' => [
                'foo' => '',
            ]])->data_response
        );
    }

    /**
     * @return void
     */
    public function test_validate_author_id()
    {
        $nonCustomer = \App\User::factory()->create(['role' => 'admin', 'additional_abilities' => []]);
        $ticket = \App\Ticket::factory()->make(['author_id' => $nonCustomer->id, 'type' => 'user_request', 'status' => 'new']);

        $this->assertValidationFailed($ticket, 'author_id');
    }

    /**
     * @return void
     */
    public function test_validate_moderator_id()
    {
        $author = \App\User::factory()->create(['role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true])]);
        $nonStaff = \App\User::factory()->create(['role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true])]);
        $ticket = \App\Ticket::factory()->make([
            'author_id' => $author->id,
            'moderator_id' => $nonStaff->id,
            'status' => new \App\Ticket()->status,
        ]);

        $this->assertValidationFailed($ticket, 'moderator_id');
    }

    /**
     * @return void
     */
    public function test_validate_unchangeable_when_closed()
    {
        $ticket = \App\Ticket::factory()->create(['status' => 'approve'])->refresh();
        $this->assertNotNull($ticket->closed_at);

        $newModerator = \App\User::factory()->create(['role' => \ConfigHelper::randomKey('models.user.role', ['is_staff' => true])]);
        $ticket->forceFill(['moderator_id' => $newModerator->id]);

        $this->assertValidationFailed($ticket, 'moderator_id');
    }

    /**
     * @return void
     */
    public function test_acl()
    {
        \App\Ticket::query()->delete();
        $role = \ConfigHelper::randomKey('models.user.role');
        $user1 = \App\User::factory()->create(['role' => $role, 'additional_abilities' => []]);
        $user2 = \App\User::factory()->create(['role' => $role, 'additional_abilities' => []]);

        $ticket1 = \App\Ticket::factory()->create(['author_id' => $user1->id]);
        $ticket2 = \App\Ticket::factory()->create(['author_id' => $user2->id]);


        // Iteration 1
        config(["models.user.role.{$role}.abilities" => []]);
        $this->assertSame(
            [],
            \App\Ticket::acl($user1)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );

        $this->assertSame(
            [],
            \App\Ticket::acl($user2)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );


        // Iteration 2
        config(["models.user.role.{$role}.abilities" => ['cabinet']]);
        $this->assertSame(
            [$ticket1->id],
            \App\Ticket::acl($user1)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );

        $this->assertSame(
            [$ticket2->id],
            \App\Ticket::acl($user2)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );


        // Iteration 3
        config(["models.user.role.{$role}.abilities" => ['cabinet.ticket.read']]);
        $this->assertSame(
            [$ticket1->id, $ticket2->id],
            \App\Ticket::acl($user1)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );

        $this->assertSame(
            [$ticket1->id, $ticket2->id],
            \App\Ticket::acl($user2)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );
    }
}
