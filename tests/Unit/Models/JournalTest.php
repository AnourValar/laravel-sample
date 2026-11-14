<?php

namespace Tests\Unit\Models;

use Tests\TestCase;

class JournalTest extends TestCase
{
    use \AnourValar\LaravelAtom\Traits\OptimizeCheckerTrait;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @return void
     */
    public function test_scope_heavy()
    {
        \App::setLocale('ru');
        \Date::setTestNow('2024-07-20 21:00:00');

        config(['eloquent_journal.entity' => [
            \App\User::class => [
                'title' => 'User',

                'schema' => [],

                'observe' => true,
                'exclude_attributes' => [],
            ],
        ]]);
        $user = \App\User::factory()->create();

        $journal = \App\Journal::factory()->make([
            'user_id' => $userId = \App\User::factory()->create()->id,
            'ip_address' => '127.0.0.1',
            'entity' => 'user',
            'entity_id' => $user->id,
            'type' => 'metric',
            'event' => 'user_token_obtain',
            'data' => ['foo' => '1', 'bar' => 2, 'baz' => null],
            'success' => true,
            'tags' => ['foobar'],
            'created_at' => now(),
        ]);
        $journal->validate()->save();

        // heavy
        $this->assertSame(
            [
                'id' => $journal->id,
                'ip_address' => '127.0.0.1',
                'entity' => 'user',
                'type' => 'metric',
                'event' => 'user_token_obtain',
                'success' => true,
                'tags' => ['foobar'],
                'created_at' => '2024-07-20T21:00:00.000000Z',
                'entity_title' => 'User',
                'type_title' => 'Метрика',
                'event_title' => 'Аутентификация (токен)',
                'short_description' => null,
                'full_description' => '<pre class="journal-metric">' . e(json_encode(['bar' => 2, 'baz' => null, 'foo' => '1'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . "</pre>\n",
                'user' => \App\User::light()->find($userId)->toArray(),
            ],
            \App\Journal::heavy()->find($journal->id)->toArray()
        );
    }

    /**
     * @return void
     */
    public function test_acl()
    {
        \App\Journal::query()->delete();
        $role = \ConfigHelper::randomKey('models.user.role');
        $user1 = \App\User::factory()->create(['role' => $role, 'additional_abilities' => []]);
        $user2 = \App\User::factory()->create(['role' => $role, 'additional_abilities' => []]);
        $user3 = \App\User::factory()->create(['role' => $role, 'additional_abilities' => []]);
        $user4 = \App\User::factory()->create(['role' => $role, 'additional_abilities' => []]);

        $model1 = \App\Journal::factory()->create(['user_id' => $user1->id, 'type' => 'metric', 'event' => 'user_token_obtain']);
        $model2 = \App\Journal::factory()->create(['user_id' => $user2->id, 'type' => 'metric', 'event' => 'user_session_obtain']);
        $model3 = \App\Journal::factory()->create(['user_id' => $user3->id, 'type' => 'model', 'event' => 'create']);
        $model4 = \App\Journal::factory()->create(['user_id' => $user4->id, 'type' => 'metric', 'event' => 'user_token_obtain']);


        // Iteration 1
        config(["models.user.role.{$role}.abilities" => []]);

        $this->assertSame(
            [$model1->id],
            \App\Journal::acl($user1)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );

        $this->assertSame(
            [$model2->id],
            \App\Journal::acl($user2)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );

        $this->assertSame(
            [],
            \App\Journal::acl($user3)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );

        $this->assertSame(
            [$model4->id],
            \App\Journal::acl($user4)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );


        // Iteration 2
        config(["models.user.role.{$role}.abilities" => ['admin.administration']]);

        $this->assertSame(
            [$model1->id, $model2->id, $model3->id, $model4->id],
            \App\Journal::acl($user1)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );

        $this->assertSame(
            [$model1->id, $model2->id, $model3->id, $model4->id],
            \App\Journal::acl($user2)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );

        $this->assertSame(
            [$model1->id, $model2->id, $model3->id, $model4->id],
            \App\Journal::acl($user3)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );

        $this->assertSame(
            [$model1->id, $model2->id, $model3->id, $model4->id],
            \App\Journal::acl($user4)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );


        // Iteration 3
        config(["models.user.role.{$role}.abilities" => ['cabinet.journal.read']]);

        $this->assertSame(
            [$model1->id, $model2->id, $model3->id, $model4->id],
            \App\Journal::acl($user1)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );

        $this->assertSame(
            [$model1->id, $model2->id, $model3->id, $model4->id],
            \App\Journal::acl($user2)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );

        $this->assertSame(
            [$model1->id, $model2->id, $model3->id, $model4->id],
            \App\Journal::acl($user3)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );

        $this->assertSame(
            [$model1->id, $model2->id, $model3->id, $model4->id],
            \App\Journal::acl($user4)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );
    }

    /**
     * @return void
     */
    public function test_UserLoggedInUniqueIpListener_yes_1()
    {
        \Notification::fake();
        $user = \App\User::factory()->create();
        \App\UserNotification::factory()->create(['user_id' => $user->id, 'trigger' => 'user_logged_in_unique_ip']);

        \App::make(\AnourValar\EloquentJournal\Service::class)->ipAddress('127.0.0.1')->user($user)->captureMetric('user_token_obtain');

        \Notification::assertCount(1);
        \Notification::assertSentTo([$user], \App\Notifications\Trigger\UserLoggedInUniqueIpNotification::class);
    }

    /**
     * @return void
     */
    public function test_UserLoggedInUniqueIpListener_yes_2()
    {
        \Notification::fake();
        $user = \App\User::factory()->create();
        \App\UserNotification::factory()->create(['user_id' => $user->id, 'trigger' => 'user_logged_in_unique_ip']);

        \App\Journal::factory()->create(['user_id' => $user->id + 1, 'type' => 'metric', 'event' => 'user_token_obtain', 'ip_address' => '127.0.0.1']);
        \App\Journal::factory()->create(['user_id' => $user->id, 'type' => 'metric', 'event' => 'user_token_obtain', 'ip_address' => '127.0.0.2']);
        \App\Journal::factory()->create(['user_id' => $user->id, 'type' => 'metric', 'event' => 'user_session_obtain', 'ip_address' => '127.0.0.1']);
        \App::make(\AnourValar\EloquentJournal\Service::class)->user($user)->ipAddress('127.0.0.1')->captureMetric('user_token_obtain');

        \Notification::assertCount(1);
        \Notification::assertSentTo([$user], \App\Notifications\Trigger\UserLoggedInUniqueIpNotification::class);
    }

    /**
     * @return void
     */
    public function test_UserLoggedInUniqueIpListener_no_1()
    {
        \Notification::fake();
        $user = \App\User::factory()->create();
        \App\UserNotification::factory()->create(['user_id' => $user->id, 'trigger' => 'user_logged_in_unique_ip']);

        \App\Journal::factory()->create(['user_id' => $user->id, 'type' => 'metric', 'event' => 'user_token_obtain', 'ip_address' => '127.0.0.1']);
        \App::make(\AnourValar\EloquentJournal\Service::class)->ipAddress('127.0.0.1')->user($user)->captureMetric('user_token_obtain');

        \Notification::assertCount(0);
    }

    /**
     * @return void
     */
    public function test_UserLoggedInUniqueIpListener_no_2()
    {
        \Notification::fake();
        $user = \App\User::factory()->create();
        \App\UserNotification::factory()->create(['user_id' => $user->id, 'trigger' => 'user_logged_in_unique_ip']);

        \App::make(\AnourValar\EloquentJournal\Service::class)->ipAddress('127.0.0.1')->user($user)->captureMetric('user_session_obtain');

        \Notification::assertCount(0);
    }
}
