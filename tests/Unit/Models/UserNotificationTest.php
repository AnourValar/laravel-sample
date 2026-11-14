<?php

namespace Tests\Unit\Models;

use Tests\TestCase;

class UserNotificationTest extends TestCase
{
    use \AnourValar\LaravelAtom\Traits\OptimizeCheckerTrait;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @return void
     */
    public function test_scope_light()
    {
        $userNotification = \App\UserNotification::factory()->create([
            'user_id' => 1,
            'trigger' => 'foo',
            'channels' => ['bar', 'baz'],
            'created_at' => '2024-05-29 12:00:00',
        ]);

        // light
        $this->assertSame(
            [
                'id' => $userNotification->id,
                'user_id' => 1,
                'trigger' => 'foo',
                'channels' => ['bar', 'baz'],
                'created_at' => '2024-05-29T12:00:00.000000Z',
            ],
            \App\UserNotification::light()->find($userNotification->id)->toArray()
        );
    }
}
