<?php

namespace Tests\Unit\Observers;

use Tests\TestCase;

class DirectoryObserverTest extends TestCase
{
    use \AnourValar\LaravelAtom\Traits\OptimizeCheckerTrait;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @return void
     */
    public function test_saving_search()
    {
        // Iteration 1: search is generated on create
        $directory = \App\Directory::factory()->create([
            'type' => 'user_tag',
            'user_id' => null,
            'data' => ['title' => 'Foo Bar', 'color' => '#ffffff'],
        ]);
        $this->assertSame(' bar foo ', $directory->refresh()->search);

        // Iteration 2: search is regenerated when "data" changes
        $directory->forceFill(['data' => ['title' => 'Beta Gamma', 'color' => '#ffffff']])->save();
        $this->assertSame(' beta gamma ', $directory->refresh()->search);

        // Iteration 3

        \DB::table('directories')->where('id', '=', $directory->id)->update(['search' => ' sentinel ']);
        $directory->touch();
        $this->assertSame(' sentinel ', $directory->refresh()->search);
    }

    /**
     * @return void
     */
    public function test_deleting_type_handler()
    {
        $directory = \App\Directory::factory()->create(['type' => 'user_tag']);

        $this->mock(\App\Handlers\Models\Directory\Type\UserTagType::class, function ($mock) use ($directory) {
            $mock->shouldReceive('onDeleted')->once()->withArgs(fn ($arg) => $arg->is($directory));
        });

        $directory->delete();

        $this->assertNull(\App\Directory::find($directory->id));
    }
}
