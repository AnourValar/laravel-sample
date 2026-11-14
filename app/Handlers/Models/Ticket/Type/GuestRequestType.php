<?php

namespace App\Handlers\Models\Ticket\Type;

class GuestRequestType implements RenderInterface, TypeInterface
{
    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Ticket\Type\TypeInterface::validate()
     */
    public function validate(\App\Ticket $ticket, \Illuminate\Validation\Validator &$validator): void
    {
        $validator->addCustomAttributes((array) trans('models/ticket.type_handler.guest_request.attributes'));

        // data_request
        $validator->addRules([
            'data_request' => ['required', 'array', 'array_keys_auto'],
                'data_request.phone' => ['nullable', 'string', 'max:20', 'phone'],
                'data_request.email' => ['required_without:data_request.phone', 'nullable', 'string', 'max:70', 'email:filter'],
                'data_request.message' => ['nullable', 'string', 'min:2', 'max:500'],
                'data_request.first_name' => ['nullable', 'string', 'min:2', 'max:60'],
                'data_request.last_name' => ['nullable', 'string', 'min:2', 'max:60'],
                'data_request.middle_name' => ['nullable', 'string', 'min:2', 'max:60'],
        ]);

        // data_response
        $validator->addRules([
            'data_response' => ['nullable', 'prohibited'],
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
     * @see \App\Handlers\Models\Ticket\Type\TypeInterface::onClosed()
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
        $phone = mt_rand(0, 1) ? $faker->unique()->regexify($faker->randomElement(config('instance.phone.faker'))) : null;

        return [
            'phone' => $phone,
            'email' => (mt_rand(0, 1) || ! $phone) ? $faker->safeEmail() : null,
            'message' => mt_rand(0, 1) ? $faker->realTextBetween(50, 400) : null,
            'first_name' => mt_rand(0, 1) ? $faker->firstName() : null,
            'last_name' => mt_rand(0, 1) ? $faker->lastName() : null,
            'middle_name' => mt_rand(0, 1) ? $faker->firstName() : null,
        ];
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
        return null;
    }

    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Ticket\Type\RenderInterface::renderRequest()
     */
    public function renderRequest(\App\Ticket $ticket): string
    {
        return view('handlers.models.ticket.type.guest_request_request', compact('ticket'))->render();
    }

    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Ticket\Type\RenderInterface::renderResponse()
     */
    public function renderResponse(\App\Ticket $ticket, string $name): string
    {
        return view('handlers.models.ticket.type.guest_request_response', compact('ticket', 'name'))->render();
    }
}
