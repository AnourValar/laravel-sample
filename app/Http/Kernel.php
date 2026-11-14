<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        //\Illuminate\Http\Middleware\TrustHosts::class,
        \Illuminate\Http\Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \Illuminate\Foundation\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \AnourValar\LaravelAtom\Http\Middleware\JsonResponseFlags::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [
            \AnourValar\LaravelAtom\Http\Middleware\SetDefaultGuard::class.':web',
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            //\Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            //\Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks::class, // \Illuminate\Support\defer()
        ],

        'api' => [
            \AnourValar\LaravelAtom\Http\Middleware\Api\Json::class,
            \AnourValar\LaravelAtom\Http\Middleware\Api\Locale::class.':ru,en',
            \AnourValar\LaravelAtom\Http\Middleware\Api\Timezone::class,
            //\AnourValar\LaravelAtom\Http\Middleware\Api\ClientRestrict::class.':v1',
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class, // never!
            //\Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            //\Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks::class, // \Illuminate\Support\defer()
        ],
    ];

    /**
     * The application's middleware aliases.
     *
     * Aliases may be used instead of class names to conveniently assign middleware to routes and groups.
     *
     * @var array<string, class-string|string>
     */
    protected $middlewareAliases = [
        'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
        //'ability' => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \Illuminate\Auth\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        //'precognitive' => \AnourValar\LaravelAtom\Http\Middleware\Api\HandlePrecognitiveRequests::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class, // \Illuminate\Routing\Middleware\ThrottleRequestsWithRedis::class
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,

        'diagnostic' => \App\Http\Middleware\Diagnostic::class,
        'web.select2' => \AnourValar\LaravelAtom\Http\Middleware\Web\Select2::class,
        'web.locale' => \AnourValar\LaravelAtom\Http\Middleware\Web\Locale::class,
        'token' => \AnourValar\LaravelAtom\Http\Middleware\Api\Token::class,
        'ip_whitelist' => \AnourValar\LaravelAtom\Http\Middleware\IpWhitelist::class,

        'confirm.pow' => \AnourValar\EloquentNotification\Http\Middleware\ConfirmPow::class,
        'confirm.email.input' => \AnourValar\EloquentNotification\Http\Middleware\ConfirmEmailInput::class,
        'confirm.email.my' => \AnourValar\EloquentNotification\Http\Middleware\ConfirmEmailMy::class,
        'confirm.phone.input' => \AnourValar\EloquentNotification\Http\Middleware\ConfirmPhoneInput::class,
        'confirm.phone.my' => \AnourValar\EloquentNotification\Http\Middleware\ConfirmPhoneMy::class,
        'confirm.totp.input' => \AnourValar\EloquentNotification\Http\Middleware\ConfirmTotpInput::class,
        'confirm.totp.my' => \AnourValar\EloquentNotification\Http\Middleware\ConfirmTotpMy::class,
        'confirm.fa.my' => \AnourValar\EloquentNotification\Http\Middleware\ConfirmFaMy::class,
        'confirm.fa.my.dynamic' => \AnourValar\EloquentNotification\Http\Middleware\ConfirmFaMyDynamic::class,
    ];
}
