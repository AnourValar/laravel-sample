<?php

namespace App\Handlers\Models\Field\Name;

class RegexpName implements NameInterface
{
    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Field\Name\NameInterface::validate()
     */
    public function validate(\App\Field $field, \Illuminate\Validation\Validator &$validator): void
    {
        $validator->addCustomAttributes((array) trans('models/field.name_handler.regexp.attributes'));

        $validator->addRules([
            'data' => ['required', 'array_keys_auto'],
                'data.value' => ['required', 'string', "regex:{$field->name_details['options']['regexp']}"],
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
        return view('handlers.models.field.name.regexp', compact('field', 'raw'))->render();
    }

    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Field\Name\NameInterface::generateFake()
     */
    public function generateFake(string $name, \Faker\Generator $faker): ?array
    {
        $config = config("models.field.name.{$name}.options");

        return ['value' => isset($config['faker']) ? $faker->{$config['faker']}() : $faker->regexify(rtrim($config['regexp'], 'uUisSm'))];
    }

    /**
     * Введённое значение
     *
     * @param \App\Field $field
     * @return string
     */
    public function getValue(\App\Field $field): string
    {
        return $field->data['value'];
    }
}
