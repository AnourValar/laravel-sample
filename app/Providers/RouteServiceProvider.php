<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/web/redirect';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->configureRoutePatterns();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('lax', function (Request $request) {
            return Limit::perMinute(150)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('strict', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('super_strict', function (Request $request) {
            return Limit::perHour(20)->by($request->user()?->id ?: $request->ip());
        });
    }

    /**
     * Route patterns
     */
    protected function configureRoutePatterns(): void
    {
        \Route::pattern('id', '[0-9]{1,18}');
        \Route::pattern('directory', '[0-9]{1,18}');
        \Route::pattern('field', '[0-9]{1,18}');
        \Route::pattern('file_virtual', '[0-9]{1,18}');
        //\Route::pattern('notification', '[0-9]{1,18}'); // uuid
        \Route::pattern('journal', '[0-9]{1,18}');
        \Route::pattern('setting', '[0-9]{1,18}');
        \Route::pattern('ticket', '[0-9]{1,18}');
        \Route::pattern('user', '[0-9]{1,18}');
        \Route::pattern('user_notification', '[0-9]{1,18}');

        \Route::pattern('token', '[0-9]{1,18}');
        \Route::pattern('entity_id', '[0-9]{1,18}');
    }
}
