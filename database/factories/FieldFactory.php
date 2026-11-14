<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Field>
 */
class FieldFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => function (array $attributes) {
                return \App\User::factory()->create(['role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true])]);
            },
            'name' => \ConfigHelper::randomKey('models.field.name', ['is_readonly' => false]),
            'data' => function (array $attributes) {
                $name = $attributes['name'];
                return \App::make(config("models.field.name.{$name}.bind"))->generateFake($name, $this->faker);
            },
            'status' => \ConfigHelper::randomKey('models.field.status'),
            'created_at' => $this->faker->dateTimeBetween('-2 years'),
        ];
    }

    /**
     * Существующий пользователь
     *
     * @param int|array $excludeIds
     * @return static
     */
    public function existingUser(int|array $excludeIds = 0)
    {
        $users = \Cache::driver('array')->rememberForever(__METHOD__, function () {
            $roles = \ConfigHelper::keys('models.user.role', ['is_customer' => true]);
            return \App\User::withTrashed()->whereIn('role', $roles)->get(['id']);
        });
        $users = $users->whereNotIn('id', (array) $excludeIds);

        return $this->state(function (array $attributes) use ($users) {
            return [
                'user_id' => function (array $attributes) use ($users) {
                    return $users->shuffle()->first();
                },
            ];
        });
    }
}
