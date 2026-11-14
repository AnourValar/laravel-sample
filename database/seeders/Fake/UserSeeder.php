<?php

namespace Database\Seeders\Fake;

use AnourValar\EloquentNotification\ConfirmService;
use Database\Factories\UserFactory;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    use \AnourValar\LaravelAtom\Traits\PostgresTrait;

    /**
     * @var array
     */
    private $reference = [
        'admin' => [
            'email' => 'admin@laravel-sample.loc',
            'password' => 'password',
        ],

        'staff' => [
            'pid' => null,
            'email' => 'staff@laravel-sample.loc',
            'phone' => null,
            'password' => 'password',
        ],

        'client' => [
            'pid' => null,
            'email' => 'client@laravel-sample.loc',
            'phone' => '79000000000',
            'password' => 'password',
            'totp_secret' => UserFactory::TOTP_SECRET_REF,
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Эталонные пользователи
        $table = [];
        foreach ($this->reference as $key => $data) {
            if ($key == 'admin') {
                \Artisan::call('user:admin', $data);
                $user = \App\User::first()->validate();
            } else {
                if (isset($data['role'])) {
                    $key = $data['role'];
                }

                $additional = [
                    'role' => $key,
                    'password' => isset($data['password']) ? \Hash::make($data['password']) : null,
                    'additional_abilities' => null,
                    'totp_secret' => isset($data['totp_secret']) ? \App::make(ConfirmService::class)->cryptogramTotp($data['totp_secret']) : null,
                ];
                $user = tap(\App\User::factory()->existingPid()->existingTags()->make(array_replace($data, $additional))->validate())->save();
            }

            $this->fillUser($user, true);

            $table[] = [
                'role' => $key,
                'email' => $data['email'] ?? '-',
                'phone' => $data['phone'] ?? '-',
                'password' => $data['password'] ?? '-',
                'route' => config("models.user.role.{$key}.route") ? route(config("models.user.role.{$key}.route")) : url(''),
                'token' => (isset($data['email']) || isset($data['phone'])) ? $user->createToken('Reference')->plainTextToken : '-',
            ];
            $this->syncAutoIncrement(new \App\User());
        }

        $this->command->alert('Reference users');
        $this->command->table(['Role', 'Email', 'Phone', 'Password', 'Route', 'Token'], $table);


        // Массовка
        foreach (\ConfigHelper::keys('models.user.role') as $role) {
            \App\User::factory()
                ->count(ceil(20 * DatabaseSeeder::FAKE_COEF))
                ->state(['role' => $role])
                ->existingPid()
                ->existingTags()
                ->make()
                ->each(fn ($user) => $this->fillUser($user));
        }
    }

    /**
     * @param \App\User $user
     * @param bool $ref
     * @return void
     */
    public function fillUser(\App\User $user, bool $ref = false): void
    {
        // Сохранение
        $user->validate()->save();

        // События
        event(new \Illuminate\Auth\Events\Registered($user));

        // Логи
        if ($user->email || $user->phone) {
            \App\Journal::factory()
                ->make(['user_id' => $user->id, 'type' => 'metric', 'event' => 'user_token_obtain'])
                ->validate()
                ->save();
        }

        // Удаление?
        if (! $ref && ! mt_rand(0, 10)) {
            $user->validateDelete()->delete();
        }
    }
}
