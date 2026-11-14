<?php

namespace App\Handlers\Models\Field\Name;

interface NameInterface
{
    /**
     * Валидация: data
     *
     * @param \App\Field $field
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function validate(\App\Field $field, \Illuminate\Validation\Validator &$validator): void;

    /**
     * Возвращает выданные теги
     *
     * @param \App\Field $field
     * @return array
     */
    public function tags(\App\Field $field): array;

    /**
     * Рендеринг карточки поля (просмотр)
     *
     * @param \App\Field $field
     * @param bool $raw
     * @return string
     */
    public function renderShow(\App\Field $field, bool $raw = false): string;

    /**
     * Генерация fake data для фабрики
     *
     * @param string $name
     * @param \Faker\Generator $faker
     * @return array|null
     */
    public function generateFake(string $name, \Faker\Generator $faker): ?array;
}
