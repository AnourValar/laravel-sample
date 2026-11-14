<?php

namespace Tests\Unit\Policies;

use Tests\TestCase;

class FieldPolicyTest extends TestCase
{
    use \AnourValar\LaravelAtom\Traits\OptimizeCheckerTrait;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @return void
     */
    public function test_view()
    {
        $role = \ConfigHelper::randomKey('models.user.role');
        $user1 = \App\User::factory()->create(['role' => $role, 'additional_abilities' => []]);

        $field1 = \App\Field::factory()->make(['user_id' => $user1->id]);
        $field2 = \App\Field::factory()->make([]);

        // Iteration 1
        config(["models.user.role.{$role}.abilities" => []]);
        $this->assertFalse($user1->can('view', $field1));
        $this->assertFalse($user1->can('view', $field2));

        // Iteration 2
        config(["models.user.role.{$role}.abilities" => ['cabinet']]);
        $this->assertTrue($user1->can('view', $field1));
        $this->assertFalse($user1->can('view', $field2));

        // Iteration 3
        config(["models.user.role.{$role}.abilities" => ['cabinet.field.read']]);
        $this->assertTrue($user1->can('view', $field1));
        $this->assertTrue($user1->can('view', $field2));
    }

    /**
     * @return void
     */
    public function test_save()
    {
        $role = \ConfigHelper::randomKey('models.user.role');
        $user1 = \App\User::factory()->create(['role' => $role, 'additional_abilities' => []]);

        $field1 = \App\Field::factory()->make(['user_id' => $user1->id, 'status' => 'draft']);
        $field2 = \App\Field::factory()->make(['name' => $field1->name, 'status' => 'draft']);
        config(["models.field.name.{$field1->name}.is_readonly" => false]);

        // Iteration 1
        config(["models.user.role.{$role}.abilities" => []]);
        $this->assertFalse($user1->can('save', $field1));
        $this->assertFalse($user1->can('save', $field2));

        // Iteration 2
        config(["models.user.role.{$role}.abilities" => ['cabinet']]);
        $this->assertTrue($user1->can('save', $field1));
        $this->assertFalse($user1->can('save', $field2));

        // Iteration 3
        $this->assertFalse($user1->can('save', (clone $field1)->forceFill(['status' => 'moderation'])));

        // Iteration 4
        config(["models.field.name.{$field1->name}.is_readonly" => true]);
        $this->assertFalse($user1->can('save', $field1));
    }

    /**
     * @return void
     */
    public function test_update()
    {
        $role = \ConfigHelper::randomKey('models.user.role');
        $user1 = \App\User::factory()->create(['role' => $role, 'additional_abilities' => []]);

        $field1 = \App\Field::factory()->make(['user_id' => $user1->id]);
        $field2 = \App\Field::factory()->make([]);

        // Iteration 1
        config(["models.user.role.{$role}.abilities" => []]);
        $this->assertFalse($user1->can('update', $field1));
        $this->assertFalse($user1->can('update', $field2));

        // Iteration 2
        config(["models.user.role.{$role}.abilities" => ['cabinet']]);
        $this->assertFalse($user1->can('update', $field1));
        $this->assertFalse($user1->can('update', $field2));

        // Iteration 3
        config(["models.user.role.{$role}.abilities" => ['cabinet.field.update']]);
        $this->assertTrue($user1->can('update', $field1));
        $this->assertTrue($user1->can('update', $field2));
    }

    /**
     * @return void
     */
    public function test_delete()
    {
        $role = \ConfigHelper::randomKey('models.user.role');
        $user1 = \App\User::factory()->create(['role' => $role, 'additional_abilities' => []]);

        $field1 = \App\Field::factory()->make(['user_id' => $user1->id, 'status' => 'draft']);
        $field2 = \App\Field::factory()->make(['name' => $field1->name, 'status' => 'draft']);
        config(["models.field.name.{$field1->name}.is_readonly" => false]);

        // Iteration 1
        config(["models.user.role.{$role}.abilities" => []]);
        $this->assertFalse($user1->can('delete', $field1));
        $this->assertFalse($user1->can('delete', $field2));

        // Iteration 2
        config(["models.user.role.{$role}.abilities" => ['cabinet']]);
        $this->assertTrue($user1->can('delete', $field1));
        $this->assertFalse($user1->can('delete', $field2));

        // Iteration 3
        $this->assertFalse($user1->can('delete', (clone $field1)->forceFill(['status' => 'moderation'])));

        // Iteration 4
        config(["models.field.name.{$field1->name}.is_readonly" => true]);
        $this->assertFalse($user1->can('delete', $field1));
    }
}
