<?php

namespace App\Handlers\Models\Setting\Name;

class UserDemoName implements NameInterface
{
    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Setting\Name\NameInterface::normalize()
     */
    public function normalize($value)
    {
        return (new \AnourValar\EloquentValidation\ValidatorHelper())->mutateArray(
            $value,
            types: ['$.phone' => 'string', '$.code' => 'string', '$.enabled' => 'boolean'],
        );
    }

    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Setting\Name\NameInterface::validate()
     */
    public function validate(\App\Setting $setting, \Illuminate\Validation\Validator &$validator): void
    {
        $validator->addCustomAttributes((array) trans('models/setting.name_handler.user_demo.attributes'));

        $validator->addRules([
            'value' => ['required', 'array', 'array_keys_auto'],
                'value.phone' => ['required', 'max:20', 'phone'],
                'value.code' => ['required', 'string', 'regex:/^\d{6}$/'],
                'value.enabled' => ['required', 'boolean'],
        ]);
    }

    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\Setting\Name\NameInterface::renderEdit()
     */
    public function renderEdit(string $inputName, array $value): string
    {
        return view('handlers.models.setting.name.user_demo', compact('inputName', 'value'))->render();
    }
}
