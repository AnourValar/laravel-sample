<?php

namespace Tests\Unit\Providers;

use Tests\TestCase;

class AuthServiceProviderTest extends TestCase
{
    use \AnourValar\LaravelAtom\Traits\OptimizeCheckerTrait;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @return void
     */
    public function test_gate()
    {
        $user = \App\User::factory()->make();
        config(["models.user.role.{$user->role}.abilities" => ['foo', 'bar']]);

        $this->assertTrue($user->can('foo'));
        $this->assertTrue($user->can('bar'));
        $this->assertTrue($user->can('foo|bar'));
        $this->assertTrue($user->can('foo|test'));
        $this->assertTrue($user->can('test|bar'));

        $this->assertFalse($user->can('baz'));
        $this->assertFalse($user->can('one|two'));

        $user->additional_abilities = ['baz'];
        $this->assertTrue($user->can('baz'));
    }
}
