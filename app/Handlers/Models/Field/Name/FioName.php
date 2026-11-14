<?php

namespace App\Handlers\Models\Field\Name;

class FioName implements NameInterface
{
    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Field\Name\NameInterface::validate()
     */
    public function validate(\App\Field $field, \Illuminate\Validation\Validator &$validator): void
    {
        $validator->addCustomAttributes((array) trans('models/field.name_handler.fio.attributes'));

        $validator->addRules([
            'data' => ['required', 'array_keys_auto'],
                'data.first_name' => ['required', 'string', 'min:2', 'max:60'],
                'data.last_name' => ['required', 'string', 'min:2', 'max:60'],
                'data.middle_name' => ['sometimes', 'required', 'string', 'min:2', 'max:60'],
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
        return view('handlers.models.field.name.fio', compact('field', 'raw'))->render();
    }

    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Field\Name\NameInterface::generateFake()
     */
    public function generateFake(string $name, \Faker\Generator $faker): ?array
    {
        $gender = $faker->randomElement([\Faker\Provider\Person::GENDER_MALE, \Faker\Provider\Person::GENDER_FEMALE]);

        return array_filter([
            'first_name' => $faker->firstName($gender),
            'last_name' => $faker->lastName($gender),
            'middle_name' => mt_rand(0, 2) ? $faker->middleName($gender) : null,
        ]);
    }

    /**
     * Введённое имя
     *
     * @param \App\Field $field
     * @return string
     */
    public function getFirstName(\App\Field $field): string
    {
        return $field->data['first_name'];
    }

    /**
     * Введённая фамилия
     *
     * @param \App\Field $field
     * @return string
     */
    public function getLastName(\App\Field $field): string
    {
        return $field->data['last_name'];
    }

    /**
     * Введённое отчество
     *
     * @param \App\Field $field
     * @return string|null
     */
    public function getMiddleName(\App\Field $field): ?string
    {
        return $field->data['middle_name'] ?? null;
    }
}
