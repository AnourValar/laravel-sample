<?php

namespace App\Handlers\Models\Field\Name;

class RuInnName implements NameInterface
{
    use \App\Handlers\InnTrait;

    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Field\Name\NameInterface::validate()
     */
    public function validate(\App\Field $field, \Illuminate\Validation\Validator &$validator): void
    {
        $validator->addCustomAttributes((array) trans('models/field.name_handler.ru_inn.attributes'));

        $closure = function ($attribute, $value, $fail) use ($field) {
            if (! $this->validateInn($value)) {
                return $fail(trans('validation.regex', ['attribute' => $field->name_title]));
            }
        };

        $validator->addRules([
            'data' => ['required', 'array_keys_auto'],
                'data.value' => ['required', 'string', 'bail', $closure],
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
        return view('handlers.models.field.name.ru_inn', compact('field', 'raw'))->render();
    }

    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Field\Name\NameInterface::generateFake()
     */
    public function generateFake(string $name, \Faker\Generator $faker): ?array
    {
        return ['value' => $faker->inn()];
    }
}
