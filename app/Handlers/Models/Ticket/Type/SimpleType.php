<?php

namespace App\Handlers\Models\Ticket\Type;

class SimpleType implements RenderInterface, TypeInterface
{
    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Ticket\Type\TypeInterface::validate()
     */
    public function validate(\App\Ticket $ticket, \Illuminate\Validation\Validator &$validator): void
    {
        $validator->addCustomAttributes((array) trans('models/ticket.type_handler.simple.attributes'));

        // data_request
        $validator->addRules([
            'data_request' => ['required', 'array', 'array_keys_auto'],
                'data_request.message' => ['required', 'string', 'min:2', 'max:5000'],
        ]);

        // data_response
        $validator->addRules([
            'data_response' => ['nullable', 'array', 'array_keys_auto'],
                'data_response.message' => ['sometimes', 'required', 'string', 'min:2', 'max:5000'],
        ]);
    }

    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Ticket\Type\TypeInterface::onCreated()
     */
    public function onCreated(\App\Ticket $ticket): void
    {

    }

    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Ticket\Type\TypeInterface::onCreating()
     */
    public function onClosed(\App\Ticket $ticket): void
    {

    }

    /**
     * Генерация fake request_data для фабрики
     *
     * @param string $type
     * @param \Faker\Generator $faker
     * @return array|null
     */
    public function generateFakeRequest(string $type, \Faker\Generator $faker): ?array
    {
        return ['message' => $faker->realTextBetween(50, 1000)];
    }

    /**
     * Генерация fake response_data для фабрики
     *
     * @param string $type
     * @param \Faker\Generator $faker
     * @return array|null
     */
    public function generateFakeResponse(string $type, \Faker\Generator $faker): ?array
    {
        if (! mt_rand(0, 3)) {
            return null;
        }

        return ['message' => $faker->realTextBetween(50, 1000)];
    }

    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Ticket\Type\RenderInterface::renderRequest()
     */
    public function renderRequest(\App\Ticket $ticket): string
    {
        return view('handlers.models.ticket.type.simple_request', compact('ticket'))->render();
    }

    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Ticket\Type\RenderInterface::renderResponse()
     */
    public function renderResponse(\App\Ticket $ticket, string $name): string
    {
        return view('handlers.models.ticket.type.simple_response', compact('ticket', 'name'))->render();
    }
}
