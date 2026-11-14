<?php

namespace App\Handlers\Models\Ticket\Type;

interface TypeInterface
{
    /**
     * Валидация: data_request, data_response
     *
     * @param \App\Ticket $ticket
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function validate(\App\Ticket $ticket, \Illuminate\Validation\Validator &$validator): void;

    /**
     * Действия после открытия (создания)
     *
     * @param \App\Ticket $ticket
     * @return void
     */
    public function onCreated(\App\Ticket $ticket): void;

    /**
     * Действия после закрытия
     *
     * @param \App\Ticket $ticket
     * @return void
     */
    public function onClosed(\App\Ticket $ticket): void;

    /**
     * Генерация fake request_data для фабрики
     *
     * @param string $type
     * @param \Faker\Generator $faker
     * @return array|null
     */
    public function generateFakeRequest(string $type, \Faker\Generator $faker): ?array;

    /**
     * Генерация fake response_data для фабрики
     *
     * @param string $type
     * @param \Faker\Generator $faker
     * @return array|null
     */
    public function generateFakeResponse(string $type, \Faker\Generator $faker): ?array;
}
