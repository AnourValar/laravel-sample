<?php

namespace Tests\Unit\Listeners;

use Tests\TestCase;

class UserSeedListenerTest extends TestCase
{
    use \AnourValar\LaravelAtom\Traits\OptimizeCheckerTrait;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * {@inheritDoc}
     * @see \Illuminate\Foundation\Testing\TestCase::setUp()
     */
    protected function setUp(): void
    {
        parent::setUp();

        config(['eloquent_notification.trigger' => [
            'foo' => ['bind' => \App\Notifications\Trigger\PersonalMessageNotification::class, 'title' => 'Foo', 'channels' => ['sms', 'telegram', 'mail', 'database'], 'is_public' => true],
            'bar' => ['bind' => \App\Notifications\Trigger\PersonalMessageNotification::class, 'title' => 'Bar', 'channels' => ['mail', 'database'], 'is_public' => true],
            'baz' => ['bind' => \App\Notifications\Trigger\PersonalMessageNotification::class, 'title' => 'Baz', 'channels' => ['sms', 'telegram', 'mail', 'database'], 'is_public' => false],
            'foobar' => ['bind' => \App\Notifications\Trigger\PersonalMessageNotification::class, 'title' => 'Foobar', 'channels' => ['database'], 'is_public' => false],
        ]]);
    }

    /**
     * @return void
     */
    public function test_name_is_customer()
    {
        $user = \App\User::factory()->create(['role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true])]);
        $this->assertNull(\App\UserNotification::where('user_id', '=', $user->id)->first());

        event(new \Illuminate\Auth\Events\Registered($user));

        $this->assertSame(
            [
                ['trigger' => 'foo', 'channels' => ['database']],
                ['trigger' => 'bar', 'channels' => ['database']],
                ['trigger' => 'baz', 'channels' => ['database', 'mail', 'sms', 'telegram']],
                ['trigger' => 'foobar', 'channels' => ['database']],
            ],
            \App\UserNotification::where('user_id', '=', $user->id)->orderBy('id', 'ASC')->get(['trigger', 'channels'])->toArray()
        );
    }

    /**
     * @return void
     */
    public function test_name_not_customer()
    {
        $user = \App\User::factory()->create(['role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => false])]);
        $this->assertNull(\App\UserNotification::where('user_id', '=', $user->id)->first());

        event(new \Illuminate\Auth\Events\Registered($user));

        $this->assertNull(\App\UserNotification::where('user_id', '=', $user->id)->first());
    }
}
