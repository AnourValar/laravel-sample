<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirect(Request $request)
    {
        $request->session()->reflash();

        if ($request->user()) {
            $route = config('models.user.role')[$request->user()->role]['route'];
            return redirect($route ? route($route) : '/');
        }

        return redirect()->route('login');
    }

    /**
     * Интерфейс: форма входа
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\View\View
     */
    public function login(Request $request)
    {
        return view('auth.login');
    }

    /**
     * Аутентификация
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @param \AnourValar\EloquentJournal\Service $journalService
     * @return \Illuminate\Http\RedirectResponse
     * @throws \App\Exceptions\ValidationException
     */
    public function store(LoginRequest $request, \AnourValar\EloquentJournal\Service $journalService)
    {
        // 1FA
        $request->merge(['email' => mb_strtolower($request->input('email'))])->authenticate('validate');
        $user = \Auth::getLastAttempted();

        // 2FA
        try {
            if ($user->totp_secret) {
                \App::make(\AnourValar\EloquentNotification\ConfirmService::class)->validateTotpCryptogram(
                    $user->totp_secret,
                    $request->input('code_totp'),
                    'code_totp'
                );
            }
        } catch (\AnourValar\EloquentValidation\Exceptions\ValidationException $e) {
            throw new \App\Exceptions\ValidationException(['email' => trans('auth.failed')]);
        }

        // Аутентификация
        \Auth::login($user, $request->boolean('remember'));

        // Etc
        $request->session()->regenerate();
        $journalService->captureMetric('user_session_obtain');

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Завершение сессии
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect(\App\Providers\RouteServiceProvider::HOME);
    }
}
