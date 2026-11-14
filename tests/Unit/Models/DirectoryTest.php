<?php

namespace Tests\Unit\Models;

use Tests\TestCase;

class DirectoryTest extends TestCase
{
    use \AnourValar\LaravelAtom\Traits\OptimizeCheckerTrait;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @return void
     */
    public function test_scope_light_heavy()
    {
        \Date::setTestNow('2024-07-20 21:00:00');

        $directory = \App\Directory::factory()->make([
            'type' => 'user_tag',
            'user_id' => null,
            'created_at' => now(),
        ]);
        $directory->validate()->save();

        // light
        $this->assertSame(
            [
                'id' => $directory->id,
                'type' => 'user_tag',
                'user_id' => null,
                'data' => $directory->data,
            ],
            \App\Directory::light()->find($directory->id)->toArray()
        );

        // heavy
        $this->assertSame(
            [
                'id' => $directory->id,
                'type' => 'user_tag',
                'user_id' => null,
                'data' => $directory->data,
                'created_at' => '2024-07-20T21:00:00.000000Z',
            ],
            \App\Directory::heavy()->find($directory->id)->toArray()
        );
    }

    /**
     * @return void
     */
    public function test_data_jsonNested()
    {
        $this->assertSame(
            [
                'color' => '2',
                'title' => '1',
                'description' => '3',
            ],
            (new \App\Directory())->forceFill(['data' => [
                'title' => 1,
                'color' => 2,
                'description' => 3,
                'bar' => null,
            ]])->data
        );
    }

    /**
     * @return void
     */
    public function test_acl()
    {
        $role = \ConfigHelper::randomKey('models.user.role');
        $user1 = \App\User::factory()->create(['role' => $role, 'additional_abilities' => []]);
        $user2 = \App\User::factory()->create(['role' => $role, 'additional_abilities' => []]);
        \App\Directory::query()->delete();

        $directory1 = \App\Directory::factory()->create(['user_id' => $user1->id]);
        $directory2 = \App\Directory::factory()->create(['user_id' => $user2->id]);
        $directory3 = \App\Directory::factory()->create(['user_id' => null]);


        // Iteration 0
        $this->assertSame(
            [$directory3->id],
            \App\Directory::acl(null)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );

        $this->assertSame(
            [$directory3->id],
            \App\Directory::acl(null)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );


        // Iteration 1
        config(["models.user.role.{$role}.abilities" => []]);
        $this->assertSame(
            [$directory3->id],
            \App\Directory::acl($user1)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );

        $this->assertSame(
            [$directory3->id],
            \App\Directory::acl($user2)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );


        // Iteration 2
        config(["models.user.role.{$role}.abilities" => ['cabinet']]);
        $this->assertSame(
            [$directory1->id, $directory3->id],
            \App\Directory::acl($user1)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );

        $this->assertSame(
            [$directory2->id, $directory3->id],
            \App\Directory::acl($user2)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );


        // Iteration 3
        config(["models.user.role.{$role}.abilities" => ['cabinet.directory.read']]);
        $this->assertSame(
            [$directory1->id, $directory2->id, $directory3->id],
            \App\Directory::acl($user1)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );

        $this->assertSame(
            [$directory1->id, $directory2->id, $directory3->id],
            \App\Directory::acl($user2)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );
    }

    /**
     * @return void
     */
    public function test_validate_user_id_not_exists()
    {
        $customer = \App\User::factory()->create(['role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true])]);
        $nonCustomer = \App\User::factory()->create(['role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => false])]);

        // Iteration 1: a customer is accepted
        $directory = \App\Directory::factory()->make([
            'user_id' => $customer->id,
            'data' => ['title' => 'Foo', 'color' => '#ffffff'],
        ]);
        config(["models.directory.type.{$directory->type}.rules.user_id" => []]);
        $this->assertValidationSuccess($directory);

        // Iteration 2: a non-customer is rejected
        $directory->forceFill(['user_id' => $nonCustomer->id]);
        $this->assertValidationFailed($directory, 'user_id', trans('models/directory.user_id_not_exists'));

        // Iteration 3: a missing user is rejected
        $directory->forceFill(['user_id' => $nonCustomer->id + 100]);
        $this->assertValidationFailed($directory, 'user_id', trans('models/directory.user_id_not_exists'));
    }

    /**
     * @return void
     */
    public function test_validate_is_singleton()
    {
        \App\Directory::query()->delete();

        // Iteration 1: the first record passes
        $directory = \App\Directory::factory()->make([
            'user_id' => null,
            'data' => ['title' => 'Foo', 'color' => '#ffffff'],
        ]);
        config(["models.directory.type.{$directory->type}.rules.user_id" => []]);
        config(["models.directory.type.{$directory->type}.is_singleton" => true]);
        $this->assertValidationSuccess($directory)->save();

        // Iteration 2: a second record (same user_id + type) is rejected
        $duplicate = \App\Directory::factory()->make([
            'type' => $directory->type,
            'user_id' => null,
            'data' => ['title' => 'Bar', 'color' => '#000000'],
        ]);
        $this->assertValidationFailed($duplicate, 'user_id');
    }
}
