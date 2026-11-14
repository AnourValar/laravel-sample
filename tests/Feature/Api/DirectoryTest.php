<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class DirectoryTest extends TestCase
{
    use \AnourValar\LaravelAtom\Traits\OptimizeCheckerTrait;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @return void
     */
    public function test_index_success()
    {
        $user = $this->authUser('cabinet');
        config(["models.user.role.{$user->role}.abilities" => ['cabinet']]);
        \App\Directory::query()->delete();

        // Iteration 1
        $this->get('/api/directory')->assertStatus(200)->assertJsonPath('data', []);

        // Iteration 2
        $directory1 = \App\Directory::factory()->create(['user_id' => $user->id]);
        $directory2 = \App\Directory::factory()->create(['user_id' => null]);
        \App\Directory::factory()->create(['user_id' => $user->id])->delete();
        \App\Directory::factory()->create(['user_id' => ($user->id + 1)]);

        $this->post('/api/directory')->assertStatus(200)->assertExactJson([
            'current_page' => 1,
            'current_page_url' => url('api/directory?page=1'),
            'data' => [
                \App\Directory::light()->find($directory1->id)->toArray(),
                \App\Directory::light()->find($directory2->id)->toArray(),
            ],
            'first_page_url' => url('api/directory?page=1'),
            'from' => 1,
            'next_page_url' => null,
            'path' => url('api/directory'),
            'per_page' => 20,
            'prev_page_url' => null,
            'to' => 2,
        ]);

        // Iteration 3
        $this
            ->post(
                '/api/directory',
                ['filter' => ['user_id' => ['is-null' => $directory2->type]]]
            )
            ->assertStatus(200)
            ->assertJsonPath('data', [\App\Directory::light()->find($directory2->id)->toArray()]);

        // Iteration 4
        $this
            ->post('/api/directory', ['per_page' => 1, 'page' => 2, 'sort' => ['id' => 'ASC']])
            ->assertStatus(200)
            ->assertJsonPath('data', [\App\Directory::light()->find($directory2->id)->toArray()]);
    }

    /**
     * @return void
     */
    public function test_index_success_not_auth()
    {
        \App\Directory::query()->delete();
        $directory1 = \App\Directory::factory()->create(['user_id' => null]);
        \App\Directory::factory()->create(['user_id' => 1]);

        $this
            ->get('/api/directory')
            ->assertStatus(200)
            ->assertJsonPath('data', [\App\Directory::light()->find($directory1->id)->toArray()]);
    }

    /**
     * @return void
     */
    public function test_index_failure_validation()
    {
        $this->authUser('cabinet');

        $this
            ->post('/api/directory', ['filter' => ['foo' => ['=' => 'bar']]])
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

        \App\Directory::query()->delete();
        \App\Directory::factory()->create(['user_id' => $user->id]);

        $this
            ->get('/api/directory')
            ->assertStatus(200)
            ->assertJsonPath('data', []);
    }

    /**
     * @return void
     */
    public function test_edit_success()
    {
        $user = $this->authUser('cabinet');

        // Iteration 1
        $directory = \App\Directory::factory()->create(['user_id' => $user->id]);
        $this->get('/api/directory/' . $directory->id)->assertStatus(200)->assertExactJson([
            'directory' => \App\Directory::heavy()->find($directory->id)->toArray(),
        ]);

        // Iteration 2
        $directory = \App\Directory::factory()->create(['user_id' => null]);
        $this->get('/api/directory/' . $directory->id)->assertStatus(200)->assertExactJson([
            'directory' => \App\Directory::heavy()->find($directory->id)->toArray(),
        ]);
    }

    /**
     * @return void
     */
    public function test_edit_success_not_auth()
    {
        $directory1 = \App\Directory::factory()->create(['user_id' => null]);
        $directory2 = \App\Directory::factory()->create(['user_id' => 1]);

        $this
            ->get('/api/directory/' . $directory1->id)
            ->assertStatus(200)
            ->assertExactJson(['directory' => \App\Directory::heavy()->find($directory1->id)->toArray()]);

        $this
            ->get('/api/directory/' . $directory2->id)
            ->assertStatus(404);
    }

    /**
     * @return void
     */
    public function test_edit_failure_not_found()
    {
        $user = $this->authUser('cabinet');
        config(["models.user.role.{$user->role}.abilities" => ['cabinet']]);

        $directory1 = tap(\App\Directory::factory()->create(['user_id' => $user->id]))->delete();
        $directory2 = \App\Directory::factory()->create(['user_id' => ($user->id + 1)]);

        $this->get('/api/directory/' . $directory1->id)->assertStatus(404);
        $this->get('/api/directory/' . ($directory1->id + 100))->assertStatus(404);
        $this->get('/api/directory/' . $directory2->id)->assertStatus(404);
    }

    /**
     * @return void
     */
    public function test_edit_failure_acl()
    {
        $user = $this->authUser('cabinet');
        $directory = \App\Directory::factory()->create(['user_id' => $user->id]);
        config(["models.user.role.{$user->role}.abilities" => ['foo']]);

        $this->get('/api/directory/' . $directory->id)->assertStatus(404);
    }

    /**
     * @return array
     */
    public static function typeMapping(): array
    {
        return [
            ['user_tag', false],
        ];
    }

    /**
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('typeMapping')]
    public function test_create_success(string $type, bool $withUser)
    {
        $user = $this->authUser($withUser ? 'cabinet' : 'cabinet.user.create');

        $directoryId = (int) \App\Directory::max('id');
        $refData = \App\Directory::factory()->make(['type' => $type])->data;

        $this
            ->post(
                '/api/directory/create',
                [
                    'directory' => [
                        'user_id' => $withUser ? $user->id : null,
                        'type' => $type,
                        'data' => $refData,
                    ],
                ]
            )
            ->assertStatus(200)
            ->assertJson(['directory' => ['id' => true]]);

        $directory = \App\Directory::latest('id')->first();
        $this->assertGreaterThan($directoryId, $directory->id);
        $this->assertSame(
            [
                'id' => $directory->id,
                'type' => $type,
                'user_id' => $withUser ? $user->id : null,
                'data' => $refData,
            ],
            json_decode(json_encode($directory->only('id', 'type', 'user_id', 'data')), true)
        );
    }

    /**
     * @return void
     */
    public function test_create_failure_not_auth()
    {
        $this
            ->post('/api/directory/create', ['directory' => ['foo' => 'bar']])
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.', 'errors' => []]);
    }

    /**
     * @return void
     */
    public function test_create_failure_validation()
    {
        $this->authUser('cabinet');
        $directoryId = (int) \App\Directory::max('id');

        $this
            ->post(
                '/api/directory/create',
                [
                    'directory' => [
                        'type' => ['foo' => 'bar'],
                    ],
                ]
            )
            ->assertStatus(422)
            ->assertJson(['errors' => ['directory.type' => true]]);

        $this->assertSame($directoryId, (int) \App\Directory::max('id'));
    }

    /**
     * @return void
     */
    public function test_create_failure_acl_1()
    {
        $user = $this->authUser('cabinet');
        config(["models.user.role.{$user->role}.abilities" => ['foo']]);
        $directoryId = (int) \App\Directory::max('id');

        $this->post('/api/directory/create', ['directory' => ['foo' => 'bar']])->assertStatus(403);

        $this->assertSame($directoryId, (int) \App\Directory::max('id'));
    }

    /**
     * @return void
     */
    public function test_create_failure_acl_2()
    {
        $user = $this->authUser('cabinet');
        config(["models.user.role.{$user->role}.abilities" => ['cabinet']]);
        $directoryId = (int) \App\Directory::max('id');

        $ref = \App\Directory::factory()->make(['user_id' => null]);
        $this->post('/api/directory/create', ['directory' => ['user_id' => null, 'type' => $ref->type, 'data' => $ref->data]])->assertStatus(403);

        $this->assertSame($directoryId, (int) \App\Directory::max('id'));
    }

    /**
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('typeMapping')]
    public function test_update_success(string $type, bool $withUser)
    {
        $user = $this->authUser($withUser ? 'cabinet' : 'cabinet.directory.update');
        $directory = \App\Directory::factory()->create(['type' => $type, 'user_id' => $withUser ? $user->id : null]);
        $refData = \App\Directory::factory()->make(['type' => $type])->data;

        $this
            ->post(
                '/api/directory/update/' . $directory->id,
                [
                    'directory' => [
                        'data' => $refData,
                    ],
                ],
            )
            ->assertStatus(200)
            ->assertJson(['directory' => ['id' => $directory->id]]);

        $this->assertSame(
            [
                'id' => $directory->id,
                'type' => $type,
                'user_id' => $withUser ? $user->id : null,
                'data' => $refData,
            ],
            json_decode(json_encode($directory->refresh()->only('id', 'type', 'user_id', 'data')), true)
        );
    }

    /**
     * @return void
     */
    public function test_update_failure_not_auth()
    {
        $this
            ->post('/api/directory/update/0', ['directory' => ['foo' => 'bar']])
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.', 'errors' => []]);
    }

    /**
     * @return void
     */
    public function test_update_failure_not_found()
    {
        $user = $this->authUser('cabinet');
        config(["models.user.role.{$user->role}.abilities" => ['cabinet']]);

        $directory1 = tap(\App\Directory::factory()->create(['user_id' => $user->id]))->delete();
        $directory2 = \App\Directory::factory()->create(['user_id' => ($user->id + 1)]);

        $this->post('/api/directory/update/' . $directory1->id, ['directory' => ['foo' => 'bar']])->assertStatus(404);
        $this->post('/api/directory/update/' . $directory2->id, ['directory' => ['foo' => 'bar']])->assertStatus(404);
        $this->post('/api/directory/update/' . ($directory2->id + 100), ['directory' => ['foo' => 'bar']])->assertStatus(404);
    }

    /**
     * @return void
     */
    public function test_update_failure_validation()
    {
        $this->authUser('cabinet.directory.update');
        $directory = \App\Directory::factory()->create()->refresh();

        $this
            ->post(
                '/api/directory/update/' . $directory->id,
                [
                    'directory' => [
                        'data' => ['foo' => 'bar'],
                    ],
                ]
            )
            ->assertStatus(422)
            ->assertJson(['errors' => ['directory.data' => true]]);

        $this->assertSame($directory->toArray(), $directory->refresh()->toArray());
    }

    /**
     * @return void
     */
    public function test_update_failure_acl_1()
    {
        $user = $this->authUser('cabinet');
        config(["models.user.role.{$user->role}.abilities" => ['foo']]);
        $directory = \App\Directory::factory()->create(['user_id' => $user->id])->refresh();

        $this
            ->post('/api/directory/update/' . $directory->id, ['directory' => ['foo' => 'bar']])
            ->assertStatus(403);

        $this->assertSame($directory->toArray(), $directory->refresh()->toArray());
    }

    /**
     * @return void
     */
    public function test_update_failure_acl_2()
    {
        $user = $this->authUser('cabinet');
        config(["models.user.role.{$user->role}.abilities" => ['cabinet']]);
        $directory = \App\Directory::factory()->create(['user_id' => null])->refresh();
        $refData = \App\Directory::factory()->make(['type' => $directory->type])->data;

        $this
            ->post('/api/directory/update/' . $directory->id, ['directory' => ['data' => $refData]])
            ->assertStatus(403);

        $this->assertSame($directory->toArray(), $directory->refresh()->toArray());
    }

    /**
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('typeMapping')]
    public function test_delete_success(string $type, bool $withUser)
    {
        $user = $this->authUser($withUser ? 'cabinet' : 'cabinet.directory.delete');
        $directory1 = \App\Directory::factory()->create(['type' => $type, 'user_id' => $withUser ? $user->id : null]);
        $directory2 = \App\Directory::factory()->create(['type' => $type, 'user_id' => $withUser ? $user->id : null]);

        $this->post('/api/directory/delete/' . $directory1->id)
            ->assertStatus(200)
            ->assertJson(['directory' => ['id' => $directory1->id]]);

        $this->assertNull(\App\Directory::find($directory1->id));
        $this->assertNotNull(\App\Directory::find($directory2->id));
    }

    /**
     * @return void
     */
    public function test_delete_failure_not_auth()
    {
        $this
            ->post('/api/directory/delete/0')
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.', 'errors' => []]);
    }

    /**
     * @return void
     */
    public function test_delete_failure_not_found()
    {
        $user = $this->authUser('cabinet');
        config(["models.user.role.{$user->role}.abilities" => ['cabinet']]);

        $directory1 = tap(\App\Directory::factory()->create(['user_id' => $user->id]))->delete();
        $directory2 = \App\Directory::factory()->create(['user_id' => ($user->id + 1)]);

        $this->post('/api/directory/delete/' . $directory1->id, ['directory' => ['foo' => 'bar']])->assertStatus(404);
        $this->post('/api/directory/delete/' . $directory2->id, ['directory' => ['foo' => 'bar']])->assertStatus(404);
        $this->post('/api/directory/delete/' . ($directory2->id + 100), ['directory' => ['foo' => 'bar']])->assertStatus(404);
    }

    /**
     * @return void
     */
    public function test_delete_failure_acl_1()
    {
        $user = $this->authUser('cabinet');
        config(["models.user.role.{$user->role}.abilities" => ['foo']]);
        $directory = \App\Directory::factory()->create(['user_id' => $user->id]);

        $this->post('/api/directory/delete/' . $directory->id)->assertStatus(403);
        $this->assertNotNull(\App\Directory::find($directory->id));
    }

    /**
     * @return void
     */
    public function test_delete_failure_acl_2()
    {
        $user = $this->authUser('cabinet');
        $directory = \App\Directory::factory()->create(['user_id' => ($user->id + 100)]);

        config(["models.user.role.{$user->role}.abilities" => ['cabinet']]);
        $this->post('/api/directory/delete/' . $directory->id)->assertStatus(404);

        config(["models.user.role.{$user->role}.abilities" => ['cabinet', 'cabinet.directory.read']]);
        $this->post('/api/directory/delete/' . $directory->id)->assertStatus(403);

        $this->assertNotNull(\App\Directory::find($directory->id));
    }

    /**
     * @return array
     */
    public static function provide_batch(): array
    {
        return [
            [
                'entity' => 'user',
                'field' => 'tag_ids',
                'closure' => function ($self) {
                    $user = $self->authUser('cabinet.user.update');
                    $values = \App\Directory::factory()->count(3)->create(['type' => 'user_tag'])->modelKeys();
                    $make = fn (?array $current) => \App\User::factory()->hasRole(['is_customer' => true])->create(['tag_ids' => $current]);

                    return [$user, $values, $make];
                },
            ],
        ];
    }

    /**
     * @param string $entity
     * @param string $field
     * @param callable $closure
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provide_batch')]
    public function test_attach_success(string $entity, string $field, callable $closure)
    {
        [$user, $values, $make] = $closure($this);

        $model1 = $make([$values[0]]);
        $model2 = $make(null);
        $model3 = $make([$values[0]]);


        // Iteration 1
        $ids = $this
            ->post("/api/directory/attach/{$entity}", ['ids' => [$model1->id, $model2->id, ($model2->id + 100)], $field => [$values[1], $values[2]]])
            ->assertStatus(200)
            ->json("{$entity}.ids");

        $this->assertSame([$model1->id, $model2->id], $ids);
        $this->assertSame([$values[0], $values[1], $values[2]], (array) $model1->refresh()->{$field});
        $this->assertSame([$values[1], $values[2]], (array) $model2->refresh()->{$field});
        $this->assertSame([$values[0]], (array) $model3->refresh()->{$field});


        // Iteration 2
        $ids = $this
            ->post("/api/directory/attach/{$entity}", ['ids' => [$model1->id, $model2->id, ($model2->id + 100)], $field => null])
            ->assertStatus(200)
            ->json("{$entity}.ids");

        $this->assertSame([$model1->id, $model2->id], $ids);
        $this->assertSame([$values[0], $values[1], $values[2]], (array) $model1->refresh()->{$field});
        $this->assertSame([$values[1], $values[2]], (array) $model2->refresh()->{$field});
        $this->assertSame([$values[0]], (array) $model3->refresh()->{$field});
    }

    /**
     * @param string $entity
     * @param string $field
     * @param callable $closure
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provide_batch')]
    public function test_detach_success(string $entity, string $field, callable $closure)
    {
        [$user, $values, $make] = $closure($this);

        $model1 = $make([$values[0], $values[1], $values[2]]);
        $model2 = $make([$values[0]]);
        $model3 = $make([$values[0]]);


        // Iteration 1
        $ids = $this
            ->post("/api/directory/detach/{$entity}", ['ids' => [$model1->id, $model2->id], $field => [$values[0], $values[1]]])
            ->assertStatus(200)
            ->json("{$entity}.ids");

        $this->assertSame([$model1->id, $model2->id], $ids);
        $this->assertSame([$values[2]], (array) $model1->refresh()->{$field});
        $this->assertSame([], (array) $model2->refresh()->{$field});
        $this->assertSame([$values[0]], (array) $model3->refresh()->{$field});


        // Iteration 2
        $ids = $this
            ->post("/api/directory/detach/{$entity}", ['ids' => [$model1->id, $model2->id], $field => null])
            ->assertStatus(200)
            ->json("{$entity}.ids");

        $this->assertSame([$model1->id, $model2->id], $ids);
        $this->assertSame([$values[2]], (array) $model1->refresh()->{$field});
        $this->assertSame([], (array) $model2->refresh()->{$field});
        $this->assertSame([$values[0]], (array) $model3->refresh()->{$field});
    }

    /**
     * @param string $entity
     * @param string $field
     * @param callable $closure
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provide_batch')]
    public function test_set_success(string $entity, string $field, callable $closure)
    {
        [$user, $values, $make] = $closure($this);

        $model1 = $make([$values[0]]);
        $model2 = $make([$values[0], $values[1]]);
        $model3 = $make(null);
        $model4 = $make(null);


        // Iteration 1
        $ids = $this
            ->post("/api/directory/set/{$entity}", ['ids' => [$model1->id, $model2->id, $model3->id], $field => [$values[1], $values[2]]])
            ->assertStatus(200)
            ->json("{$entity}.ids");

        $this->assertSame([$model1->id, $model2->id, $model3->id], $ids);
        $this->assertSame([$values[1], $values[2]], $model1->refresh()->{$field});
        $this->assertSame([$values[1], $values[2]], $model2->refresh()->{$field});
        $this->assertSame([$values[1], $values[2]], $model3->refresh()->{$field});
        $this->assertNull($model4->refresh()->{$field});


        // Iteration 2
        $ids = $this
            ->post("/api/directory/set/{$entity}", ['ids' => [$model1->id, $model2->id], $field => null])
            ->assertStatus(200)
            ->json("{$entity}.ids");

        $this->assertSame([$model1->id, $model2->id], $ids);
        $this->assertNull($model1->refresh()->{$field});
        $this->assertNull($model2->refresh()->{$field});
        $this->assertSame([$values[1], $values[2]], $model3->refresh()->{$field});
        $this->assertNull($model4->refresh()->{$field});
    }

    /**
     * @param string $entity
     * @param string $field
     * @param callable $closure
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provide_batch')]
    public function test_batch_failure_skipped(string $entity, string $field, callable $closure)
    {
        [$user, $values, $make] = $closure($this);
        $missingId = $make(null)->id + 999999;

        foreach (['attach', 'detach', 'set'] as $operation) {
            $this
                ->post("/api/directory/{$operation}/{$entity}", ['ids' => [$missingId], $field => $values])
                ->assertStatus(200)
                ->assertExactJson([$entity => ['ids' => []]]);
        }
    }

    /**
     * @param string $entity
     * @param string $field
     * @param callable $closure
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provide_batch')]
    public function test_batch_failure_not_auth(string $entity, string $field, callable $closure)
    {
        foreach (['attach', 'detach', 'set'] as $operation) {
            $this
                ->post("/api/directory/{$operation}/{$entity}", ['ids' => [1], $field => [1]])
                ->assertStatus(401)
                ->assertJson(['message' => 'Unauthenticated.', 'errors' => []]);
        }
    }

    /**
     * @param string $entity
     * @param string $field
     * @param callable $closure
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provide_batch')]
    public function test_batch_failure_not_found(string $entity, string $field, callable $closure)
    {
        $closure($this);

        foreach (['attach', 'detach', 'set'] as $operation) {
            $this
                ->post("/api/directory/{$operation}/foo", ['ids' => [1], $field => [1]])
                ->assertStatus(404);
        }
    }

    /**
     * @param string $entity
     * @param string $field
     * @param callable $closure
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provide_batch')]
    public function test_batch_failure_validation(string $entity, string $field, callable $closure)
    {
        [$user, $values, $make] = $closure($this);
        $model = $make(null);

        foreach (['attach', 'detach', 'set'] as $operation) {
            // ids
            $this
                ->post("/api/directory/{$operation}/{$entity}", ['ids' => 'foo', $field => $values])
                ->assertStatus(422)
                ->assertJson(['errors' => ['ids' => true]]);

            // "field"
            $this
                ->post("/api/directory/{$operation}/{$entity}", ['ids' => [$model->id], $field => ['foo']])
                ->assertStatus(422)
                ->assertJson(['errors' => ["{$field}.0" => true]]);
        }

        // "field"
        $this
            ->post("/api/directory/attach/{$entity}", ['ids' => [$model->id], $field => [($values[0] + 100)]])
            ->assertStatus(422)
            ->assertJson(['errors' => [$field => true]]);
    }

    /**
     * @param string $entity
     * @param string $field
     * @param callable $closure
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provide_batch')]
    public function test_batch_failure_acl_1(string $entity, string $field, callable $closure)
    {
        [$user, $values, $make] = $closure($this);
        config(["models.user.role.{$user->role}.abilities" => ['foo']]);
        $model = $make(null);

        foreach (['attach', 'detach', 'set'] as $operation) {
            $this
                ->post("/api/directory/{$operation}/{$entity}", ['ids' => [$model->id], $field => $values])
                ->assertStatus(403);
        }
    }

    /**
     * @param string $entity
     * @param string $field
     * @param callable $closure
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provide_batch')]
    public function test_batch_failure_acl_2(string $entity, string $field, callable $closure)
    {
        [$user, $values, $make] = $closure($this);
        config(["models.user.role.{$user->role}.abilities" => ['cabinet', 'cabinet.user.read']]);
        $model = $make(null);
        $model = get_class($model)::factory()->create();

        foreach (['attach', 'detach', 'set'] as $operation) {
            $this
                ->post("/api/directory/{$operation}/{$entity}", ['ids' => [$model->id], $field => $values])
                ->assertStatus(403);
        }
    }
}
