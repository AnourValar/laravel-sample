<?php

namespace Tests\Unit\Handlers\Models\Directory\Type;

use Tests\TestCase;

class UserTagTypeTest extends TestCase
{
    use \AnourValar\LaravelAtom\Traits\OptimizeCheckerTrait;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @return void
     */
    public function test_onDeleted()
    {
        $user1 = \App\User::factory()->hasRole(['is_customer' => true])->create();
        $user2 = \App\User::factory()->hasRole(['is_customer' => true])->create();
        $user3 = \App\User::factory()->hasRole(['is_customer' => true])->create();

        $directory1 = \App\Directory::factory()->create(['user_id' => null, 'type' => 'user_tag']);
        $directory2 = \App\Directory::factory()->create(['user_id' => null, 'type' => 'user_tag']);
        $directory3 = \App\Directory::factory()->create(['user_id' => null, 'type' => 'user_tag']);

        $user1->forceFill(['tag_ids' => [$directory1->id, $directory2->id, $directory3->id]])->save();
        $user2->forceFill(['tag_ids' => [$directory1->id, $directory2->id]])->save();
        tap($user3->forceFill(['tag_ids' => [$directory1->id]]))->save()->delete();


        // Iteration 0
        $this->assertSame([$directory1->id, $directory2->id, $directory3->id], $user1->directories()->orderBy('id', 'ASC')->pluck('id')->toArray());
        $this->assertSame([$directory1->id, $directory2->id], $user2->directories()->orderBy('id', 'ASC')->pluck('id')->toArray());
        $this->assertSame([$directory1->id], $user3->directories()->orderBy('id', 'ASC')->pluck('id')->toArray());

        $this->assertSame([$directory1->id, $directory2->id, $directory3->id], $user1->refresh()->tag_ids);
        $this->assertSame([$directory1->id, $directory2->id], $user2->refresh()->tag_ids);
        $this->assertSame([$directory1->id], $user3->refresh()->tag_ids);


        // Iteration 1
        $directory1->delete();

        $this->assertSame([$directory2->id, $directory3->id], $user1->directories()->orderBy('id', 'ASC')->pluck('id')->toArray());
        $this->assertSame([$directory2->id], $user2->directories()->orderBy('id', 'ASC')->pluck('id')->toArray());
        $this->assertSame([], $user3->directories()->orderBy('id', 'ASC')->pluck('id')->toArray());

        $this->assertSame([$directory2->id, $directory3->id], $user1->refresh()->tag_ids);
        $this->assertSame([$directory2->id], $user2->refresh()->tag_ids);
        $this->assertNull($user3->refresh()->tag_ids);


        // Iteration 2
        $directory2->delete();

        $this->assertSame([$directory3->id], $user1->directories()->orderBy('id', 'ASC')->pluck('id')->toArray());
        $this->assertSame([], $user2->directories()->orderBy('id', 'ASC')->pluck('id')->toArray());
        $this->assertSame([], $user3->directories()->orderBy('id', 'ASC')->pluck('id')->toArray());

        $this->assertSame([$directory3->id], $user1->refresh()->tag_ids);
        $this->assertNull($user2->refresh()->tag_ids);
        $this->assertNull($user3->refresh()->tag_ids);


        // Iteration 3
        $directory3->delete();

        $this->assertSame([], $user1->directories()->orderBy('id', 'ASC')->pluck('id')->toArray());
        $this->assertSame([], $user2->directories()->orderBy('id', 'ASC')->pluck('id')->toArray());
        $this->assertSame([], $user3->directories()->orderBy('id', 'ASC')->pluck('id')->toArray());

        $this->assertNull($user1->refresh()->tag_ids);
        $this->assertNull($user2->refresh()->tag_ids);
        $this->assertNull($user3->refresh()->tag_ids);
    }
}
