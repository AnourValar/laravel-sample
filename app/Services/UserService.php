<?php

namespace App\Services;

class UserService
{
    /**
     * Создание токена
     *
     * @param \App\User $user
     * @param string $deviceName
     * @param array $abilities
     * @param array $extra
     * @return \Laravel\Sanctum\NewAccessToken
     */
    public function obtainToken(\App\User $user, string $deviceName, array $abilities = ['*'], array $extra = []): \Laravel\Sanctum\NewAccessToken
    {
        // Выпуск токена
        $isWildcard = $abilities == ['*'];
        $token = $user->createToken(mb_substr($deviceName, 0, 150), $abilities, $isWildcard ? now()->addYear() : null);


        // Доп. поля
        if ($ipAddress = \Request::ip()) {
            $extra['ip_address'] = $ipAddress;
        }

        if ($extra) {
            $token->accessToken->mergeCasts(config('models.user.token_casts'))->getModel()->forceFill($extra)->save();
        }

        if (! empty($extra['ip_address'])) {
            \Atom::onCommit(fn () => dispatch(new \App\Jobs\TokenGeoLocationJob($token->accessToken->id, $extra['ip_address'])));
        }


        // Возвращаем токен
        return $token;
    }
}
