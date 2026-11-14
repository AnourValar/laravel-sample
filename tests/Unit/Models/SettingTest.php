<?php

namespace Tests\Unit\Models;

use Tests\TestCase;

class SettingTest extends TestCase
{
    use \AnourValar\LaravelAtom\Traits\OptimizeCheckerTrait;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @return void
     */
    public function test_scope_light()
    {
        \Date::setTestNow('2024-07-20 21:00:00');

        $setting = \App\Setting::forceCreate([
            'name' => 'foo-1',
            'value' => ['foo' => 'bar'],
            'created_at' => now(),
        ]);

        // light
        $this->assertSame(
            [
                'id' => $setting->id,
                'name' => 'foo-1',
                'value' => ['foo' => 'bar'],
            ],
            \App\Setting::light()->find($setting->id)->toArray()
        );
    }

    /**
     * @return void
     */
    public function test_jsonNested()
    {
        $this->assertSame(
            ['a' => '1', 'b' => 2],
            (new \App\Setting())->forceFill(['value' => ['b' => 2, 'a' => '1', 'c' => ' ']])->value
        );
    }
}
