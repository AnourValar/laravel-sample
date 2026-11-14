<?php

namespace App\Handlers\Models\Directory\Type;

interface TypeInterface
{
    /**
     * Валидация: data
     *
     * @param \App\Directory $directory
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function validate(\App\Directory $directory, \Illuminate\Validation\Validator &$validator): void;

    /**
     * Возвращает поля для поиска
     *
     * @param \App\Directory $directory
     * @return array
     */
    public function search(\App\Directory $directory): array;

    /**
     * Действия при удалении
     *
     * @param \App\Directory $directory
     * @return void
     */
    public function onDeleted(\App\Directory $directory): void;

    /**
     * Генерация fake data для фабрики
     *
     * @param string $type
     * @param \Faker\Generator $faker
     * @return array
     */
    public function generateFake(string $type, \Faker\Generator $faker): array;
}
