<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class UserNotificationTest extends TestCase
{
    use \AnourValar\LaravelAtom\Traits\OptimizeCheckerTrait;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @return void
     */
    public function test_notification_index_success()
    {
        $user = $this->authUser('cabinet');

        $userNotification1 = \App\UserNotification::factory()->create(['user_id' => $user->id, 'trigger' => 'user_email_updated']);
        $userNotification2 = \App\UserNotification::factory()->create(['user_id' => $user->id, 'trigger' => 'user_logged_in_unique_ip']);
        $userNotification3 = \App\UserNotification::factory()->create(['user_id' => $user->id, 'trigger' => 'user_phone_updated']);

        $this
            ->get('/api/user/notification/index/' . $user->id)
            ->assertStatus(200)
            ->assertExactJson(['data' => [
                \App\UserNotification::light()->find($userNotification1->id)->toArray(),
                \App\UserNotification::light()->find($userNotification2->id)->toArray(),
                \App\UserNotification::light()->find($userNotification3->id)->toArray(),

            ]]);
    }

    /**
     * @return void
     */
    public function test_notification_index_failure_not_auth()
    {
        $this->get('/api/user/notification/index/1')->assertStatus(401);
    }

    /**
     * @return void
     */
    public function test_notification_index_failure_not_found()
    {
        $user = $this->authUser('cabinet', ['role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true])]);
        $user2 = \App\User::factory()->create(['role' => $user->role]);

        $this->get('/api/user/notification/index/' . $user2->id, ['user_notifications' => ['foo' => ['bar']]])->assertStatus(404);
        $this->get('/api/user/notification/index/' . ($user2->id + 100), ['user_notifications' => ['foo' => ['bar']]])->assertStatus(404);
    }

    /**
     * @return void
     */
    public function test_notification_index_failure_acl()
    {
        $user1 = $this->authUser('cabinet');

        config(["models.user.role.{$user1->role}.abilities" => ['foo']]);
        $this->get('/api/user/notification/index/' . $user1->id)->assertStatus(403);

        config(["models.user.role.{$user1->role}.abilities" => ['cabinet']]);
        $user2 = \App\User::factory()->create();
        $this->get('/api/user/notification/index/' . $user2->id)->assertStatus(404);
    }

    /**
     * @return void
     */
    public function test_notification_sync_success()
    {
        $user = $this->authUser('cabinet');
        \App\UserNotification::factory()->create(['user_id' => $user->id, 'trigger' => 'personal_message', 'channels' => ['database']]);
        \App\UserNotification::factory()->create(['user_id' => $user->id, 'trigger' => 'user_phone_updated', 'channels' => ['mail']]);
        \App\UserNotification::factory()->create(['user_id' => $user->id, 'trigger' => 'user_logged_in_unique_ip', 'channels' => ['mail']]);

        $this
            ->post(
                '/api/user/notification/sync/' . $user->id,
                [
                    'user_notifications' => [
                        'user_phone_updated' => ['mail', 'sms'],
                        'user_password_updated' => ['sms', 'telegram', 'mail', 'database'],
                    ],
                ]
            )
            ->assertStatus(200)
            ->assertJson(['user' => ['id' => $user->id]]);

        $this->assertSame(
            [
                ['trigger' => 'personal_message', 'channels' => ['database']],
                ['trigger' => 'user_phone_updated', 'channels' => ['mail', 'sms']],
                ['trigger' => 'user_password_updated', 'channels' => ['database', 'mail', 'sms', 'telegram']],
            ],
            \App\UserNotification::where('user_id', '=', $user->id)->orderBy('id', 'ASC')->get(['trigger', 'channels'])->toArray()
        );
    }

    /**
     * @return void
     */
    public function test_notification_sync_failure_not_auth()
    {
        $this->post('/api/user/notification/sync/1', ['user' => ['user_notifications' => ['foo' => ['bar']]]])->assertStatus(401);
    }

    /**
     * @return void
     */
    public function test_notification_sync_failure_not_found()
    {
        $user = $this->authUser('cabinet', ['role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true])]);
        $user2 = \App\User::factory()->create(['role' => $user->role]);

        $this->post('/api/user/notification/sync/' . $user2->id, ['user_notifications' => ['foo' => ['bar']]])->assertStatus(404);
        $this->post('/api/user/notification/sync/' . ($user2->id + 100), ['user_notifications' => ['foo' => ['bar']]])->assertStatus(404);
    }

    /**
     * @return void
     */
    public function test_notification_sync_failure_validation()
    {
        $user = $this->authUser('cabinet');

        $this
            ->post('/api/user/notification/sync/' . $user->id, ['user_notifications' => ['foo' => ['bar']]])
            ->assertStatus(422)
            ->assertJson(['errors' => ['user_notifications.foo.trigger' => true]]);
    }

    /**
     * @return void
     */
    public function test_notification_sync_failure_acl_1()
    {
        $user = $this->authUser('cabinet');

        config(["models.user.role.{$user->role}.abilities" => ['foo']]);
        $this->post('/api/user/notification/sync/' . $user->id, ['user_notifications' => ['foo' => ['bar']]])->assertStatus(403);
    }

    /**
     * @return void
     */
    public function test_notification_sync_failure_acl_2()
    {
        $user = $this->authUser('cabinet');
        config(["models.user.role.{$user->role}.abilities" => ['cabinet', 'cabinet.user.read']]);
        $user2 = \App\User::factory()->create(['role' => $user->role]);

        $this->post('/api/user/notification/sync/' . $user2->id, ['user_notifications' => ['foo' => ['bar']]])->assertStatus(403);
    }

    /**
     * @return void
     */
    public function test_notification_sync_failure_acl_3()
    {
        $user = $this->authUser('cabinet');
        \App\UserNotification::factory()->create(['user_id' => $user->id, 'trigger' => 'personal_message', 'channels' => ['mail']]);

        $this
            ->post(
                '/api/user/notification/sync/' . $user->id,
                [
                    'user_notifications' => [
                        'personal_message' => ['database'],
                    ],
                ]
            )
            ->assertStatus(422)
            ->assertJson(['errors' => ['user_notifications.personal_message.trigger' => true]]);

        $this->assertSame(
            [
                ['trigger' => 'personal_message', 'channels' => ['mail']],
            ],
            \App\UserNotification::where('user_id', '=', $user->id)->orderBy('id', 'ASC')->get(['trigger', 'channels'])->toArray()
        );
    }
}
