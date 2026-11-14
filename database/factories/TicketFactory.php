<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'author_id' => function (array $attributes) {
                if (\ConfigHelper::hasItem("models.ticket.type.{$attributes['type']}.rules.author_id", 'prohibited')) {
                    return null;
                }

                return \App\User::factory()->create(['role' => \ConfigHelper::randomKey('models.user.role', ['is_customer' => true])]);
            },
            'moderator_id' => function (array $attributes) {
                if (($attributes['status'] ?? null) == new \App\Ticket()->status) {
                    return null;
                }

                return \App\User::factory()->create(['role' => \ConfigHelper::randomKey('models.user.role', ['is_staff' => true])]);
            },
            'type' => \ConfigHelper::randomKey('models.ticket.type', ['is_manual' => true]),
            'data_request' => function (array $attributes) {
                $type = $attributes['type'];
                return \App::make(config("models.ticket.type.{$type}.bind"))->generateFakeRequest($type, $this->faker);
            },
            'data_response' => function (array $attributes) {
                if (is_string($attributes['status'] ?? null) && $attributes['status'] == new \App\Ticket()->status) {
                    return null;
                }

                $type = $attributes['type'];
                return \App::make(config("models.ticket.type.{$type}.bind"))->generateFakeResponse($type, $this->faker);
            },
            'status' => \ConfigHelper::randomKey('models.ticket.status'),
            'created_at' => $this->faker->dateTimeBetween('-2 years'),
        ];
    }

    /**
     * Существующий автор
     *
     * @return static
     */
    public function existingAuthor()
    {
        $users = \Cache::driver('array')->rememberForever(__METHOD__, function () {
            $roles = \ConfigHelper::keys('models.user.role', ['is_customer' => true]);
            return \App\User::withTrashed()->whereIn('role', $roles)->get(['id']);
        });

        return $this->state(function (array $attributes) use ($users) {
            return [
                'author_id' => function (array $attributes) use ($users) {
                    if (\ConfigHelper::hasItem("models.ticket.type.{$attributes['type']}.rules.author_id", 'prohibited')) {
                        return null;
                    }

                    return $users->shuffle()->first();
                },
            ];
        });
    }

    /**
     * Существующий модератор
     *
     * @return static
     */
    public function existingModerator()
    {
        $users = \Cache::driver('array')->rememberForever(__METHOD__, function () {
            $roles = \ConfigHelper::keys('models.user.role', ['is_staff' => true]);
            return \App\User::withTrashed()->whereIn('role', $roles)->get(['id']);
        });

        return $this->state(function (array $attributes) use ($users) {
            return [
                'moderator_id' => function (array $attributes) use ($users) {
                    if (($attributes['status'] ?? null) == new \App\Ticket()->status) {
                        return null;
                    }

                    return $users->shuffle()->first();
                },
            ];
        });
    }
}
