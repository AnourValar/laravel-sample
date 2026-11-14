<?php

namespace App\Handlers\Models\Field\Name;

class IntegerName implements NameInterface, NormalizeInterface
{
    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Field\Name\NameInterface::validate()
     */
    public function validate(\App\Field $field, \Illuminate\Validation\Validator &$validator): void
    {
        $validator->addCustomAttributes((array) trans('models/field.name_handler.integer.attributes'));

        $validator->addRules([
            'data' => ['required', 'array_keys_auto'],
                'data.value' => ['required', 'integer', "min:{$field->name_details['options']['min']}", "max:{$field->name_details['options']['max']}"],
        ]);
    }

    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Field\Name\NormalizeInterface::normalizeData()
     */
    public function normalizeData(\App\Field $field): ?array
    {
        $data = $field->data;
        $data['value'] = (int) $data['value'];

        return $data;
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
        return view('handlers.models.field.name.integer', compact('field', 'raw'))->render();
    }

    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Field\Name\NameInterface::generateFake()
     */
    public function generateFake(string $name, \Faker\Generator $faker): ?array
    {
        $options = config("models.field.name.{$name}.options");

        return ['value' => $faker->numberBetween($options['min'], $options['max'])];
    }
}
