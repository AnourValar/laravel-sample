<?php

namespace App\Handlers\Models\User\Role;

class NominalRole implements RoleInterface
{
    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\User\Role\RoleInterface::validate()
     */
    public function validate(\App\User $user, \Illuminate\Validation\Validator &$validator): void
    {
        $validator->addRules([
            'role_data' => ['nullable', 'prohibited'],
        ]);
    }

    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\User\Role\RoleInterface::search()
     */
    public function search(\App\User $user): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\User\Role\RoleInterface::onChanged()
     */
    public function onChanged(\App\User $user): void
    {

    }

    /**
     * {@inheritDoc}
     * @see \App\Handlers\Models\User\Role\RoleInterface::generateFake()
     */
    public function generateFake(string $role, \Faker\Generator $faker): ?array
    {
        return null;
    }
}
