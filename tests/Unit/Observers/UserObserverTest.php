<?php

namespace Tests\Unit\Observers;

use AnourValar\EloquentNotification\Adapters\Telegram\TelegramInterface;
use Tests\TestCase;

class UserObserverTest extends TestCase
{
    use \AnourValar\LaravelAtom\Traits\OptimizeCheckerTrait;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @return void
     */
    public function test_saving_telegram_chatid_1()
    {
        $this->partialMock(TelegramInterface::class, function ($mock) {
            $mock->shouldReceive('fetchChatId')->once()->withArgs(fn ($arg1) => $arg1 == 'foo')->andReturn('123');
        });

        $this->assertSame(
            ['telegram_username' => 'foo', 'telegram_chatid' => '123'],
            \App\User::factory()->create(['telegram_username' => 'foo'])->only('telegram_username', 'telegram_chatid')
        );
    }

    /**
     * @return void
     */
    public function test_saving_telegram_chatid_2()
    {
        $this->partialMock(TelegramInterface::class, function ($mock) {
            $mock->shouldReceive('fetchChatId')->once()->withArgs(fn ($arg1) => $arg1 == 'foo')->andReturn(null);
        });

        $this->assertSame(
            ['telegram_username' => null, 'telegram_chatid' => null],
            \App\User::factory()->create(['telegram_username' => 'foo'])->only('telegram_username', 'telegram_chatid')
        );
    }
}
