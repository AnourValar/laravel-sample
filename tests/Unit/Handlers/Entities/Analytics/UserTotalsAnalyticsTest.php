<?php

namespace Tests\Unit\Handlers\Entities\Analytics;

use Tests\TestCase;

class UserTotalsAnalyticsTest extends TestCase
{
    use \AnourValar\LaravelAtom\Traits\OptimizeCheckerTrait;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @return void
     */
    public function test_getData()
    {
        // Чистка
        \Date::setTestNow('2026-05-21 12:00:00');
        \App\Journal::query()->delete();
        \App\User::query()->forceDelete();

        // Пользователи
        $user1 = \App\User::factory()->create(['first_name' => 'Иван', 'last_name' => 'Иванов', 'middle_name' => 'Иванович', 'created_at' => '2026-01-01 10:00:00', 'deleted_at' => null]);
        $user2 = tap(\App\User::factory()->create(['first_name' => null, 'last_name' => null, 'middle_name' => null, 'created_at' => '2026-03-01 00:00:00', 'deleted_at' => null]))->delete();

        // Журналы
        \App\Journal::factory()->create(['user_id' => $user1->id, 'event' => 'user_token_obtain', 'success' => true, 'created_at' => '2026-05-21 08:00:00']);
        \App\Journal::factory()->create(['user_id' => $user1->id, 'event' => 'user_session_obtain', 'success' => true, 'created_at' => '2026-05-19 08:00:00']);
        \App\Journal::factory()->create(['user_id' => $user1->id, 'event' => 'user_token_obtain', 'success' => false, 'created_at' => '2026-05-21 11:00:00']);
        \App\Journal::factory()->create(['user_id' => $user1->id, 'event' => 'foo', 'success' => true, 'created_at' => '2026-05-21 11:00:00']);

        // Балласт
        // ...

        // Iteration 1
        $response = $this->getHandler()->getData([
            'page' => 1, 'per_page' => 20, 'sort_column' => 'id', 'sort_direction' => 'ASC',
        ]);

        $this->assertInstanceOf(\Illuminate\Pagination\Paginator::class, $response);
        $this->assertSame(20, $response->perPage());
        $this->assertSame(1, $response->currentPage());
        $this->assertSame(
            [
                [
                    'id' => $user1->id,
                    'first_name' => 'Иван',
                    'last_name' => 'Иванов',
                    'middle_name' => 'Иванович',
                    'created_at' => '2026-01-01 10:00:00',
                    'last_login_at' => '2026-05-21 08:00:00',
                    'deleted_at' => null,
                ],
                [
                    'id' => $user2->id,
                    'first_name' => null,
                    'last_name' => null,
                    'middle_name' => null,
                    'created_at' => '2026-03-01 00:00:00',
                    'last_login_at' => null,
                    'deleted_at' => '2026-05-21 12:00:00',
                ],
            ],
            $response->items()
        );

        // Iteration 2
        $response = $this->getHandler()->getData([
            'page' => 1, 'per_page' => 1, 'sort_column' => 'id', 'sort_direction' => 'DESC',
        ]);

        $this->assertSame(1, $response->currentPage());
        $this->assertSame(
            [
                [
                    'id' => $user2->id,
                    'first_name' => null,
                    'last_name' => null,
                    'middle_name' => null,
                    'created_at' => '2026-03-01 00:00:00',
                    'last_login_at' => null,
                    'deleted_at' => '2026-05-21 12:00:00',
                ],
            ],
            $response->items()
        );

        // Iteration 3
        $response = $this->getHandler()->getData([
            'page' => 2, 'per_page' => 1, 'sort_column' => 'id', 'sort_direction' => 'DESC',
        ]);

        $this->assertSame(2, $response->currentPage());
        $this->assertSame(
            [
                [
                    'id' => $user1->id,
                    'first_name' => 'Иван',
                    'last_name' => 'Иванов',
                    'middle_name' => 'Иванович',
                    'created_at' => '2026-01-01 10:00:00',
                    'last_login_at' => '2026-05-21 08:00:00',
                    'deleted_at' => null,
                ],
            ],
            $response->items()
        );
    }

    /**
     * @return void
     */
    public function test_getData_empty()
    {
        \Date::setTestNow('2026-05-21 12:00:00');
        \App\Journal::query()->delete();
        \App\User::query()->forceDelete();

        $response = $this->getHandler()->getData([
            'page' => 1, 'per_page' => 20, 'sort_column' => 'id', 'sort_direction' => 'ASC',
        ]);

        $this->assertSame(1, $response->currentPage());
        $this->assertSame([], $response->items());
    }

    /**
     * @return void
     */
    public function test_validate_success()
    {
        $user = $this->authUser('cabinet');

        // Iteration 1
        $validator = \Validator::make(['page' => 1, 'per_page' => 20, 'sort_column' => 'id', 'sort_direction' => 'ASC'], []);
        $this->getHandler()->validate($user, $validator);
        $this->assertSame([
            'page' => 1,
            'per_page' => 20,
            'sort_column' => 'id',
            'sort_direction' => 'ASC',
        ], $validator->validate());

        // Iteration 2
        $validator = \Validator::make(['page' => 2, 'per_page' => 50, 'sort_column' => 'created_at', 'sort_direction' => 'DESC'], []);
        $this->getHandler()->validate($user, $validator);
        $this->assertSame([
            'page' => 2,
            'per_page' => 50,
            'sort_column' => 'created_at',
            'sort_direction' => 'DESC',
        ], $validator->validate());
    }

    /**
     * @return void
     */
    public function test_validate_failure()
    {
        $user = $this->authUser('cabinet');

        // Iteration 1
        $validator = \Validator::make(['page' => 0, 'per_page' => 20, 'sort_column' => 'id', 'sort_direction' => 'ASC'], []);
        $this->getHandler()->validate($user, $validator);
        $this->assertCustomValidationFailed(fn () => $validator->validate(), 'page');

        // Iteration 2
        $validator = \Validator::make(['page' => 1, 'per_page' => 21, 'sort_column' => 'id', 'sort_direction' => 'ASC'], []);
        $this->getHandler()->validate($user, $validator);
        $this->assertCustomValidationFailed(fn () => $validator->validate(), 'per_page');

        // Iteration 3
        $validator = \Validator::make(['page' => 1, 'per_page' => 20, 'sort_column' => 'foo', 'sort_direction' => 'ASC'], []);
        $this->getHandler()->validate($user, $validator);
        $this->assertCustomValidationFailed(fn () => $validator->validate(), 'sort_column');

        // Iteration 4
        $validator = \Validator::make(['page' => 1, 'per_page' => 20, 'sort_column' => 'id', 'sort_direction' => 'BAR'], []);
        $this->getHandler()->validate($user, $validator);
        $this->assertCustomValidationFailed(fn () => $validator->validate(), 'sort_direction');
    }

    /**
     * @return void
     */
    public function test_authorize_success()
    {
        $user = $this->authUser('cabinet.user.read');

        $this->getHandler()->authorize($user, []);

        $this->expectNotToPerformAssertions();
    }

    /**
     * @return void
     */
    public function test_authorize_failure()
    {
        $user = $this->authUser('cabinet');
        config(["models.user.role.{$user->role}.abilities" => ['foo']]);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $this->getHandler()->authorize($user, []);
    }

    /**
     * @return \App\Handlers\Entities\Analytics\UserTotalsAnalytics
     */
    private function getHandler(): \App\Handlers\Entities\Analytics\UserTotalsAnalytics
    {
        return \App::make(\App\Handlers\Entities\Analytics\UserTotalsAnalytics::class);
    }
}
