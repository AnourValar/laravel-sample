<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Directory>
 */
class DirectoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => function (array $attributes) {
                $hasUser = is_numeric($attributes['user_id']);
                $hasntUser = is_null($attributes['user_id']);

                $types = \ConfigHelper::keys('models.directory.type');
                shuffle($types);

                $type = null;
                foreach ($types as $type) {
                    $supportUser = in_array('prohibited', config("models.directory.type.{$type}.rules.user_id") ?? []);

                    if ($hasUser && $supportUser) {
                        return $type;
                    } elseif ($hasntUser && ! $supportUser) {
                        return $type;
                    }
                }

                return $type;
            },
            'user_id' => function (array $attributes) {
                if (\ConfigHelper::hasItem("models.directory.type.{$attributes['type']}.rules.user_id", 'prohibited')) {
                    return null;
                }

                return \App\User::factory()->create(['role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true])]);
            },
            'data' => function (array $attributes) {
                return \App::make(config("models.directory.type.{$attributes['type']}.bind"))->generateFake($attributes['type'], $this->faker);
            },
            'created_at' => $this->faker->dateTimeBetween('-2 years'),
        ];
    }

    /**
     * Существующий клиент
     *
     * @param int|array $excludeIds
     * @return static
     */
    public function existingUser(int|array $excludeIds = [])
    {
        $users = \Cache::driver('array')->rememberForever(__METHOD__, function () {
            $roles = \ConfigHelper::keys('models.user.role', ['is_customer' => true]);
            return \App\User::withTrashed()->whereIn('role', $roles)->get(['id']);
        });
        $users = $users->whereNotIn('id', $excludeIds);

        return $this->state(function (array $attributes) use ($users) {
            return [
                'user_id' => function (array $attributes) use ($users) {
                    if (\ConfigHelper::hasItem("models.directory.type.{$attributes['type']}.rules.user_id", 'prohibited')) {
                        return null;
                    }

                    return $users->shuffle()->first();
                },
            ];
        });
    }
}
