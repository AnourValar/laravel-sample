<?php

namespace App\Handlers\Models\Setting\Name;

interface NameInterface
{
    /**
     * Нормализация (casts): value
     *
     * @param mixed $value
     * @return mixed
     */
    public function normalize($value);

    /**
     * Валидация: value
     *
     * @param \App\Setting $setting
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function validate(\App\Setting $setting, \Illuminate\Validation\Validator &$validator): void;

    /**
     * Рендеринг карточки настройки (редактирование)
     *
     * @param string $inputName
     * @param array $value
     * @return string
     */
    public function renderEdit(string $inputName, array $value): string;
}
