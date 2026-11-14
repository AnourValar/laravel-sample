<?php

namespace App\Handlers\Models\User\Role;

interface FormInterface
{
    /**
     * Возвращает рендеринг анкеты пользователя
     *
     * @param \App\User $user
     * @param string $name
     * @return string
     */
    public function renderEdit(\App\User $user, string $name): string;
}
