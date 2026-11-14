<?php

namespace App\Handlers\Models\User\Role;

interface RoleInterface
{
    /**
     * Валидация: role_data
     *
     * @param \App\User $user
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function validate(\App\User $user, \Illuminate\Validation\Validator &$validator): void;

    /**
     * Возвращает поля для поиска
     *
     * @param \App\User $user
     * @return array
     */
    public function search(\App\User $user): array;

    /**
     * Действия при изменении role_data
     *
     * @param \App\User $user
     * @return void
     */
    public function onChanged(\App\User $user): void;

    /**
     * Генерация fake role_data для фабрики
     *
     * @param string $role
     * @param \Faker\Generator $faker
     * @return array|null
     */
    public function generateFake(string $role, \Faker\Generator $faker): ?array;
}
