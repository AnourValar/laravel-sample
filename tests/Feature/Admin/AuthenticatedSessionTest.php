<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AuthenticatedSessionTest extends TestCase
{
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * {@inheritDoc}
     * @see \Illuminate\Foundation\Testing\TestCase::setUp()
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Routing\Middleware\ThrottleRequests::class,
            \Illuminate\Routing\Middleware\ThrottleRequestsWithRedis::class,
        ]);
    }

    /**
     * @return void
     */
    public function test_login_success_without_totp()
    {
        $user = \App\User::factory()->create(['email' => 'foo@example.org', 'totp_secret' => null]);

        $this
            ->post('/web/login', ['email' => 'foo@example.org', 'password' => 'password'])
            ->assertRedirect(\App\Providers\RouteServiceProvider::HOME);

        $this->assertAuthenticatedAs($user);
    }

    /**
     * @return void
     */
    public function test_login_success_with_totp()
    {
        \Date::setTestNow('2025-10-14 10:50:00');
        $user = \App\User::factory()->totpFixed()->create(['email' => 'foo@example.org']);

        $this
            ->post('/web/login', ['email' => 'foo@example.org', 'password' => 'password', 'code_totp' => '080199'])
            ->assertRedirect(\App\Providers\RouteServiceProvider::HOME);

        $this->assertAuthenticatedAs($user);
    }

    /**
     * @return void
     */
    public function test_login_failure_invalid_totp_keeps_guest()
    {
        \Date::setTestNow('2025-10-14 10:50:00');
        \App\User::factory()->totpFixed()->create(['email' => 'foo@example.org']);

        $this
            ->post('/web/login', ['email' => 'foo@example.org', 'password' => 'password', 'code_totp' => '000000'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /**
     * @return void
     */
    public function test_login_failure_missing_totp_keeps_guest()
    {
        \Date::setTestNow('2025-10-14 10:50:00');
        \App\User::factory()->totpFixed()->create(['email' => 'foo@example.org']);

        $this
            ->post('/web/login', ['email' => 'foo@example.org', 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /**
     * @return void
     */
    public function test_login_failure_invalid_credentials()
    {
        \App\User::factory()->create(['email' => 'foo@example.org', 'totp_secret' => null]);

        $this
            ->post('/web/login', ['email' => 'foo@example.org', 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
