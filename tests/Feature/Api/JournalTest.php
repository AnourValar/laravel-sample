<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class JournalTest extends TestCase
{
    use \AnourValar\LaravelAtom\Traits\OptimizeCheckerTrait;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @return void
     */
    public function test_index_success()
    {
        $user = $this->authUser('cabinet.user.read');
        \App\Journal::query()->delete();

        // Iteration 1
        $this->get('/api/journal')->assertStatus(200)->assertJsonPath('data', []);

        // Iteration 2
        $journal1 = \App\Journal::factory()->create(['user_id' => $user->id, 'type' => 'metric', 'created_at' => '2024-07-24 10:00']);
        $journal2 = \App\Journal::factory()->create(['user_id' => $user->id + 1, 'type' => 'metric', 'created_at' => '2024-07-25 10:00']);
        $journal3 = \App\Journal::factory()->create(['user_id' => $user->id, 'type' => 'metric', 'created_at' => '2024-07-23 10:00']);
        \App\Journal::factory()->create(['user_id' => $user->id, 'type' => 'metric'])->delete();

        $this->post('/api/journal')->assertStatus(200)->assertExactJson([
            'data' => [
                \App\Journal::heavy()->find($journal3->id)->toArray(),
                \App\Journal::heavy()->find($journal2->id)->toArray(),
                \App\Journal::heavy()->find($journal1->id)->toArray(),
            ],
            'next_cursor' => null,
            'next_page_url' => null,
            'path' => url('api/journal'),
            'per_page' => 20,
            'prev_cursor' => null,
            'prev_page_url' => null,
        ]);

        // Iteration 3
        $this
            ->post(
                '/api/journal',
                ['filter' => ['created_at' => ['>=' => '2024-07-25 00:00']]]
            )
            ->assertStatus(200)
            ->assertJsonPath('data', [
                \App\Journal::heavy()->find($journal2->id)->toArray(),
            ]);

        // Iteration 4
        $response = $this->post('/api/journal', ['per_page' => 1, 'sort' => ['id' => 'ASC']])->assertStatus(200)->assertJsonPath('data', [
            \App\Journal::heavy()->find($journal1->id)->toArray(),
        ])->json();

        $this->post('/api/journal', ['per_page' => 1, 'sort' => ['id' => 'ASC'], 'cursor' => $response['next_cursor']])->assertStatus(200)->assertJsonPath('data', [
            \App\Journal::heavy()->find($journal2->id)->toArray(),
        ]);
    }

    /**
     * @return void
     */
    public function test_index_failure_not_auth()
    {
        $this
            ->get('/api/journal')
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
            ->post('/api/journal', ['filter' => ['foo' => ['=' => 'bar']]])
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

        $this->get('/api/journal')->assertStatus(403);
    }
}
