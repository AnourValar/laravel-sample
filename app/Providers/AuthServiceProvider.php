<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // ACL
        \Gate::before(function (\App\User $user, $ability, array $params) {
            if (! $params) {
                $ability = explode('|', $ability);

                if (array_intersect($ability, $user->role_details['abilities'])) {
                    return true;
                }

                if (array_intersect($ability, (array) $user->additional_abilities)) {
                    return true;
                }
            }
        });

        \Gate::define('viewPulse', function (\App\User $user) {
            return $user->can('admin.administration');
        });
    }
}
