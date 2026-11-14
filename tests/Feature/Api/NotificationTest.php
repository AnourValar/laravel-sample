<?php

namespace Tests\Feature\Api;

use Illuminate\Notifications\DatabaseNotification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use \AnourValar\LaravelAtom\Traits\OptimizeCheckerTrait;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @return void
     */
    public function test_index_success()
    {
        $user1 = $this->authUser('cabinet');

        // Iteration 1
        $this->get('/api/notification')->assertStatus(200)->assertJsonPath('data', []);

        // Iteration 2
        $notification1 = $this->createDatabaseNotification($user1, ['read_at' => null, 'created_at' => '2025-08-08 09:00:00']);
        $notification2 = $this->createDatabaseNotification($user1, ['read_at' => '2025-07-24 18:00:00', 'created_at' => '2025-08-08 10:00:00']);
        $this->createDatabaseNotification($user1)->delete();
        $this->createDatabaseNotification();

        $this->post('/api/notification')->assertStatus(200)->assertExactJson([
            'data' => [
                DatabaseNotification::select(config('models.user.notification_light'))->find($notification2->id)->toArray(),
                DatabaseNotification::select(config('models.user.notification_light'))->find($notification1->id)->toArray(),
            ],
            'next_cursor' => null,
            'next_page_url' => null,
            'path' => url('api/notification'),
            'per_page' => 20,
            'prev_cursor' => null,
            'prev_page_url' => null,
        ]);

        // Iteration 3
        $this
            ->post(
                '/api/notification',
                ['filter' => ['read_at' => ['is-null' => true]]]
            )
            ->assertStatus(200)
            ->assertJsonPath('data', [DatabaseNotification::select(config('models.user.notification_light'))->find($notification1->id)->toArray()]);

        // Iteration 4
        $this
            ->post(
                '/api/notification',
                ['filter' => ['read_at' => ['is-null' => false]]]
            )
            ->assertStatus(200)
            ->assertJsonPath('data', [DatabaseNotification::select(config('models.user.notification_light'))->find($notification2->id)->toArray()]);

        // Iteration 5
        $response = $this
            ->post(
                '/api/notification',
                ['per_page' => 1]
            )
            ->assertStatus(200)
            ->assertJsonPath('data', [DatabaseNotification::select(config('models.user.notification_light'))->find($notification2->id)->toArray()])
            ->json();

        // Iteration 6
        $this
            ->post(
                '/api/notification',
                ['per_page' => 1, 'cursor' => $response['next_cursor']]
            )
            ->assertStatus(200)
            ->assertJsonPath('data', [DatabaseNotification::select(config('models.user.notification_light'))->find($notification1->id)->toArray()]);
    }

    /**
     * @return void
     */
    public function test_index_failure_not_auth()
    {
        $this
            ->get('/api/notification')
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.', 'errors' => []]);
    }

    /**
     * @return void
     */
    public function test_index_failure_validation()
    {
        $this->authUser('cabinet');

        $this
            ->post('/api/notification', ['filter' => ['foo' => ['=' => 'bar']]])
            ->assertStatus(422)
            ->assertJson(['errors' => ['filter.foo.=' => true]]);
    }

    /**
     * @return void
     */
    public function test_index_failure_acl()
    {
        $user = $this->authUser('cabinet');
        config(["models.user.role.{$user->role}.abilities" => ['foo']]);

        $this->get('/api/notification')->assertStatus(403);
    }

    /**
     * @return void
     */
    public function test_edit_success()
    {
        $user = $this->authUser('cabinet');
        $notification = $this->createDatabaseNotification($user);

        $this->get('/api/notification/' . $notification->id)->assertStatus(200)->assertExactJson([
            'notification' => DatabaseNotification::select(config('models.user.notification_heavy'))->find($notification->id)->toArray(),
        ]);
    }

    /**
     * @return void
     */
    public function test_edit_failure_not_auth()
    {
        $this
            ->get('/api/notification/foo')
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.', 'errors' => []]);
    }

    /**
     * @return void
     */
    public function test_edit_failure_not_found()
    {
        $user = $this->authUser('cabinet');

        $notification1 = tap($this->createDatabaseNotification($user))->delete();
        $notification2 = $this->createDatabaseNotification();

        $this->get('/api/notification/' . $notification1->id)->assertStatus(404);
        $this->get('/api/notification/' . $notification2->id)->assertStatus(404);
        $this->get('/api/notification/' . (mb_substr($notification2->id, 0, -1) . '2'))->assertStatus(404);
    }

    /**
     * @return void
     */
    public function test_edit_failure_acl_1()
    {
        $user = $this->authUser('cabinet');
        config(["models.user.role.{$user->role}.abilities" => ['foo']]);

        $this->get('/api/notification/0')->assertStatus(403);
    }

    /**
     * @return void
     */
    public function test_edit_failure_acl_2()
    {
        $user1 = $this->authUser('cabinet');
        $user2 = \App\User::factory()->create(['role' => $user1->role]);

        $notification = $this->createDatabaseNotification($user2);
        $this->get('/api/notification/' . $notification->id)->assertStatus(404);
    }

    /**
     * @return void
     */
    public function test_mark_read_success()
    {
        $user = $this->authUser('cabinet');
        $notification = $this->createDatabaseNotification($user, ['read_at' => null]);


        // Iteration 1
        \Date::setTestNow('2025-07-24 19:00:00');

        $this
            ->post('/api/notification/mark_read/' . $notification->id)
            ->assertStatus(200)
            ->assertJson(['notification' => ['id' => $notification->id]]);

        $this->assertSame(
            [
                'id' => $notification->id,
                'read_at' => '2025-07-24T19:00:00.000000Z',
            ],
            json_decode(json_encode($notification->refresh()->only('id', 'read_at')), true)
        );


        // Iteration 2
        \Date::setTestNow('2025-07-24 20:00:00');

        $this
            ->post('/api/notification/mark_read/' . $notification->id)
            ->assertStatus(200)
            ->assertJson(['notification' => ['id' => $notification->id]]);

        $this->assertSame(
            [
                'id' => $notification->id,
                'read_at' => '2025-07-24T19:00:00.000000Z',
            ],
            json_decode(json_encode($notification->refresh()->only('id', 'read_at')), true)
        );
    }

    /**
     * @return void
     */
    public function test_mark_read_failure_not_auth()
    {
        $this
            ->post('/api/notification/mark_read/0')
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.', 'errors' => []]);
    }

    /**
     * @return void
     */
    public function test_mark_read_failure_not_found()
    {
        $user = $this->authUser('cabinet');

        $notification1 = tap($this->createDatabaseNotification($user, ['read_at' => null]))->delete();
        $notification2 = $this->createDatabaseNotification(null, ['read_at' => null]);

        $this->post('/api/notification/mark_read/' . $notification1->id)->assertStatus(404);
        $this->post('/api/notification/mark_read/' . $notification2->id)->assertStatus(404);
    }

    /**
     * @return void
     */
    public function test_mark_read_failure_acl_1()
    {
        $user = $this->authUser('cabinet');
        config(["models.user.role.{$user->role}.abilities" => ['foo']]);
        $notification = $this->createDatabaseNotification($user, ['read_at' => null]);

        $this
            ->post('/api/notification/mark_read/' . $notification->id)
            ->assertStatus(403);

        $this->assertSame($notification->toArray(), $notification->refresh()->toArray());
    }

    /**
     * @return void
     */
    public function test_mark_read_failure_acl_2()
    {
        $user1 = $this->authUser('cabinet');
        $user2 = \App\User::factory()->create(['role' => $user1->role]);
        $notification = $this->createDatabaseNotification($user2, ['read_at' => null]);

        $this->post('/api/notification/mark_read/' . $notification->id)->assertStatus(404);

        $this->assertSame($notification->toArray(), $notification->refresh()->toArray());
    }

    /**
     * @return void
     */
    public function test_mark_read_all_success()
    {
        $user1 = $this->authUser('cabinet');
        $user2 = \App\User::factory()->create(['role' => $user1->role]);
        $notification1 = $this->createDatabaseNotification($user1, ['read_at' => null]);
        $notification2 = $this->createDatabaseNotification($user1, ['read_at' => null]);
        $notification3 = $this->createDatabaseNotification($user2, ['read_at' => null]);


        // Iteration 1
        \Date::setTestNow('2025-07-24 19:00:00');

        $this
            ->post('/api/notification/mark_read/all')
            ->assertStatus(200)
            ->assertJson(['notifications' => true]);

        $this->assertSame('2025-07-24 19:00:00', $notification1->refresh()->read_at->format('Y-m-d H:i:s'));
        $this->assertSame('2025-07-24 19:00:00', $notification2->refresh()->read_at->format('Y-m-d H:i:s'));
        $this->assertNull($notification3->refresh()->read_at);


        // Iteration 2
        \Date::setTestNow('2025-07-24 20:00:00');

        $this
            ->post('/api/notification/mark_read/all')
            ->assertStatus(200)
            ->assertJson(['notifications' => true]);

        $this->assertSame('2025-07-24 19:00:00', $notification1->refresh()->read_at->format('Y-m-d H:i:s'));
        $this->assertSame('2025-07-24 19:00:00', $notification2->refresh()->read_at->format('Y-m-d H:i:s'));
        $this->assertNull($notification3->refresh()->read_at);
    }

    /**
     * @return void
     */
    public function test_mark_read_all_success_empty()
    {
        $this->authUser('cabinet');

        $this
            ->post('/api/notification/mark_read/all')
            ->assertStatus(200)
            ->assertJson(['notifications' => true]);
    }

    /**
     * @return void
     */
    public function test_mark_read_all_failure_not_auth()
    {
        $this
            ->post('/api/notification/mark_read/all')
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.', 'errors' => []]);
    }

    /**
     * @return void
     */
    public function test_mark_read_all_failure_acl()
    {
        $user = $this->authUser('cabinet');
        config(["models.user.role.{$user->role}.abilities" => ['foo']]);
        $notification = $this->createDatabaseNotification($user, ['read_at' => null]);

        $this
            ->post('/api/notification/mark_read/all')
            ->assertStatus(403);

        $this->assertSame($notification->toArray(), $notification->refresh()->toArray());
    }

    /**
     * @return void
     */
    public function test_notification_personal_message_success()
    {
        \Notification::fake();
        $this->authUser('cabinet.user.notify');
        $user2 = \App\User::factory()->create();
        \App\UserNotification::factory()->create(['user_id' => $user2->id, 'trigger' => 'personal_message', 'channels' => ['database']]);

        $this
            ->post('/api/notification/personal_message/' . $user2->id, ['message' => 'Здравствуйте!'])
            ->assertStatus(200)
            ->assertJson(['user' => ['id' => $user2->id]]);

        \Notification::assertCount(1);
        \Notification::assertSentTo(
            [$user2],
            \App\Notifications\Trigger\PersonalMessageNotification::class,
            fn ($notification) => $notification->message === 'Здравствуйте!'
        );
    }

    /**
     * @return void
     */
    public function test_notification_personal_message_failure_not_auth()
    {
        \Notification::fake();

        $this->post('/api/notification/personal_message/1', ['message' => 'foo'])->assertStatus(401);

        \Notification::assertNothingSent();
    }

    /**
     * @return void
     */
    public function test_notification_personal_message_failure_not_found()
    {
        \Notification::fake();
        $this->authUser('cabinet.user.notify');
        $user2 = tap(\App\User::factory()->create())->delete();

        $this->post('/api/notification/personal_message/' . $user2->id, ['message' => 'foo'])->assertStatus(404);
        $this->post('/api/notification/personal_message/' . ($user2->id + 100), ['message' => 'foo'])->assertStatus(404);

        \Notification::assertNothingSent();
    }

    /**
     * @return void
     */
    public function test_notification_personal_message_failure_validation()
    {
        \Notification::fake();
        $user = $this->authUser('cabinet.user.notify');

        $this->post('/api/notification/personal_message/' . $user->id, [])->assertStatus(422)->assertJson(['errors' => ['message' => true]]);
        $this->post('/api/notification/personal_message/' . $user->id, ['message' => ['foo']])->assertStatus(422)->assertJson(['errors' => ['message' => true]]);

        \Notification::assertNothingSent();
    }

    /**
     * @return void
     */
    public function test_notification_personal_message_failure_acl()
    {
        \Notification::fake();
        $user = $this->authUser('cabinet.user.notify');

        config(["models.user.role.{$user->role}.abilities" => ['foo']]);
        $this->post('/api/notification/personal_message/' . $user->id, ['message' => 'foo'])->assertStatus(403);

        config(["models.user.role.{$user->role}.abilities" => ['cabinet', 'cabinet.user.read']]);
        $this->post('/api/notification/personal_message/' . $user->id, ['message' => 'foo'])->assertStatus(403);

        \Notification::assertNothingSent();
    }
}
