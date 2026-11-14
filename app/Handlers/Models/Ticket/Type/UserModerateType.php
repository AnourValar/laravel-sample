<?php

namespace App\Handlers\Models\Ticket\Type;

class UserModerateType implements RenderInterface, TypeInterface
{
    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Ticket\Type\TypeInterface::validate()
     */
    public function validate(\App\Ticket $ticket, \Illuminate\Validation\Validator &$validator): void
    {
        $validator->addCustomAttributes((array) trans('models/ticket.type_handler.user_moderate.attributes'));

        // data_request
        $validator->addRules([
            'data_request' => ['nullable', 'prohibited'],
        ]);

        // data_response
        $keys = implode(',', $ticket->type_details['options']['fields']);
        $validator->addRules([
            'data_response' => ['nullable', 'array', 'array_keys_auto'],
                'data_response.fields' => ['sometimes', 'required', 'array', "array_keys:{$keys}"],
                    'data_response.fields.*' => ['required', 'string', 'min:2', 'max:100'],
        ]);
    }

    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Ticket\Type\TypeInterface::onCreated()
     */
    public function onCreated(\App\Ticket $ticket): void
    {
        $this->fieldsStatusToFlow($ticket, 'moderation'); // создание тикета доступно и для уже верифицированного юзера для инициирования вопроса изменения данных
    }

    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Ticket\Type\TypeInterface::onCreating()
     */
    public function onClosed(\App\Ticket $ticket): void
    {
        if ($ticket->status_details['flow'] == 'finish_success') {

            $this->fieldsStatusToFlow($ticket, 'accept');

        } elseif ($ticket->status_details['flow'] == 'finish_failure') {

            $this->fieldsStatusToFlow($ticket, 'draft');

        } else {

            throw new \RuntimeException('Incorrect usage.');

        }
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
        return null;
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

        $result = [];
        foreach (config("models.ticket.type.{$type}.options.fields") as $field) {
            if (mt_rand(0, 3)) {
                continue;
            }

            $result[$field] = $faker->realTextBetween(10, 70);
        }

        return ['fields' => $result];
    }

    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Ticket\Type\RenderInterface::renderRequest()
     */
    public function renderRequest(\App\Ticket $ticket): string
    {
        return view('handlers.models.ticket.type.user_moderate_request', compact('ticket'))->render();
    }

    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Ticket\Type\RenderInterface::renderResponse()
     */
    public function renderResponse(\App\Ticket $ticket, string $name): string
    {
        return view('handlers.models.ticket.type.user_moderate_response', compact('ticket', 'name'))->render();
    }

    /**
     * @param \App\Ticket $ticket
     * @param string $flow
     * @return void
     */
    private function fieldsStatusToFlow(\App\Ticket $ticket, string $flow): void
    {
        \Atom::lockUser($ticket->author_id, 'field');

        \App\Field::query()
            ->where('user_id', '=', $ticket->author_id)
            ->whereIn('name', $ticket->type_details['options']['fields'])
            ->update(['status' => \ConfigHelper::key('models.field.status', ['flow' => $flow])]);

        \Atom::onCommit(fn () => \App::make(\App\Services\FieldService::class)->flushCache($ticket->author_id));
    }
}
