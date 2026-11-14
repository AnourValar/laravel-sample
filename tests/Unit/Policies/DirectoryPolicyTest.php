<?php

namespace Tests\Unit\Policies;

use Tests\TestCase;

class DirectoryPolicyTest extends TestCase
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

        $directory1 = \App\Directory::factory()->make(['user_id' => $user1->id]);
        $directory2 = \App\Directory::factory()->make(['user_id' => $user2->id]);
        $directory3 = \App\Directory::factory()->make(['user_id' => null]);

        // Iteration 1
        config(["models.user.role.{$role}.abilities" => []]);
        $this->assertFalse($user1->can('create', $directory1));
        $this->assertFalse($user1->can('create', $directory2));
        $this->assertFalse($user1->can('create', $directory3));

        // Iteration 2
        config(["models.user.role.{$role}.abilities" => ['cabinet']]);
        $this->assertTrue($user1->can('create', $directory1));
        $this->assertFalse($user1->can('create', $directory2));
        $this->assertFalse($user1->can('create', $directory3));

        // Iteration 3
        config(["models.user.role.{$role}.abilities" => ['cabinet.directory.create']]);
        $this->assertTrue($user1->can('create', $directory1));
        $this->assertTrue($user1->can('create', $directory2));
        $this->assertTrue($user1->can('create', $directory3));
    }

    /**
     * @return void
     */
    public function test_create_manually()
    {
        $role = \ConfigHelper::randomKey('models.user.role');
        $user1 = \App\User::factory()->create(['role' => $role, 'additional_abilities' => ['cabinet']]);

        // Iteration 1
        $directory = \App\Directory::factory()->make(['user_id' => $user1->id]);
        config(["models.directory.type.{$directory->type}.is_manually" => true]);
        $this->assertTrue($user1->can('create', $directory));

        // Iteration 2
        config(["models.directory.type.{$directory->type}.is_manually" => false]);
        $this->assertFalse($user1->can('create', $directory));
    }

    /**
     * @return void
     */
    public function test_update()
    {
        $role = \ConfigHelper::randomKey('models.user.role');
        $user1 = \App\User::factory()->create(['role' => $role, 'additional_abilities' => []]);
        $user2 = \App\User::factory()->create(['role' => $role, 'additional_abilities' => []]);

        $directory1 = \App\Directory::factory()->make(['user_id' => $user1->id]);
        $directory2 = \App\Directory::factory()->make(['user_id' => $user2->id]);
        $directory3 = \App\Directory::factory()->make(['user_id' => null]);

        // Iteration 1
        config(["models.user.role.{$role}.abilities" => []]);
        $this->assertFalse($user1->can('update', $directory1));
        $this->assertFalse($user1->can('update', $directory2));
        $this->assertFalse($user1->can('update', $directory3));

        // Iteration 2
        config(["models.user.role.{$role}.abilities" => ['cabinet']]);
        $this->assertTrue($user1->can('update', $directory1));
        $this->assertFalse($user1->can('update', $directory2));
        $this->assertFalse($user1->can('update', $directory3));

        // Iteration 3
        config(["models.user.role.{$role}.abilities" => ['cabinet.directory.update']]);
        $this->assertTrue($user1->can('update', $directory1));
        $this->assertTrue($user1->can('update', $directory2));
        $this->assertTrue($user1->can('update', $directory3));
    }

    /**
     * @return void
     */
    public function test_update_manually()
    {
        $role = \ConfigHelper::randomKey('models.user.role');
        $user1 = \App\User::factory()->create(['role' => $role, 'additional_abilities' => ['cabinet']]);

        // Iteration 1
        $directory = \App\Directory::factory()->make(['user_id' => $user1->id]);
        config(["models.directory.type.{$directory->type}.is_manually" => true]);
        $this->assertTrue($user1->can('update', $directory));

        // Iteration 2
        config(["models.directory.type.{$directory->type}.is_manually" => false]);
        $this->assertFalse($user1->can('update', $directory));
    }

    /**
     * @return void
     */
    public function test_delete()
    {
        $role = \ConfigHelper::randomKey('models.user.role');
        $user1 = \App\User::factory()->create(['role' => $role, 'additional_abilities' => []]);
        $user2 = \App\User::factory()->create(['role' => $role, 'additional_abilities' => []]);

        $directory1 = \App\Directory::factory()->make(['user_id' => $user1->id]);
        $directory2 = \App\Directory::factory()->make(['user_id' => $user2->id]);
        $directory3 = \App\Directory::factory()->make(['user_id' => null]);

        // Iteration 1
        config(["models.user.role.{$role}.abilities" => []]);
        $this->assertFalse($user1->can('delete', $directory1));
        $this->assertFalse($user1->can('delete', $directory2));
        $this->assertFalse($user1->can('delete', $directory3));

        // Iteration 2
        config(["models.user.role.{$role}.abilities" => ['cabinet']]);
        $this->assertTrue($user1->can('delete', $directory1));
        $this->assertFalse($user1->can('delete', $directory2));
        $this->assertFalse($user1->can('delete', $directory3));

        // Iteration 3
        config(["models.user.role.{$role}.abilities" => ['cabinet.directory.delete']]);
        $this->assertTrue($user1->can('delete', $directory1));
        $this->assertTrue($user1->can('delete', $directory2));
        $this->assertTrue($user1->can('delete', $directory3));
    }

    /**
     * @return void
     */
    public function test_delete_manually()
    {
        $role = \ConfigHelper::randomKey('models.user.role');
        $user1 = \App\User::factory()->create(['role' => $role, 'additional_abilities' => ['cabinet']]);

        // Iteration 1
        $directory = \App\Directory::factory()->make(['user_id' => $user1->id]);
        config(["models.directory.type.{$directory->type}.is_manually" => true]);
        $this->assertTrue($user1->can('delete', $directory));

        // Iteration 2
        config(["models.directory.type.{$directory->type}.is_manually" => false]);
        $this->assertFalse($user1->can('delete', $directory));
    }
}
