<?php

namespace App\Handlers\Models\Field\Name;

class DateName implements NameInterface
{
    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Field\Name\NameInterface::validate()
     */
    public function validate(\App\Field $field, \Illuminate\Validation\Validator &$validator): void
    {
        $validator->addCustomAttributes((array) trans('models/field.name_handler.date.attributes'));

        $validator->addRules([
            'data' => ['required', 'array_keys_auto'],
                'data.value' => ['required', 'string', 'date_format:Y-m-d'],
        ]);
    }

    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Field\Name\NameInterface::tags()
     */
    public function tags(\App\Field $field): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Field\Name\NameInterface::renderShow()
     */
    public function renderShow(\App\Field $field, bool $raw = false): string
    {
        return view('handlers.models.field.name.date', compact('field', 'raw'))->render();
    }

    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Field\Name\NameInterface::generateFake()
     */
    public function generateFake(string $name, \Faker\Generator $faker): ?array
    {
        return ['value' => $faker->date()];
    }

    /**
     * Введённая дата
     *
     * @param \App\Field $field
     * @return string
     */
    public function getValue(\App\Field $field): string
    {
        return $field->data['value'];
    }
}
