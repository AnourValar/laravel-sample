<?php

namespace Tests\Unit\Policies;

use Tests\TestCase;

class TicketPolicyTest extends TestCase
{
    use \AnourValar\LaravelAtom\Traits\OptimizeCheckerTrait;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @return void
     */
    public function test_create()
    {
        $role = \ConfigHelper::randomKey('models.user.role');
        $user1 = \App\User::factory()->create(['role' => $role, 'additional_abilities' => []]);
        $user2 = \App\User::factory()->create(['role' => $role, 'additional_abilities' => []]);

        $ticket1 = \App\Ticket::factory()->make(['author_id' => $user1->id]);
        $ticket2 = \App\Ticket::factory()->make(['author_id' => $user2->id]);
        $ticket3 = \App\Ticket::factory()->make(['author_id' => null]);

        // Iteration 0
        $policy = \App::make(\App\Policies\TicketPolicy::class);
        $this->assertFalse($policy->create(null, new \App\Ticket()->forceFill(['author_id' => 1, 'type' => $ticket1->type])));
        $this->assertTrue($policy->create(null, new \App\Ticket()->forceFill(['author_id' => null, 'type' => $ticket1->type])));

        // Iteration 1
        config(["models.user.role.{$role}.abilities" => []]);
        $this->assertFalse($user1->can('create', $ticket1));
        $this->assertFalse($user1->can('create', $ticket2));
        $this->assertTrue($user1->can('create', $ticket3));

        // Iteration 2
        config(["models.user.role.{$role}.abilities" => ['cabinet']]);
        $this->assertTrue($user1->can('create', $ticket1));
        $this->assertFalse($user1->can('create', $ticket2));
        $this->assertTrue($user1->can('create', $ticket3));

        // Iteration 3
        config(["models.user.role.{$role}.abilities" => ['cabinet.ticket.create']]);
        $this->assertTrue($user1->can('create', $ticket1));
        $this->assertTrue($user1->can('create', $ticket2));
        $this->assertTrue($user1->can('create', $ticket3));

        // Iteration 4: not manual
        config(["models.ticket.type.{$ticket1->type}.is_manual" => false]);
        $this->assertFalse($user1->can('create', $ticket1));
    }

    /**
     * @return void
     */
    public function test_create_singleton()
    {
        $role = \ConfigHelper::randomKey('models.user.role');
        $user1 = \App\User::factory()->create(['role' => $role, 'additional_abilities' => ['cabinet']]);

        // Iteration 1
        $ticket = \App\Ticket::factory()->create(['author_id' => $user1->id, 'status' => new \App\Ticket()->status]);
        config(["models.ticket.type.{$ticket->type}.is_singleton" => false]);
        $this->assertTrue($user1->can('create', \App\Ticket::factory()->make(['type' => $ticket->type, 'author_id' => $ticket->author_id, 'status' => new \App\Ticket()->status])));

        // Iteration 2
        config(["models.ticket.type.{$ticket->type}.is_singleton" => true]);
        $this->assertFalse($user1->can('create', \App\Ticket::factory()->make(['type' => $ticket->type, 'author_id' => $ticket->author_id, 'status' => new \App\Ticket()->status])));
    }

    /**
     * @return void
     */
    public function test_createUpload()
    {
        $role = \ConfigHelper::randomKey('models.user.role');
        $user1 = \App\User::factory()->create(['role' => $role, 'additional_abilities' => []]);
        $user2 = \App\User::factory()->create(['role' => $role, 'additional_abilities' => []]);

        $ticket1 = \App\Ticket::factory()->make(['author_id' => $user1->id, 'created_at' => now()]);
        $ticket2 = \App\Ticket::factory()->make(['author_id' => $user2->id, 'created_at' => now()->subMinute()]);

        // Iteration 1
        config(["models.user.role.{$role}.abilities" => []]);
        $this->assertFalse($user1->can('createUpload', $ticket1));
        $this->assertFalse($user1->can('createUpload', $ticket2));

        // Iteration 2
        config(["models.user.role.{$role}.abilities" => ['cabinet']]);
        $this->assertTrue($user1->can('createUpload', $ticket1));
        $this->assertFalse($user1->can('createUpload', $ticket2));

        // Iteration 3
        config(["models.user.role.{$role}.abilities" => ['cabinet.ticket.create']]);
        $this->assertTrue($user1->can('createUpload', $ticket1));
        $this->assertTrue($user1->can('createUpload', $ticket2));
    }

    /**
     * @return void
     */
    public function test_createUpload_expired()
    {
        $role = \ConfigHelper::randomKey('models.user.role');
        $user1 = \App\User::factory()->create(['role' => $role, 'additional_abilities' => []]);
        $user2 = \App\User::factory()->create(['role' => $role, 'additional_abilities' => []]);

        $ticket1 = \App\Ticket::factory()->make(['author_id' => $user1->id, 'created_at' => now()->subMinutes(2)]);
        $ticket2 = \App\Ticket::factory()->make(['author_id' => $user2->id, 'created_at' => now()->subMinutes(3)]);

        config(["models.user.role.{$role}.abilities" => ['cabinet.ticket.create']]);
        $this->assertFalse($user1->can('createUpload', $ticket1));
        $this->assertFalse($user1->can('createUpload', $ticket2));
    }

    /**
     * @return void
     */
    public function test_update()
    {
        $role = \ConfigHelper::randomKey('models.user.role');
        $user1 = \App\User::factory()->create(['role' => $role, 'additional_abilities' => []]);
        $user2 = \App\User::factory()->create(['role' => $role, 'additional_abilities' => []]);

        $ticket1 = \App\Ticket::factory()->make(['author_id' => $user1->id, 'data_response' => null]);
        $ticket2 = \App\Ticket::factory()->make(['author_id' => $user2->id, 'data_response' => null]);

        // Iteration 1
        config(["models.user.role.{$role}.abilities" => []]);
        $this->assertFalse($user1->can('update', $ticket1));
        $this->assertFalse($user1->can('update', $ticket2));

        // Iteration 2
        config(["models.user.role.{$role}.abilities" => ['cabinet']]);
        $this->assertFalse($user1->can('update', $ticket1));
        $this->assertFalse($user1->can('update', $ticket2));

        // Iteration 3
        config(["models.user.role.{$role}.abilities" => ['cabinet.ticket.update']]);
        $this->assertTrue($user1->can('update', $ticket1));
        $this->assertTrue($user1->can('update', $ticket2));
    }

    /**
     * @return void
     */
    public function test_update_closed()
    {
        \Date::setTestNow('2026-04-01 11:05:00');
        $role = \ConfigHelper::randomKey('models.user.role');
        $user1 = \App\User::factory()->create(['role' => $role, 'additional_abilities' => []]);
        config(["models.user.role.{$role}.abilities" => ['cabinet.ticket.update']]);

        // Iteration 1: not closed -> allowed
        $this->assertTrue($user1->can('update', \App\Ticket::factory()->make(['author_id' => $user1->id, 'data_response' => null, 'closed_at' => null])));

        // Iteration 2: closed within 2 minutes -> allowed
        $this->assertTrue($user1->can('update', \App\Ticket::factory()->make(['author_id' => $user1->id, 'data_response' => null, 'closed_at' => '2026-04-01 11:04:00'])));

        // Iteration 3: closed more than 2 minutes ago -> denied
        $this->assertFalse($user1->can('update', \App\Ticket::factory()->make(['author_id' => $user1->id, 'data_response' => null, 'closed_at' => '2026-04-01 11:02:00'])));
    }

    /**
     * @return void
     */
    public function test_update_data_response_without_moderator()
    {
        $staffRole = \ConfigHelper::randomKey('models.user.role', ['is_staff' => true]);
        config(["models.user.role.{$staffRole}.abilities" => ['cabinet.ticket.update']]);
        $moderator = \App\User::factory()->create(['role' => $staffRole, 'additional_abilities' => []]);

        // Iteration 1
        $ticket = \App\Ticket::factory()->create(['moderator_id' => null, 'data_response' => null]);
        $this->assertTrue($moderator->can('update', $ticket));

        // Iteration 2
        $ticket->data_response = ['foo'];
        $this->assertFalse($moderator->can('update', $ticket));

        // Iteration 3
        $ticket->moderator_id = $moderator->id;
        $this->assertTrue($moderator->can('update', $ticket));
    }
}
