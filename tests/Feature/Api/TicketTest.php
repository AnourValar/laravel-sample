<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class TicketTest extends TestCase
{
    use \AnourValar\LaravelAtom\Traits\OptimizeCheckerTrait;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @return void
     */
    public function test_index_success()
    {
        $user = $this->authUser('cabinet', ['role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true])]);
        \App\Ticket::query()->delete();

        // Iteration 1
        $this->get('/api/ticket')->assertStatus(200)->assertJsonPath('data', []);

        // Iteration 2
        $ticket1 = \App\Ticket::factory()->create(['author_id' => $user->id, 'created_at' => '2024-07-24 10:00']);
        $ticket2 = \App\Ticket::factory()->create(['author_id' => $user->id, 'created_at' => '2024-07-25 10:00']);
        \App\Ticket::factory()->create();
        \App\Ticket::factory()->create(['author_id' => $user->id])->delete();

        $this->post('/api/ticket')->assertStatus(200)->assertExactJson([
            'current_page' => 1,
            'current_page_url' => url('api/ticket?page=1'),
            'data' => [
                \App\Ticket::light()->find($ticket2->id)->toArray(),
                \App\Ticket::light()->find($ticket1->id)->toArray(),
            ],
            'first_page_url' => url('api/ticket?page=1'),
            'from' => 1,
            'next_page_url' => null,
            'path' => url('api/ticket'),
            'per_page' => 20,
            'prev_page_url' => null,
            'to' => 2,
        ]);

        // Iteration 3
        $this
            ->post(
                '/api/ticket',
                ['filter' => ['created_at' => ['>=' => '2024-07-25 00:00']]]
            )
            ->assertStatus(200)
            ->assertJsonPath('data', [
                \App\Ticket::light()->find($ticket2->id)->toArray(),
            ]);

        // Iteration 4
        $this
            ->post('/api/ticket', ['per_page' => 1, 'page' => 2, 'sort' => ['id' => 'ASC']])
            ->assertStatus(200)
            ->assertJsonPath('data', [\App\Ticket::light()->find($ticket2->id)->toArray()]);
    }

    /**
     * @return void
     */
    public function test_index_failure_not_auth()
    {
        $this
            ->get('/api/ticket')
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.', 'errors' => []]);
    }

    /**
     * @return void
     */
    public function test_index_failure_validation()
    {
        $this->authUser('cabinet.ticket.read');

        $this
            ->post('/api/ticket', ['filter' => ['foo' => ['=' => 'bar']]])
            ->assertStatus(422)
            ->assertJson(['errors' => ['filter.foo.=' => true]]);
    }

    /**
     * @return void
     */
    public function test_index_failure_acl()
    {
        $user = $this->authUser('cabinet.ticket.read');
        \App\Ticket::factory()->create();

        config(["models.user.role.{$user->role}.abilities" => ['foo']]);
        $this->get('/api/ticket')->assertStatus(403);

        config(["models.user.role.{$user->role}.abilities" => ['cabinet']]);
        $this->get('/api/ticket')->assertStatus(200)->assertJsonPath('data', []);
    }

    /**
     * @return void
     */
    public function test_edit_success()
    {
        $user = $this->authUser('cabinet');
        $ticket = \App\Ticket::factory()->create(['author_id' => $user->id]);

        $this->get('/api/ticket/' . $ticket->id)->assertStatus(200)->assertExactJson([
            'ticket' => \App\Ticket::heavy()->find($ticket->id)->toArray(),
        ]);
    }
    /**
     * @return void
     */
    public function test_edit_failure_not_auth()
    {
        $this
            ->get('/api/ticket/0')
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.', 'errors' => []]);
    }

    /**
     * @return void
     */
    public function test_edit_failure_not_found()
    {
        $user = $this->authUser('cabinet', ['role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true])]);
        $ticket1 = \App\Ticket::factory()->create();
        $ticket2 = tap(\App\Ticket::factory()->create(['author_id' => $user->id]))->delete();

        $this->get('/api/ticket/' . $ticket1->id)->assertStatus(404);
        $this->get('/api/ticket/' . $ticket2->id)->assertStatus(404);
        $this->get('/api/ticket/' . ($ticket2->id + 100))->assertStatus(404);
    }

    /**
     * @return void
     */
    public function test_edit_failure_acl()
    {
        $user = $this->authUser('cabinet');
        $ticket = \App\Ticket::factory()->create();

        config(["models.user.role.{$user->role}.abilities" => ['foo']]);
        $this->get('/api/ticket/' . $ticket->id)->assertStatus(403);

        config(["models.user.role.{$user->role}.abilities" => ['cabinet']]);
        $this->get('/api/ticket/' . $ticket->id)->assertStatus(404);
    }

    /**
     * @return void
     */
    public function test_create_success()
    {
        \Illuminate\Database\Eloquent\Model::preventSilentlyDiscardingAttributes(false);
        $user = $this->authUser('cabinet', ['role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true])]);
        $ticketId = (int) \App\Ticket::max('id');

        $this
            ->post(
                '/api/ticket/create',
                [
                    'ticket' => [
                        'author_id' => $user->id,
                        'moderator_id' => ['foo'], // no
                        'type' => 'user_request',
                        'data_request' => ['message' => 'Foo'],
                        'data_response' => ['foo'], // no
                        'status' => ['foo'], // no
                    ],
                ]
            )
            ->assertStatus(200)
            ->assertJson(['ticket' => ['id' => true]]);

        $ticket = \App\Ticket::latest('id')->first();
        $this->assertGreaterThan($ticketId, $ticket->id);
        $this->assertSame(
            $ref = [
                'id' => $ticket->id,
                'author_id' => $user->id,
                'moderator_id' => null,
                'type' => 'user_request',
                'data_request' => ['message' => 'Foo'],
                'data_response' => null,
                'status' => 'new',
            ],
            json_decode(json_encode($ticket->only(array_keys($ref))), true)
        );
        \Illuminate\Database\Eloquent\Model::preventSilentlyDiscardingAttributes(true);
    }

    /**
     * @return void
     */
    public function test_create_success_not_auth()
    {
        $ticketId = (int) \App\Ticket::max('id');

        $this
            ->post(
                '/api/ticket/create',
                [
                    'ticket' => [
                        'type' => 'guest_request',
                        'data_request' => ['email' => 'foo@example.org'],
                    ],
                ]
            )
            ->assertStatus(200)
            ->assertJson(['ticket' => ['id' => true]]);

        $ticket = \App\Ticket::latest('id')->first();
        $this->assertGreaterThan($ticketId, $ticket->id);
        $this->assertSame(
            $ref = [
                'id' => $ticket->id,
                'author_id' => null,
                'type' => 'guest_request',
                'data_request' => ['email' => 'foo@example.org'],
            ],
            json_decode(json_encode($ticket->only(array_keys($ref))), true)
        );
    }

    /**
     * @return void
     */
    public function test_create_failure_validation()
    {
        $this->authUser('cabinet');
        $ticketId = (int) \App\Ticket::max('id');

        $this
            ->post(
                '/api/ticket/create',
                [
                    'ticket' => [
                        'author_id' => ['foo'],
                    ],
                ]
            )
            ->assertStatus(422)
            ->assertJson(['errors' => ['ticket.author_id' => true]]);

        $this->assertSame($ticketId, (int) \App\Ticket::max('id'));
    }

    /**
     * @return void
     */
    public function test_create_failure_acl_1()
    {
        $user = $this->authUser('cabinet.ticket.create', ['role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true])]);
        config(["models.user.role.{$user->role}.abilities" => ['foo']]);
        $ticketId = (int) \App\Ticket::max('id');

        $this
            ->post('/api/ticket/create', ['ticket' => ['author_id' => $user->id, 'type' => 'user_request', 'data_request' => ['message' => 'foo']]])
            ->assertStatus(403);

        $this->assertSame($ticketId, (int) \App\Ticket::max('id'));
    }

    /**
     * @return void
     */
    public function test_create_failure_acl_2()
    {
        $this->authUser('cabinet', ['role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true])]);
        $user2 = \App\User::factory()->create(['role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true])]);
        $ticketId = (int) \App\Ticket::max('id');

        $this->post('/api/ticket/create', ['ticket' => ['author_id' => $user2->id, 'type' => 'user_moderate']])->assertStatus(403);

        $this->assertSame($ticketId, (int) \App\Ticket::max('id'));
    }

    /**
     * @return void
     */
    public function test_update_success()
    {
        \Illuminate\Database\Eloquent\Model::preventSilentlyDiscardingAttributes(false);
        $user = $this->authUser('cabinet.ticket.update');
        $ticket = \App\Ticket::factory()->create([
            'type' => 'user_moderate',
            'moderator_id' => null,
            'data_response' => null,
            'status' => 'new',
        ]);

        $this
            ->post(
                '/api/ticket/update/' . $ticket->id,
                [
                    'ticket' => [
                        'author_id' => ['foo'], // no
                        'moderator_id' => $user->id,
                        'type' => ['foo'], // no
                        'data_request' => ['foo'], // no
                        'data_response' => ['fields' => ['profile_company' => 'oops']],
                        'status' => 'in_work',
                    ],
                ],
            )
            ->assertStatus(200)
            ->assertJson(['ticket' => ['id' => $ticket->id]]);

        $this->assertSame(
            $ref = [
                'id' => $ticket->id,
                'author_id' => $ticket->author_id,
                'moderator_id' => $user->id,
                'type' => 'user_moderate',
                'data_request' => null,
                'data_response' => ['fields' => ['profile_company' => 'oops']],
                'status' => 'in_work',
            ],
            json_decode(json_encode($ticket->refresh()->only(array_keys($ref))), true)
        );
        \Illuminate\Database\Eloquent\Model::preventSilentlyDiscardingAttributes(true);
    }

    /**
     * @return void
     */
    public function test_update_failure_not_auth()
    {
        $this
            ->post('/api/ticket/update/0', ['ticket' => ['foo' => 'bar']])
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.', 'errors' => []]);
    }

    /**
     * @return void
     */
    public function test_update_failure_not_found()
    {
        $user = $this->authUser('cabinet.ticket.update');
        $ticket1 = tap(\App\Ticket::factory()->create())->delete();

        $this->post('/api/ticket/update/' . $ticket1->id, ['ticket' => ['moderator_id' => $user->id]])->assertStatus(404);
        $this->post('/api/ticket/update/' . ($ticket1->id + 100), ['ticket' => ['moderator_id' => $user->id]])->assertStatus(404);
    }

    /**
     * @return void
     */
    public function test_update_failure_validation()
    {
        $this->authUser('cabinet.ticket.update');
        $ticket = \App\Ticket::factory()->create()->refresh();

        $this
            ->post(
                '/api/ticket/update/' . $ticket->id,
                [
                    'ticket' => [
                        'moderator_id' => ['foo'],
                    ],
                ]
            )
            ->assertStatus(422)
            ->assertJson(['errors' => ['ticket.moderator_id' => true]]);

        $this->assertSame($ticket->toArray(), $ticket->refresh()->toArray());
    }

    /**
     * @return void
     */
    public function test_update_failure_acl()
    {
        $user = $this->authUser('cabinet.ticket.update');
        $ticket = \App\Ticket::factory()->create(['status' => new \App\Ticket()->status])->refresh();

        // Iteration 1
        config(["models.user.role.{$user->role}.abilities" => ['foo']]);
        $this
            ->post('/api/ticket/update/' . $ticket->id, ['ticket' => ['moderator_id' => $user->id]])
            ->assertStatus(403);

        // Iteration 2
        config(["models.user.role.{$user->role}.abilities" => ['cabinet']]);
        $this
            ->post('/api/ticket/update/' . $ticket->id, ['ticket' => ['moderator_id' => $user->id]])
            ->assertStatus(404);

        $this->assertSame($ticket->toArray(), $ticket->refresh()->toArray());

        // Iteration 3
        config(["models.user.role.{$user->role}.abilities" => ['cabinet', 'cabinet.ticket.read']]);
        $this
            ->post('/api/ticket/update/' . $ticket->id, ['ticket' => ['moderator_id' => $user->id]])
            ->assertStatus(403);

        $this->assertSame($ticket->toArray(), $ticket->refresh()->toArray());
    }
}
