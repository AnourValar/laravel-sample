<?php

namespace App\Handlers\Models\User\Role;

interface OnlineInterface
{
    /**
     * Войти под пользователем
     *
     * @param \App\User $user
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function loginAs(\App\User $user): \Symfony\Component\HttpFoundation\Response;
}
