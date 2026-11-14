<?php

namespace Tests\Unit\Models;

use Tests\TestCase;

class FieldTest extends TestCase
{
    use \AnourValar\LaravelAtom\Traits\OptimizeCheckerTrait;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @return void
     */
    public function test_scope_light_heavy()
    {
        \Date::setTestNow('2024-07-20 21:00:00');

        $field = \App\Field::factory()->make([
            'user_id' => \App\User::factory()->create(['role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true])])->id,
            'name' => 'profile_form',
            'data' => ['value' => 'ru_phys'],
            'status' => 'draft',
            'created_at' => now(),
        ]);
        $field->validate()->save();
        $fileVirtual = $this->createFile($field, 'attachment');

        // light
        $this->assertSame(
            [
                'data' => ['value' => 'ru_phys'],
                'status' => 'draft',
                'created_at' => '2024-07-20T21:00:00.000000Z',
                'file_virtuals' => [\App\FileVirtual::light()->find($fileVirtual->id)->toArray()],
            ],
            \App\Field::light()->find($field->id)->toArray()
        );

        // heavy
        $this->assertSame(
            [
                'data' => ['value' => 'ru_phys'],
                'status' => 'draft',
                'created_at' => '2024-07-20T21:00:00.000000Z',
                'file_virtuals' => [\App\FileVirtual::light()->find($fileVirtual->id)->toArray()],
            ],
            \App\Field::heavy()->find($field->id)->toArray()
        );
    }

    /**
     * @return void
     */
    public function test_jsonNested_data()
    {
        $this->assertSame(
            [
                'a' => 1,
                'b' => 1.5,
                'c' => '2',
            ],
            (new \App\Field())->forceFill(['data' => [
                'a' => 1,
                'c' => '2',
                'b' => 1.5,
                'd' => ' ',
            ]])->data
        );

        $field = (new \App\Field())->forceFill(['data' => null]);
        $this->assertNull($field->data);
        $this->assertNull($field->getAttributes()['data']); // encrypted

        $field = (new \App\Field())->forceFill(['data' => ['foo' => 'bar']]);
        $this->assertSame(['foo' => 'bar'], $field->data);
        $this->assertNotSame('{"foo":"bar"}', $field->getAttributes()['data']); // encrypted

        $field = (new \App\Field())->forceFill(['data' => ['a', 'b']]);
        $this->assertSame(['a', 'b'], $field->data);
        $this->assertNotSame('["a","b"]', $field->getAttributes()['data']); // encrypted
    }

    /**
     * @return void
     */
    public function test_acl()
    {
        \App\Field::query()->delete();
        $role = \ConfigHelper::randomKey('models.user.role');
        $user1 = \App\User::factory()->create([/*'id' => null,*/ 'role' => $role, 'additional_abilities' => []]);
        $user2 = \App\User::factory()->create([/*'id' => $user1->id,*/ 'role' => $role, 'additional_abilities' => []]);
        $user3 = \App\User::factory()->create([/*'id' => null,*/ 'role' => $role, 'additional_abilities' => []/*, 'acl' => [$user1->id]*/]);
        $user4 = \App\User::factory()->create([/*'id' => $user3->id,*/ 'role' => $role, 'additional_abilities' => []/*, 'acl' => [$user1->id]*/]);

        $field1 = \App\Field::factory()->create(['user_id' => $user1->id]);
        $field2 = \App\Field::factory()->create(['user_id' => $user2->id]);
        $field3 = \App\Field::factory()->create(['user_id' => $user3->id]);
        $field4 = \App\Field::factory()->create(['user_id' => $user4->id]);


        // Iteration 1
        config(["models.user.role.{$role}.abilities" => []]);

        $this->assertSame(
            [$field1->id],
            \App\Field::acl($user1)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );

        $this->assertSame(
            [$field2->id],
            \App\Field::acl($user2)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );

        $this->assertSame(
            [$field3->id],
            \App\Field::acl($user3)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );

        $this->assertSame(
            [$field4->id],
            \App\Field::acl($user4)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );


        // Iteration 2
        config(["models.user.role.{$role}.abilities" => ['cabinet.field.read']]);

        $this->assertSame(
            [$field1->id, $field2->id, $field3->id, $field4->id],
            \App\Field::acl($user1)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );

        $this->assertSame(
            [$field1->id, $field2->id, $field3->id, $field4->id],
            \App\Field::acl($user2)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );

        $this->assertSame(
            [$field1->id, $field2->id, $field3->id, $field4->id],
            \App\Field::acl($user3)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );

        $this->assertSame(
            [$field1->id, $field2->id, $field3->id, $field4->id],
            \App\Field::acl($user4)->orderBy('id', 'ASC')->pluck('id')->toArray()
        );
    }
}
