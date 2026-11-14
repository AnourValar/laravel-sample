<?php

namespace App\Handlers\Models\Field\Name;

class SelectName implements NameInterface
{
    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Field\Name\NameInterface::validate()
     */
    public function validate(\App\Field $field, \Illuminate\Validation\Validator &$validator): void
    {
        $validator->addCustomAttributes((array) trans('models/field.name_handler.select.attributes'));

        $validator->addRules([
            'data' => ['required', 'array_keys_auto'],
                'data.value' => ['required', 'string', "config:models.field.name.{$field->name}.options.values"],
        ]);
    }

    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Field\Name\NameInterface::tags()
     */
    public function tags(\App\Field $field): array
    {
        return [$field->name . ':' . $field->data['value']];
    }

    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Field\Name\NameInterface::renderShow()
     */
    public function renderShow(\App\Field $field, bool $raw = false): string
    {
        return view('handlers.models.field.name.select', compact('field', 'raw'))->render();
    }

    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Field\Name\NameInterface::generateFake()
     */
    public function generateFake(string $name, \Faker\Generator $faker): ?array
    {
        return ['value' => \ConfigHelper::randomKey("models.field.name.{$name}.options.values")];
    }

    /**
     * Выбранное значение
     *
     * @param \App\Field $field
     * @return string
     */
    public function getValue(\App\Field $field): string
    {
        return $field->data['value'];
    }
}
