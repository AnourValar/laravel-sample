<?php

namespace Tests\Unit\Listeners;

use Tests\TestCase;

class TelegramUsernameCleanerListenerTest extends TestCase
{
    use \AnourValar\LaravelAtom\Traits\OptimizeCheckerTrait;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @return void
     */
    public function test_handle()
    {
        $user = \App\User::factory()->create();
        \App\User::where('id', '=', $user->id)->update(['telegram_username' => 'foo_bar', 'telegram_chatid' => '12345']);

        $event = new \AnourValar\EloquentNotification\Events\TelegramUsernameBlocked($user);
        new \App\Listeners\TelegramUsernameCleanerListener()->handle($event);

        $this->assertNull($user->refresh()->telegram_username);
        $this->assertNull($user->refresh()->telegram_chatid);
    }
}
