<?php

namespace Tests\Unit\Policies;

use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use \AnourValar\LaravelAtom\Traits\OptimizeCheckerTrait;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @return void
     */
    public function test_create()
    {
        $role = \ConfigHelper::randomKey('models.user.role');
        $user = \App\User::factory()->create(['role' => $role, 'additional_abilities' => []]);

        $user2 = \App\User::factory()->make(['role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => false]), 'additional_abilities' => null]);
        $user3 = \App\User::factory()->make(['role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true]), 'additional_abilities' => null]);

        // Iteration 0
        $handler = \App::make(\App\Policies\UserPolicy::class);
        $this->assertFalse($handler->create(null, $user2));
        $this->assertTrue($handler->create(null, $user3));

        // Iteration 1
        config(["models.user.role.{$role}.abilities" => []]);
        $this->assertFalse($user->can('create', $user2));

        // Iteration 2
        config(["models.user.role.{$role}.abilities" => ['cabinet']]);
        $this->assertFalse($user->can('create', $user2));

        // Iteration 3
        config(["models.user.role.{$role}.abilities" => ['cabinet.user.create']]);
        $this->assertTrue($user->can('create', $user2));
    }

    /**
     * @return void
     */
    public function test_create_extra()
    {
        $role = \ConfigHelper::randomKey('models.user.role');
        $user = \App\User::factory()->create(['role' => $role, 'additional_abilities' => []]);
        config(["models.user.role.{$role}.abilities" => ['cabinet']]);

        // Iteration 1
        $userNew = \App\User::factory()->make(['pid' => 1, 'role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true]), 'additional_abilities' => null]);
        $this->assertFalse($user->can('create', $userNew));

        // Iteration 2
        $userNew = \App\User::factory()->make(['pid' => null, 'role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => false]), 'additional_abilities' => null]);
        $this->assertFalse($user->can('create', $userNew));

        // Iteration 4
        $userNew = \App\User::factory()->make(['pid' => null, 'role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true]), 'additional_abilities' => ['foo']]);
        $this->assertFalse($user->can('create', $userNew));

        // Iteration 4
        $userNew = \App\User::factory()->make(['pid' => null, 'role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true]), 'additional_abilities' => null]);
        $this->assertTrue($user->can('create', $userNew));
    }

    /**
     * @return void
     */
    public function test_update()
    {
        $role = \ConfigHelper::randomKey('models.user.role');
        $user = \App\User::factory()->create(['role' => $role, 'additional_abilities' => []]);

        $user2 = \App\User::factory()->make(['additional_abilities' => null]);

        // Iteration 1
        config(["models.user.role.{$role}.abilities" => []]);
        $this->assertFalse($user->can('update', $user));
        $this->assertFalse($user->can('update', $user2));

        // Iteration 2
        config(["models.user.role.{$role}.abilities" => ['cabinet']]);
        $this->assertTrue($user->can('update', $user));
        $this->assertFalse($user->can('update', $user2));

        // Iteration 3
        config(["models.user.role.{$role}.abilities" => ['cabinet.user.update']]);
        $this->assertTrue($user->can('update', $user));
        $this->assertTrue($user->can('update', $user2));
    }

    /**
     * @return void
     */
    public function test_update_additional_abilities()
    {
        $role = \ConfigHelper::randomKey('models.user.role');
        $user = \App\User::factory()->create(['role' => $role, 'additional_abilities' => []]);
        config(["models.user.role.{$role}.abilities" => ['cabinet']]);

        // Iteration 1
        $this->assertTrue($user->can('update', $user));

        // Iteration 2
        $this->assertFalse($user->can('update', $user->forceFill(['additional_abilities' => ['foo']])));
    }

    /**
     * @return void
     */
    public function test_delete()
    {
        $role = \ConfigHelper::randomKey('models.user.role');
        $user = \App\User::factory()->create(['role' => $role, 'additional_abilities' => []]);

        $user2 = \App\User::factory()->make([]);

        // Iteration 1
        config(["models.user.role.{$role}.abilities" => []]);
        $this->assertFalse($user->can('delete', $user));
        $this->assertFalse($user->can('delete', $user2));

        // Iteration 2
        config(["models.user.role.{$role}.abilities" => ['cabinet']]);
        $this->assertFalse($user->can('delete', $user));
        $this->assertFalse($user->can('delete', $user2));

        // Iteration 3
        config(["models.user.role.{$role}.abilities" => ['cabinet.user.delete']]);
        $this->assertTrue($user->can('delete', $user));
        $this->assertTrue($user->can('delete', $user2));
    }

    /**
     * @return void
     */
    public function test_notify()
    {
        $role = \ConfigHelper::randomKey('models.user.role');
        $user = \App\User::factory()->create(['role' => $role, 'additional_abilities' => []]);

        $user2 = \App\User::factory()->make([]);

        // Iteration 1
        config(["models.user.role.{$role}.abilities" => []]);
        $this->assertFalse($user->can('notify', $user));
        $this->assertFalse($user->can('notify', $user2));

        // Iteration 2
        config(["models.user.role.{$role}.abilities" => ['cabinet']]);
        $this->assertFalse($user->can('notify', $user));
        $this->assertFalse($user->can('notify', $user2));

        // Iteration 3
        config(["models.user.role.{$role}.abilities" => ['cabinet.user.notify']]);
        $this->assertTrue($user->can('notify', $user));
        $this->assertTrue($user->can('notify', $user2));
    }
}
