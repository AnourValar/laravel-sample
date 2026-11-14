<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //\DB::listen(function ($sql) { dump($sql->sql, $sql->bindings); });

        // Carbon
        \Date::use(\Carbon\CarbonImmutable::class);

        // Eloquent
        \Illuminate\Database\Eloquent\Model::preventLazyLoading(! $this->app->isProduction());
        \Illuminate\Database\Eloquent\Model::preventAccessingMissingAttributes(! $this->app->isProduction());
        \Illuminate\Database\Eloquent\Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        // Polymorph map
        \Illuminate\Database\Eloquent\Relations\Relation::morphMap([
            'directory' => \App\Directory::class,
            'field' => \App\Field::class,
            'file_virtual' => \App\FileVirtual::class,
            'journal' => \App\Journal::class,
            'setting' => \App\Setting::class,
            'ticket' => \App\Ticket::class,
            'user' => \App\User::class,
            'user_notification' => \App\UserNotification::class,
        ]);

        // Validation
        $this->addPhoneValidator();

        // Blade
        \Illuminate\Pagination\Paginator::useBootstrap();

        // Migrations
        \Illuminate\Database\Schema\Builder::defaultStringLength(null); // @phpstan-ignore argument.type

        // Middleware
        \Illuminate\Auth\Middleware\Authenticate::redirectUsing(fn () => route('login'));
        \Illuminate\Auth\Middleware\RedirectIfAuthenticated::redirectUsing(fn () => url(\App\Providers\RouteServiceProvider::HOME));
        \Illuminate\Http\Middleware\TrustProxies::at('*');

        // Pulse
        \Pulse::handleExceptionsUsing(function ($e) {
            \Log::debug('An exception happened in Pulse', [
                'message' => $e->getMessage(),
                'stack' => $e->getTraceAsString(),
            ]);
        });

        // Field (wildcard expansion)
        \App\Services\FieldService::expandConfig();
    }

    /**
     * @return void
     */
    private function addPhoneValidator(): void
    {
        \Validator::extend('phone', function ($attribute, $value, $parameters, $validator) {
            if (! is_string($value)) {
                return false;
            }

            foreach (config('instance.phone.regexp') as $regexp) {
                if (preg_match($regexp, $value)) {
                    return true;
                }
            }

            return false;
        });

        \Validator::replacer('phone', function ($message, $attribute, $rule, $parameters, $validator) {
            return trans('validation.regex', ['attribute' => $validator->getDisplayableAttribute($attribute)]);
        });
    }
}
