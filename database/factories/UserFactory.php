<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\User>
 */
class UserFactory extends Factory
{
    /**
     * @var string
     */
    public const TOTP_SECRET_REF = 'SZPWE7KDNKOESPV2IBMY57CZDDEJI5ZS';

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $roles = \ConfigHelper::keys('models.user.role', fn ($value) => \ConfigHelper::hasItem($value['rules']['pid'] ?? [], 'prohibited'));
        shuffle($roles);

        $genders = array_keys(config('models.user.gender'));
        shuffle($genders);

        return [
            'pid' => function (array $attributes) {
                if (\ConfigHelper::hasItem("models.user.role.{$attributes['role']}.rules.pid", 'prohibited')) {
                    return null;
                }

                return \App\User::factory()->create();
            },
            'role' => $roles[0],
            'role_data' => function (array $attributes) {
                $role = $attributes['role'];
                return \App::make(config("models.user.role.{$role}.bind"))->generateFake($role, $this->faker);
            },
            'email' => function (array $attributes) {
                if (\ConfigHelper::hasItem("models.user.role.{$attributes['role']}.rules.email", 'prohibited')) {
                    return null;
                }

                $email = $this->faker->unique()->safeEmail();
                $email = explode('@', $email);
                return sprintf('%s%d@%s', $email[0], mt_rand(1, 99), $email[1]);
            },
            'phone' => function (array $attributes) {
                if (! \ConfigHelper::hasItem("models.user.role.{$attributes['role']}.rules.phone", 'required') && ! mt_rand(0, 2)) {
                    return null;
                }

                return $this->faker->unique()->regexify($this->faker->randomElement(config('instance.phone.faker')));
            },
            'password' => function (array $attributes) {
                if (\ConfigHelper::hasItem("models.user.role.{$attributes['role']}.rules.password", 'prohibited')) {
                    return null;
                }

                return \Hash::make('password');
            },
            'totp_secret' => function () {
                if (! mt_rand(0, 1)) {
                    return null;
                }

                $confirmService = \App::make(\AnourValar\EloquentNotification\ConfirmService::class);
                return $confirmService->cryptogramTotp($confirmService->generateTotp());
            },
            'additional_abilities' => function (array $attributes) {
                if (\ConfigHelper::hasItem("models.user.role.{$attributes['role']}.rules.additional_abilities", 'prohibited')) {
                    return null;
                }

                $result = [];

                $curr = config("models.user.role.{$attributes['role']}.abilities");
                foreach (\ConfigHelper::keys('models.user.additional_abilities') as $item) {
                    if (in_array($item, $curr) || ! mt_rand(0, 2)) {
                        continue;
                    }

                    $result[] = $item;
                }

                return $result;
            },
            'first_name' => fn (array $attributes) => $this->faker->firstName($attributes['gender'] ?? $genders[0]),
            'last_name' => fn (array $attributes) => mt_rand(0, 2) ? $this->faker->lastName($attributes['gender'] ?? $genders[0]) : null,
            'middle_name' => fn (array $attributes) => mt_rand(0, 2) ? $this->faker->middleName($attributes['gender'] ?? $genders[0]) : null,
            'gender' => mt_rand(0, 2) ? $genders[0] : null,
            'birthday' => mt_rand(0, 2) ? $this->faker->dateTimeBetween('-50 years', '-18 years')->format('Y-m-d') : null,
            'telegram_username' => mt_rand(0, 1) ? $this->faker->unique()->userName() : null,
            'tag_ids' => function (array $attributes) {
                if (! mt_rand(0, 1)) {
                    return null;
                }

                return [\App\Directory::factory()->create(['type' => 'user_tag'])->id];
            },
            'comments' => mt_rand(0, 1) ? $this->faker->realTextBetween(20, 200) : null,
            'created_at' => $this->faker->dateTimeBetween('-2 years'),
        ];
    }

    /**
     * Имеющий флаг (роль)
     *
     * @param array|callable $operation
     * @return static
     */
    public function hasRole(array|callable $operation)
    {
        return $this->state(function (array $attributes) use ($operation) {
            $roles = \ConfigHelper::keys('models.user.role', $operation);
            shuffle($roles);

            return [
                'role' => $roles[0],
            ];
        });
    }

    /**
     * Имеющий право (роль)
     *
     * @param mixed $ability
     * @return static
     */
    public function hasAbility(mixed $ability)
    {
        return $this->state(function (array $attributes) use ($ability) {
            $roles = \ConfigHelper::keys(
                'models.user.role',
                fn ($value) => \ConfigHelper::hasItem($value['abilities'], $ability) && \ConfigHelper::hasItem($value['rules']['pid'] ?? [], 'prohibited')
            );
            shuffle($roles);

            return [
                'role' => $roles[0],
            ];
        });
    }

    /**
     * Существующий родительский пользователь
     *
     * @param array $excludeIds
     * @return static
     */
    public function existingPid(array $excludeIds = [])
    {
        $users = \Cache::driver('array')->rememberForever(implode(' / ', [__METHOD__, json_encode($excludeIds)]), function () use ($excludeIds) {
            $roles = \ConfigHelper::keys('models.user.role', ['has_childs' => true]);
            $users = \App\User::withTrashed()->whereIn('role', $roles)->whereNotIn('id', $excludeIds)->get(['id']);

            return $users->count() ? $users : null;
        });

        return $this->state(function (array $attributes) use ($users) {
            return [
                'pid' => function (array $attributes) use ($users) {
                    if (\ConfigHelper::hasItem("models.user.role.{$attributes['role']}.rules.pid", 'prohibited')) {
                        return null;
                    }

                    return $users->shuffle()->first();
                },
            ];
        });
    }

    /**
     * Существующие теги пользователя
     *
     * @param int|array $excludeIds
     * @return static
     */
    public function existingTags(int|array $excludeIds = [])
    {
        $tags = \Cache::driver('array')->rememberForever(__METHOD__, function () {
            return \App\Directory::where('type', '=', 'user_tag')->get(['id']);
        });
        $tags = $tags->whereNotIn('id', (array) $excludeIds);

        return $this->state(function (array $attributes) use ($tags) {
            return [
                'tag_ids' => $tags->shuffle()->take(mt_rand(0, 3))->pluck('id')->toArray(),
            ];
        });
    }

    /**
     * Фиксированный TOTP (для тестов)
     * 2025-10-14 10:50:00 => 080199
     *
     * @return static
     */
    public function totpFixed()
    {
        return $this->state(function (array $attributes) {
            return [
                'totp_secret' => \App::make(\AnourValar\EloquentNotification\ConfirmService::class)->cryptogramTotp(self::TOTP_SECRET_REF),
            ];
        });
    }
}
