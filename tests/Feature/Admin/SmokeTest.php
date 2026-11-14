<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class SmokeTest extends TestCase
{
    use \AnourValar\LaravelAtom\Traits\OptimizeCheckerTrait;
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * @var array
     */
    private array $excludeRoutes = [
        'admin.administration.diagnostic',
    ];

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
            \Illuminate\Session\Middleware\AuthenticateSession::class,
        ]);

        \Gate::before(function () {
            return true;
        });
    }

    /**
     * @return void
     */
    public function test_all_routes_in_menu()
    {
        foreach (\ConfigHelper::keys('models.user.role', ['abilities' => 'admin']) as $role) {
            $user = \App\User::factory()->state(['role' => $role])->create();
            $user->refresh();

            foreach (config('admin.menu') as $menu) {
                if (isset($menu['route']) && $user->can(implode('|', $menu['user_ability']))) {
                    $this->openRoute($menu['route'], $user);
                }

                if (isset($menu['dropdown'])) {
                    foreach ($menu['dropdown'] as $submenu) {
                        if ($user->can(implode('|', $submenu['user_ability']))) {
                            $this->openRoute($submenu['route'], $user);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param string|array $route
     * @param \App\User $user
     * @return void
     */
    private function openRoute($route, \App\User $user): void
    {
        if (in_array($route, $this->excludeRoutes)) {
            return;
        }

        if (is_array($route)) {
            $route = route($route[0], $route[1]);
        } else {
            $route = route($route);
        }

        $this
            ->actingAs($user, 'web')
            ->get($route)
            ->assertStatus(200)
            ->assertDontSee('models/')
            ->assertDontSee('handlers/')
            ->assertDontSee('services/')
            ->assertDontSee('entities/')
            ->assertDontSee('policies/')
            ->assertDontSee('controllers/')
            ->assertDontSee('notifications/')
            ->assertDontSee('requests/')
            ->assertDontSee('instance/');
    }
}
