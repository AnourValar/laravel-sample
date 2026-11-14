<?php

namespace Tests\Unit\Console\Commands;

use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use \AnourValar\LaravelAtom\Traits\OptimizeCheckerTrait;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @return void
     */
    public function test_handle()
    {
        \Date::setTestNow('2026-06-19 15:00:00');

        // ...

        \Queue::fake();
        $this->artisan('user:admin foo@example.org password')->assertExitCode(0);

        $this->assertSame(
            $ref = [
                'role' => 'admin',
                'email' => 'foo@example.org',
                'first_name' => 'Администратор',
                'created_at' => '2026-06-19T15:00:00.000000Z',
            ],
            json_decode(json_encode(($user = \App\User::latest('id')->first())->only(array_keys($ref))), true)
        );
        $this->assertTrue(\Hash::check('password', $user->password));

        //\Queue::assertPushed(\App\Jobs\FooJob::class, 1);
        //\Queue::assertPushed(\App\Jobs\FooJob::class, fn ($job) => $job->user->id == $user->id);
    }

    /**
     * @return void
     */
    public function test_handle_failure_validation()
    {
        \App\User::factory()->create(['email' => 'foo@example.org']);
        $countBefore = \App\User::withTrashed()->count();

        \Artisan::call('user:admin foo@example.org password');

        $this->assertStringContainsString(trans('validation.unique', ['attribute' => trans('models/user.attributes.email')]), \Artisan::output());
        $this->assertSame($countBefore, \App\User::withTrashed()->count());
    }
}
