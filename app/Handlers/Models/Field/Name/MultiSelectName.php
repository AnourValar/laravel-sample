<?php

namespace App\Handlers\Models\Field\Name;

class MultiSelectName implements NameInterface
{
    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Field\Name\NameInterface::validate()
     */
    public function validate(\App\Field $field, \Illuminate\Validation\Validator &$validator): void
    {
        $validator->addCustomAttributes((array) trans('models/field.name_handler.multi_select.attributes'));

        $validator->addRules([
            'data' => ['required', 'array_keys_auto'],
                'data.values' => ['required', 'list', 'min:1', 'max:10'],
                    'data.values.*' => ['required', 'string', "config:models.field.name.{$field->name}.options.values", 'distinct'],
        ]);
    }

    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Field\Name\NameInterface::tags()
     */
    public function tags(\App\Field $field): array
    {
        return array_map(fn ($item) => ($field->name . ':' . $item), $field->data['values']);
    }

    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Field\Name\NameInterface::renderShow()
     */
    public function renderShow(\App\Field $field, bool $raw = false): string
    {
        return view('handlers.models.field.name.multi_select', compact('field', 'raw'))->render();
    }

    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Field\Name\NameInterface::generateFake()
     */
    public function generateFake(string $name, \Faker\Generator $faker): ?array
    {
        $values = array_values(array_unique([
            \ConfigHelper::randomKey("models.field.name.{$name}.options.values"),
            \ConfigHelper::randomKey("models.field.name.{$name}.options.values"),
            \ConfigHelper::randomKey("models.field.name.{$name}.options.values"),
        ]));

        return ['values' => $values];
    }
}
