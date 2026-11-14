<?php

namespace App\Helpers;

class StringHelper extends \AnourValar\LaravelAtom\Helpers\StringHelper
{
    /**
     * Условное хэширование пароля для корректной валидации
     *
     * @param mixed $password
     * @return mixed
     */
    public function hashPassword($password)
    {
        return \Hash::make($password);
    }
}
