<?php

namespace Tests\Unit\Models;

use Tests\TestCase;

class UserTest extends TestCase
{
    use \AnourValar\LaravelAtom\Traits\OptimizeCheckerTrait;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @return void
     */
    public function test_scope_light_heavy()
    {
        \Atom::transactionZeroLevel(1);
        $tag = \App\Directory::factory()->create(['type' => 'user_tag']);

        $user = \App\User::factory()->make([
            'pid' => null,
            'role' => 'admin',
            'email' => 'foo@example.org',
            'phone' => '79001234567',
            'additional_abilities' => ['admin.user.read'],
            'first_name' => 'Иван',
            'last_name' => 'Иванов',
            'middle_name' => 'Иванович',
            'gender' => 'male',
            'birthday' => '2020-06-01',
            'telegram_username' => 'ivanov_ivan',
            'tag_ids' => [$tag->id],
            'created_at' => '2024-05-29 12:00:00',
        ]);
        $user->validate()->save();

        // light
        $this->assertSame(
            [
                'id' => $user->id,
                'pid' => null,
                'role' => $user->role,
                'email' => 'foo@example.org',
                'phone' => '79001234567',
                'first_name' => 'Иван',
                'last_name' => 'Иванов',
                'middle_name' => 'Иванович',
                'tag_ids' => [$tag->id],
                'created_at' => '2024-05-29T12:00:00.000000Z',
                'deleted_at' => null,
            ],
            \App\User::light()->find($user->id)->toArray()
        );

        // heavy
        $this->assertSame(
            [
                'id' => $user->id,
                'pid' => null,
                'role' => $user->role,
                'email' => 'foo@example.org',
                'phone' => '79001234567',
                'first_name' => 'Иван',
                'last_name' => 'Иванов',
                'middle_name' => 'Иванович',
                'tag_ids' => [$tag->id],
                'created_at' => '2024-05-29T12:00:00.000000Z',
                'deleted_at' => null,
                'role_data' => $user->role_data,
                'gender' => 'male',
                'birthday' => '2020-06-01',
                'telegram_username' => 'ivanov_ivan',
                'additional_abilities' => ['admin.user.read'],
            ],
            \App\User::heavy()->find($user->id)->toArray()
        );

        // tag_ids (relation)
        $this->assertSame([$tag->id], $user->directories()->pluck('id')->toArray());
    }

    /**
     * @return void
     */
    public function test_jsonNested_role_data()
    {
        $this->assertSame(
            null,
            (new \App\User())->forceFill(['role_data' => [
                'a' => null,
            ]])->role_data
        );
    }

    /**
     * @return void
     */
    public function test_jsonNested_tag_ids()
    {
        $this->assertSame(
            [1, 2, 3, 10],
            (new \App\User())->forceFill(['tag_ids' => ['3', '1', '', '2', 'foo' => '10', null]])->tag_ids
        );

        $this->assertNull(
            (new \App\User())->forceFill(['tag_ids' => []])->tag_ids
        );
    }

    /**
     * @return void
     */
    public function test_acl()
    {
        \App\User::query()->delete();
        $role = \ConfigHelper::randomKey('models.user.role');
        $user1 = \App\User::factory()->create([/*'id' => null,*/ 'role' => $role, 'additional_abilities' => []]);
        $user2 = \App\User::factory()->create([/*'id' => $user1->id,*/ 'role' => $role, 'additional_abilities' => []]);
        $user3 = \App\User::factory()->create([/*'id' => null,*/ 'role' => $role, 'additional_abilities' => []/*, 'acl' => [$user1->id]*/]);
        $user4 = \App\User::factory()->create([/*'id' => $user3->id,*/ 'role' => $role, 'additional_abilities' => []/*, 'acl' => [$user1->id]*/]);


        // Iteration 1
        config(["models.user.role.{$role}.abilities" => []]);

        $this->assertSame(
            [$user1->id],
            \App\User::acl($user1)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );

        $this->assertSame(
            [$user2->id],
            \App\User::acl($user2)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );

        $this->assertSame(
            [$user3->id],
            \App\User::acl($user3)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );

        $this->assertSame(
            [$user4->id],
            \App\User::acl($user4)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );


        // Iteration 2
        config(["models.user.role.{$role}.abilities" => ['cabinet.user.read']]);

        $this->assertSame(
            [$user1->id, $user2->id, $user3->id, $user4->id],
            \App\User::acl($user1)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );

        $this->assertSame(
            [$user1->id, $user2->id, $user3->id, $user4->id],
            \App\User::acl($user2)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );

        $this->assertSame(
            [$user1->id, $user2->id, $user3->id, $user4->id],
            \App\User::acl($user3)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );

        $this->assertSame(
            [$user1->id, $user2->id, $user3->id, $user4->id],
            \App\User::acl($user4)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );
    }

    /**
     * @return void
     */
    public function test_validate_additional_abilities()
    {
        $user = \App\User::factory()->make(['role' => 'admin', 'additional_abilities' => ['unknown_ability']]);

        $this->assertValidationFailed($user, 'additional_abilities');
    }

    /**
     * @return void
     */
    public function test_validate_role()
    {
        $user = \App\User::factory()->create(['role' => 'staff', 'additional_abilities' => []])->refresh();
        $user->forceFill(['role' => 'client']);

        $this->assertValidationFailed($user, 'role');
    }

    /**
     * @return void
     */
    public function test_validate_telegram_username()
    {
        $this->mock(\AnourValar\EloquentNotification\Adapters\Telegram\TelegramInterface::class, function ($mock) {
            $mock->shouldReceive('fetchChatId')->andReturn(null);
        });

        $user = \App\User::factory()->make(['role' => 'admin', 'telegram_username' => 'foo_bar']);
        $this->assertValidationFailed($user, 'telegram_username');
    }

    /**
     * @return void
     */
    public function test_validate_tag_ids()
    {
        $tag = \App\Directory::factory()->create(['type' => 'user_tag']);

        // Iteration 1
        $user = \App\User::factory()->make(['tag_ids' => [$tag->id]]);
        $this->assertValidationSuccess($user);

        // Iteration 2
        $user = \App\User::factory()->make(['tag_ids' => [($tag->id + 100)]]);
        $this->assertValidationFailed($user, 'tag_ids');

        // Iteration 3
        $tag->forceFill(['type' => 'foo'])->saveQuietly();
        $user = \App\User::factory()->make(['tag_ids' => [$tag->id]]);
        $this->assertValidationFailed($user, 'tag_ids');
    }

    /**
     * @return void
     */
    public function test_notification_light_heavy()
    {
        $user = \App\User::factory()->create();
        $notification = $this->createDatabaseNotification($user);

        // light
        $this->assertSame(
            [
                'id' => $notification->id,
                'type' => 'foo',
                'data' => ['bar' => 'baz'],
                'read_at' => '2025-07-24T18:00:00.000000Z',
                'created_at' => '2024-07-24T17:00:00.000000Z',
            ],
            $user->notifications()->select(config('models.user.notification_light'))->first()->toArray()
        );

        // heavy
        $this->assertSame(
            [
                'id' => $notification->id,
                'type' => 'foo',
                'data' => ['bar' => 'baz'],
                'read_at' => '2025-07-24T18:00:00.000000Z',
                'created_at' => '2024-07-24T17:00:00.000000Z',
            ],
            $user->notifications()->select(config('models.user.notification_heavy'))->first()->toArray()
        );
    }

    /**
     * @return void
     */
    public function test_token_light_heavy()
    {
        \Date::setTestNow('2025-08-19 15:00:00');
        $user = \App\User::factory()->create();
        $token = $user->createToken('foo');


        // light
        $this->assertSame(
            [
                'id' => $token->accessToken->id,
                'name' => 'foo',
                'abilities' => ['*'],
                'ip_address' => null,
                'geo_location' => null,
                'last_used_at' => null,
                'created_at' => '2025-08-19T15:00:00.000000Z',
            ],
            json_decode(json_encode($user->tokens()->select(config('models.user.token_light'))->withCasts(config('models.user.token_casts'))->first()->toArray()), true)
        );


        // heavy
        $this->assertSame(
            [
                'id' => $token->accessToken->id,
                'name' => 'foo',
                'abilities' => ['*'],
                'ip_address' => null,
                'geo_location' => null,
                'last_used_at' => null,
                'created_at' => '2025-08-19T15:00:00.000000Z',
            ],
            json_decode(json_encode($user->tokens()->select(config('models.user.token_heavy'))->withCasts(config('models.user.token_casts'))->first()->toArray()), true)
        );


        // light (alt)
        $data = ['ip_address' => '127.0.0.2', 'geo_location' => 'Russia, Moscow'];
        $token->accessToken->mergeCasts(config('models.user.token_casts'))->getModel()->forceFill($data)->save();

        $this->assertSame(
            [
                'id' => $token->accessToken->id,
                'name' => 'foo',
                'abilities' => ['*'],
                'ip_address' => '127.0.0.2',
                'geo_location' => 'Russia, Moscow',
                'last_used_at' => null,
                'created_at' => '2025-08-19T15:00:00.000000Z',
            ],
            json_decode(json_encode($user->tokens()->select(config('models.user.token_light'))->withCasts(config('models.user.token_casts'))->first()->toArray()), true)
        );
    }

    /**
     * @return void
     */
    public function test_validate_pid()
    {
        $role = \ConfigHelper::randomKey('models.user.role', ['is_customer' => true]);
        config(["models.user.role.{$role}.has_childs" => true]);
        config(["models.user.role.{$role}.rules.pid" => ['nullable', 'integer']]);

        $parent = \App\User::factory()->create(['role' => $role, 'pid' => null]);

        // Iteration 1 (успех)
        $this->assertValidationSuccess(\App\User::factory()->make(['role' => $role, 'pid' => $parent->id]));

        // Iteration 2 (несуществующий родитель)
        $this->assertValidationFailed(
            \App\User::factory()->make(['role' => $role, 'pid' => ($parent->id + 1000)]),
            'pid',
            trans('models/user.pid_not_exists')
        );

        // Iteration 3 (удалённый родитель)
        $parent->delete();
        $this->assertValidationFailed(
            \App\User::factory()->make(['role' => $role, 'pid' => $parent->id]),
            'pid',
            trans('models/user.pid_not_active')
        );
    }

    /**
     * @return void
     */
    public function test_routeNotificationFor()
    {
        $user = \App\User::factory()->create([
            'email' => 'foo@example.org',
            'phone' => '79000000001',
            'telegram_username' => null,
        ]);
        $notification = new \App\Notifications\Trigger\UserPasswordUpdatedNotification();

        $this->assertSame('foo@example.org', $user->routeNotificationForMail($notification));
        $this->assertSame('79000000001', $user->routeNotificationForSms($notification));
        $this->assertNull($user->routeNotificationForTelegram($notification));
        $this->assertSame([], $user->routeNotificationForPush($notification));

        // push
        $user->createToken('foo')->accessToken->forceFill(['push_token' => 'token-1'])->save();
        $user->createToken('bar')->accessToken->forceFill(['push_token' => 'token-2'])->save();

        $this->assertSame(['token-2', 'token-1'], $user->routeNotificationForPush($notification));
    }
}
